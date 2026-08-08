<?php

namespace App\Http\Controllers;

use App\Models\Akun;
use App\Models\Pengguna;
use App\Models\Perusahaan;
use Illuminate\Http\Request;

class SuperAdminController extends Controller
{
    public function stats(Request $request)
    {
        $totalUsers = Akun::count();
        $activeUsers = Akun::with('pengguna')->get()->filter(fn ($a) => $a->isActiveUser())->count();
        $expiredTrial = Akun::with('pengguna')->get()->filter(fn ($a) => $a->isTrialExpired())->count();
        $inTrial = Akun::with('pengguna')->get()->filter(fn ($a) => $a->hasTrial() && ! $a->isTrialExpired())->count();
        $inactiveAccounts = Akun::where('is_active', '0')->count();
        $totalCompanies = Perusahaan::count();

        $byPlan = [
            'total' => $totalUsers,
            'aktif' => $activeUsers,
            'trial_berjalan' => $inTrial,
            'trial_habis' => $expiredTrial,
            'nonaktif' => $inactiveAccounts,
            'perusahaan' => $totalCompanies,
        ];

        $perCompany = Perusahaan::withCount(['pengguna as pengguna_count'])->get()->map(function ($p) {
            $active = Akun::with('pengguna')
                ->whereHas('pengguna', fn ($q) => $q->where('id_perusahaan', $p->id_perusahaan))
                ->get()
                ->filter(fn ($a) => $a->isActiveUser())
                ->count();

            return [
                'id' => $p->id_perusahaan,
                'nama' => $p->nama_perusahaan,
                'owner' => $p->owner,
                'pengguna' => $p->pengguna_count,
                'aktif' => $active,
            ];
        });

        $recentUsers = Akun::with('pengguna')
            ->orderBy('created_at', 'desc')
            ->limit(15)
            ->get()
            ->map(fn ($a) => [
                'id' => $a->id_akun,
                'nama' => $a->nama_lengkap,
                'email' => $a->email,
                'role' => $a->role,
                'is_active' => $a->is_active,
                'trial_ends_at' => $a->trial_ends_at?->format('d M Y'),
                'status' => $a->is_active !== '1' ? 'nonaktif' : ($a->isTrialExpired() ? 'trial habis' : 'aktif'),
                'created_at' => $a->created_at?->format('d M Y'),
            ]);

        return view('super-admin.stats', [
            'title' => 'Statistik Pengguna',
            'byPlan' => $byPlan,
            'perCompany' => $perCompany,
            'recentUsers' => $recentUsers,
        ]);
    }
}