<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    // Deliberately NOT pinned to pgsql_app: auth (SetDbSession pre-context,
    // login lookup) runs before GUC session vars exist, so it must use the
    // superuser default connection. Post-context reads (MemberController,
    // RLS-scoped queries) must opt in explicitly with User::on('pgsql_app')
    // so row-level security applies. Books/Borrowing run post-context only,
    // hence their static $connection.

    protected $fillable = [
        'name',
        'email',
        'username',
        'password',
        'role',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function borrowings()
    {
        return $this->hasMany(Borrowing::class, 'member_id');
    }

    public function processedBorrowings()
    {
        return $this->hasMany(Borrowing::class, 'processed_by');
    }
}
