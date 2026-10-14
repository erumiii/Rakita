<?php

use Illuminate\Support\Carbon;

if (! function_exists('format_dt')) {
    function format_dt(?Carbon $dt): string
    {
        return $dt ? $dt->format('d/m/Y H:i') : '—';
    }
}

if (! function_exists('borrow_reminder')) {
    /** "2 hari lagi" | "Hari ini" | "Terlambat 1 hari" */
    function borrow_reminder(\Illuminate\Support\Carbon $due): string
    {
        $diffDays = now()->startOfDay()->diffInDays($due->startOfDay(), false);
        return match (true) {
            $diffDays < 0 => 'Terlambat '.abs($diffDays).' hari',
            $diffDays == 0 => 'Hari ini',
            default => $diffDays.' hari lagi',
        };
    }
}
