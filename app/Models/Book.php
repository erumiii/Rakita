<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Book extends Model
{
    use HasFactory, SoftDeletes;

    // Runtime queries run as library_app so RLS is live (DbSession sets context here).
    protected $connection = 'pgsql_app';

    protected $fillable = ['title', 'author', 'publisher', 'page', 'isbn', 'status'];

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
