<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    // Runtime queries run as library_app so RLS is live (DbSession sets context here).
    protected $connection = 'pgsql_app';

    public const UPDATED_AT = null;

    protected $fillable = ['user_id', 'recipient_role', 'type', 'payload', 'read_at'];

    protected function casts(): array
    {
        return ['payload' => 'array', 'read_at' => 'datetime'];
    }

    public function scopeUnread(Builder $query)
    {
        return $query->whereNull('read_at');
    }

    /** Human-readable line, phrased per recipient role. */
    public function label(): string
    {
        $p = $this->payload;
        $forEmployee = $this->recipient_role === 'employee';

        return match ($this->type) {
            'reservation.created' => $forEmployee
                ? ($p['member'] ?? '').' reserved "'.($p['title'] ?? '').'"'
                : "Your reservation for \"".($p['title'] ?? '')."\" is queued",
            'member.registered' => ($p['name'] ?? '').' just registered',
            'borrowing.due_soon' => $forEmployee
                ? 'Due soon: "'.($p['title'] ?? '').'" borrowed by '.($p['member'] ?? '')
                : "\"".($p['title'] ?? '')."\" is due soon",
            'borrowing.overdue' => $forEmployee
                ? 'Overdue: "'.($p['title'] ?? '').'" borrowed by '.($p['member'] ?? '')
                : "\"".($p['title'] ?? '')."\" is overdue",
            'book.added' => 'New in collection: "'.($p['title'] ?? '').'"',
            'reservation.pickup' => 'Pickup window open: "'.($p['title'] ?? '').'" — pick up within 1 day',
            default => $this->type,
        };
    }
}
