<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Jetstream\HasProfilePhoto;
use Laravel\Jetstream\HasTeams;
use Laravel\Sanctum\HasApiTokens;

class Akun extends Authenticatable implements MustVerifyEmail
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
        'email',
        'role',
        'password',
        'is_active',
        'change_password',
        'trial_ends_at',
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
        'trial_ends_at' => 'datetime',
        'password' => 'hashed',
        'is_active' => 'boolean',
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
        return $this->email ?: $this->username;
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

    public function isInvestor(): bool
    {
        return $this->role === 'INVESTOR';
    }

    public function investorProject()
    {
        return $this->hasOne(ProjectInvestor::class, 'id_akun', 'id_akun');
    }

    public function companyModule(): string
    {
        if (! $this->pengguna || ! $this->pengguna->perusahaan) {
            return Perusahaan::MODULE_ALL;
        }

        return $this->pengguna->perusahaan->module();
    }

    public function getNamaLengkapAttribute(): string
    {
        return $this->pengguna?->nama_lengkap ?? $this->username;
    }

    public function getIdPerusahaanAttribute(): ?int
    {
        return $this->pengguna?->id_perusahaan;
    }

    public function masterDataCompanyId(): ?int
    {
        if ($this->isSuperAdmin()) {
            return null;
        }

        return $this->id_perusahaan;
    }

    public function hasTrial(): bool
    {
        return $this->trial_ends_at !== null;
    }

    public function isTrialExpired(): bool
    {
        if (! $this->trial_ends_at) {
            return false;
        }

        return now()->greaterThan($this->trial_ends_at);
    }

    public function trialDaysLeft(): int
    {
        if (! $this->trial_ends_at) {
            return 0;
        }
        $days = (int) Carbon::now()->diffInDays($this->trial_ends_at, false);

        return max(0, $days);
    }

    public function isActiveUser(): bool
    {
        return $this->is_active === '1' && ! $this->isTrialExpired();
    }
}
