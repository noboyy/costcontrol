<?php

namespace App\Actions\Fortify;

use App\Models\Akun;
use App\Models\Pengguna;
use App\Models\Perusahaan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    public const TRIAL_DAYS = 14;

    /**
     * Validate and create a newly registered user (14-day trial).
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): Akun
    {
        $input['email'] = strtolower(trim($input['email'] ?? ''));

        Validator::make($input, [
            'nama_lengkap' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:akun'],
            'password' => $this->passwordRules(),
            'nama_perusahaan' => ['required', 'string', 'max:255'],
            'no_hp' => ['nullable', 'string', 'max:20'],
            'jabatan' => ['nullable', 'string', 'max:100'],
        ])->validate();

        return DB::transaction(function () use ($input) {
            $perusahaan = Perusahaan::create([
                'nama_perusahaan' => $input['nama_perusahaan'],
                'alamat_lengkap' => $input['alamat_perusahaan'] ?? null,
                'owner' => $input['email'],
            ]);

            $pengguna = Pengguna::create([
                'id_perusahaan' => $perusahaan->id_perusahaan,
                'nama_lengkap' => $input['nama_lengkap'],
                'no_hp' => $input['no_hp'] ?? null,
                'jabatan' => $input['jabatan'] ?? null,
            ]);

            $akun = Akun::create([
                'id_pengguna' => $pengguna->id_pengguna,
                'username' => $input['email'],
                'email' => $input['email'],
                'role' => 'ADMIN',
                'password' => Hash::make($input['password']),
                'is_active' => '1',
                'change_password' => 0,
                'trial_ends_at' => now()->addDays(self::TRIAL_DAYS),
            ]);

            return $akun;
        });
    }
}
