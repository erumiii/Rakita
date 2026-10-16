<?php

namespace App\Console\Commands;

use App\Services\NotificationService;
use Illuminate\Console\Command;

class GenerateNotifications extends Command
{
    protected $signature = 'notifications:generate';
    protected $description = 'Generate due/overdue/pickup notifications';

    public function handle(NotificationService $service): int
    {
        $service->dueSoon();
        $service->overdue();
        $service->pickupReady();

        $this->info('Notifications generated.');

        return self::SUCCESS;
    }
}
