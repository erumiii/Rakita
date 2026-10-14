<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('borrowings:mark-overdue')->everyMinute();
