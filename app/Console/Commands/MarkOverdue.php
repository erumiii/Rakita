<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MarkOverdue extends Command
{
    protected $signature = 'borrowings:mark-overdue';
    protected $description = 'Mark unreturned borrowings past due as overdue';

    public function handle(): int
    {
        // Default superuser connection bypasses RLS on purpose: a batch job
        // must see all rows; the console context has no DbSession GUCs.
        $count = DB::update("
            UPDATE borrowings SET status = 'overdue', updated_at = now()
            WHERE status = 'borrowed' AND returned_at IS NULL AND due_at < now()
        ");

        $this->info("Marked {$count} overdue.");

        return self::SUCCESS;
    }
}
