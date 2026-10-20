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

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'no_telepon',
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
        ];
    }

    // Relasi
    public function pengaduan()
    {
        return $this->hasMany(Pengaduan::class, 'id_pengguna');
    }

    public function tinjauanAnalis()
    {
        return $this->hasMany(TinjauanAnalis::class, 'id_pengguna');
    }

    public function tindakLanjutKasus()
    {
        return $this->hasMany(TindakLanjutKasus::class, 'id_pengguna');
    }

    // Helper role
    public function isMasyarakat(): bool
    {
        return $this->role === 'Masyarakat';
    }

    public function isAnalis(): bool
    {
        return $this->role === 'Analis';
    }

    public function isKepalaBidang(): bool
    {
        return $this->role === 'Kepala Bidang';
    }
}
