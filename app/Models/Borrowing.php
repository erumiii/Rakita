<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Borrowing extends Model
{
    protected $fillable = ['book_id', 'member_id', 'processed_by', 'borrowed_at', 'borrow_period_days', 'due_at', 'returned_at', 'status'];

    protected $casts = [
        'borrowed_at' => 'datetime',
        'due_at' => 'datetime',
        'returned_at' => 'datetime',
    ];

    public function book()
    {
        return $this->belongsTo(Book::class);
    }

    public function member()
    {
        return $this->belongsTo(User::class, 'member_id');
    }

    public function processor()
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', ['borrowed', 'overdue'])->whereNull('returned_at');
    }
}
