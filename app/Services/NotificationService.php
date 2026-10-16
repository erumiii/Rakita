<?php

namespace App\Services;

use App\Models\Borrowing;
use App\Models\Notification;
use App\Models\Reservation;
use Illuminate\Support\Facades\DB;

class NotificationService
{
    public const DUE_SOON_DAYS = 3;

    /**
     * Writes go through the superuser connection on purpose: PG16 requires an
     * INSERT..RETURNING row to also pass the SELECT policy, so a cross-target
     * insert (e.g. employee context writing a member row) would fail on
     * pgsql_app even with a matching INSERT policy. Inserts are system-
     * internal and server-built; RLS still governs every read.
     */
    public function notify(?int $userId, ?string $role, string $type, array $payload): void
    {
        DB::connection('pgsql')->table('notifications')->insert([
            'user_id' => $userId,
            'recipient_role' => $role,
            'type' => $type,
            'payload' => json_encode($payload),
            'created_at' => now(),
        ]);
    }

    public function notifyEmployees(string $type, array $payload): void
    {
        $this->notify(null, 'employee', $type, $payload);
    }

    public function dueSoon(): void
    {
        Borrowing::on('pgsql')
            ->whereNull('returned_at')
            ->whereBetween('due_at', [now()->startOfDay(), now()->addDays(self::DUE_SOON_DAYS)->endOfDay()])
            ->with(['member', 'book'])
            ->get()
            ->each(fn ($b) => $this->notifyBorrowing($b, 'borrowing.due_soon'));
    }

    public function overdue(): void
    {
        Borrowing::on('pgsql')
            ->whereNull('returned_at')
            ->where('due_at', '<', now()->startOfDay())
            ->with(['member', 'book'])
            ->get()
            ->each(fn ($b) => $this->notifyBorrowing($b, 'borrowing.overdue'));
    }

    public function pickupReady(): void
    {
        // Live pickup offers live on PENDING rows with a future expires_at —
        // promoteNext()/ExpireReservations never set status 'fulfilled'.
        Reservation::on('pgsql')
            ->offered()
            ->with('book')
            ->get()
            ->each(function (Reservation $r) {
                if ($this->dedupe('reservation.pickup', 'reservation_id', (string) $r->id)) {
                    return;
                }
                $this->notify($r->member_id, null, 'reservation.pickup', [
                    'title' => $r->book->title,
                    'link' => '/member/reservations',
                    'reservation_id' => (string) $r->id,
                ]);
            });
    }

    private function notifyBorrowing(Borrowing $b, string $type): void
    {
        if ($this->dedupe($type, 'borrowing_id', (string) $b->id)) {
            return;
        }

        // One transaction so a partial failure can't strand the pair: dedupe
        // keys on the borrowing_id, so a missing half would never regenerate.
        DB::connection('pgsql')->transaction(function () use ($b, $type) {
            $this->notify($b->member_id, null, $type, [
                'member' => $b->member->name,
                'title' => $b->book->title,
                'link' => '/member/borrowings',
                'borrowing_id' => (string) $b->id,
            ]);

            $this->notifyEmployees($type, [
                'member' => $b->member->name,
                'title' => $b->book->title,
                'link' => '/employee/borrowings/history',
                'borrowing_id' => (string) $b->id,
            ]);
        });
    }

    /** True when a notification of $type for $key already exists. */
    private function dedupe(string $type, string $key, string $id): bool
    {
        return DB::connection('pgsql')
            ->table('notifications')
            ->where('type', $type)
            ->whereRaw("payload->>? = ?", [$key, $id])
            ->exists();
    }
}
