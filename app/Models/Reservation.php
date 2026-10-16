<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reservation extends Model
{
    use HasFactory;

    // Runtime queries run as library_app so RLS is live (DbSession sets context here).
    protected $connection = 'pgsql_app';

    protected $fillable = ['book_id', 'member_id', 'status', 'expires_at'];

    protected function casts(): array
    {
        return ['expires_at' => 'datetime'];
    }

    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(User::class, 'member_id');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /** A live pickup offer: the copy is held for this member until expires_at. */
    public function scopeOffered($query)
    {
        return $query->pending()->where('expires_at', '>', now());
    }
}
