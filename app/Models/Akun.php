<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Jetstream\HasProfilePhoto;
use Laravel\Jetstream\HasTeams;
use Laravel\Sanctum\HasApiTokens;

class Akun extends Authenticatable
{
    use HasApiTokens;
    use HasFactory;
    use HasProfilePhoto;
    use HasTeams;
    use Notifiable;
    use TwoFactorAuthenticatable;

    protected $table = 'akun';
    protected $primaryKey = 'id_akun';

    protected $fillable = [
        'id_pengguna',
        'username',
        'role',
        'password',
        'is_active',
        'change_password',
        'email_verified_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    protected $appends = [
        'profile_photo_url',
    ];

    public function getAuthIdentifierName()
    {
        return 'id_akun';
    }

    public function getEmailForPasswordReset()
    {
        return $this->username;
    }

    public function pengguna()
    {
        return $this->belongsTo(Pengguna::class, 'id_pengguna', 'id_pengguna');
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === 'SUPER ADMIN';
    }

    public function isAdmin(): bool
    {
        return in_array($this->role, ['SUPER ADMIN', 'ADMIN']);
    }

    public function getNamaLengkapAttribute(): string
    {
        return $this->pengguna?->nama_lengkap ?? $this->username;
    }

    public function getIdPerusahaanAttribute(): ?int
    {
        return $this->pengguna?->id_perusahaan;
    }
}
