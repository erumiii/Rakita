<?php

namespace App\Console\Commands;

use App\Services\ReservationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ExpireReservations extends Command
{
    protected $signature = 'reservations:expire';
    protected $description = 'Expire stale reservation offers and promote queue heads';

    public function handle(): int
    {
        // Default superuser connection bypasses RLS on purpose: batch job must
        // see all rows, including queue heads owned by other members.
        $db = DB::connection('pgsql');
        $now = now();

        $expired = $db->table('reservations')
            ->where('status', 'pending')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', $now)
            ->update(['status' => 'expired', 'updated_at' => $now]);

        // Promote the oldest queued reservation on every available book that
        // has no live offer (covers expiries and member-side cancellations).
        $heads = $db->table('reservations as r')
            ->join('books as b', 'b.id', '=', 'r.book_id')
            ->where('r.status', 'pending')
            ->whereNull('r.expires_at')
            ->where('b.status', 'isAvailable')
            ->whereNull('b.deleted_at')
            ->whereNotExists(function ($q) use ($now) {
                $q->select(DB::raw(1))
                    ->from('reservations as r2')
                    ->whereColumn('r2.book_id', 'r.book_id')
                    ->where('r2.status', 'pending')
                    ->where('r2.expires_at', '>', $now);
            })
            ->orderBy('r.book_id')
            ->orderBy('r.created_at')
            ->get(['r.id', 'r.book_id']);

        $promoted = 0;
        $seen = [];
        foreach ($heads as $head) {
            if (isset($seen[$head->book_id])) {
                continue;
            }
            $seen[$head->book_id] = true;
            $db->table('reservations')->where('id', $head->id)->update([
                'expires_at' => $now->copy()->addDays(ReservationService::PICKUP_DAYS),
                'updated_at' => $now,
            ]);
            $promoted++;
        }

        $this->info("Expired {$expired}, promoted {$promoted}.");

        return self::SUCCESS;
    }
}
