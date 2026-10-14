<?php

use Illuminate\Support\Carbon;

if (! function_exists('format_dt')) {
    function format_dt(?Carbon $dt): string
    {
        return $dt ? $dt->format('d/m/Y H:i') : '—';
    }
}

if (! function_exists('borrow_reminder')) {
    /** "2 days left" | "Today" | "1 day overdue" */
    function borrow_reminder(\Illuminate\Support\Carbon $due): string
    {
        $diffDays = now()->startOfDay()->diffInDays($due->startOfDay(), false);
        return match (true) {
            $diffDays < 0 => 'Overdue by '.abs($diffDays).' day'.(abs($diffDays) > 1 ? 's' : ''),
            $diffDays == 0 => 'Due today',
            default => $diffDays.' day'.($diffDays > 1 ? 's' : '').' left',
        };
    }
}
