<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('borrowings:mark-overdue')->everyMinute();
Schedule::command('reservations:expire')->everyMinute();
Schedule::command('notifications:generate')->everyMinute();
