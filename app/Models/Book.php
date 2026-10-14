<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Book extends Model
{
    use SoftDeletes;

    protected $fillable = ['title', 'author', 'publisher', 'page', 'isbn'];

    public function activeBorrowing(): HasOne
    {
        return $this->hasOne(Borrowing::class)->whereNull('returned_at');
    }

    public function scopeSearch($query, ?string $q)
    {
        return $q ? $query->where(fn ($w) => $w
            ->where('title', 'ilike', "%{$q}%")
            ->orWhere('author', 'ilike', "%{$q}%")
            ->orWhere('isbn', 'ilike', "%{$q}%")) : $query;
    }

    public function scopeStatus($query, ?string $status)
    {
        return $status ? $query->where('status', $status) : $query;
    }
}
