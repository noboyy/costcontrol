<?php

namespace App\Http\Controllers;

use App\Models\Akun;
use App\Models\CostEntry;
use App\Models\IncomeEntry;
use App\Models\Perusahaan;
use App\Models\Project;
use Illuminate\Http\Request;

class SuperAdminController extends Controller
{
    /**
     * Owner Panel — ringkasan internal Sahla Journey Finance.
     * (Bukan lagi panel multi-tenant SaaS.)
     */
    public function stats(Request $request)
    {
        $totalUsers = Akun::count();
        $activeUsers = Akun::where('is_active', '1')->count();
        $inactiveAccounts = Akun::where('is_active', '0')->count();
        $investorCount = Akun::where('role', 'INVESTOR')->count();
        $adminCount = Akun::where('role', 'ADMIN')->count();

        $perusahaan = Perusahaan::first();

        $overview = [
            'total_user' => $totalUsers,
            'aktif' => $activeUsers,
            'nonaktif' => $inactiveAccounts,
            'admin' => $adminCount,
            'investor' => $investorCount,
            'proyek' => Project::count(),
            'perusahaan' => $perusahaan?->nama_perusahaan ?? '—',
        ];

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
                'status' => $a->is_active === '1' ? 'aktif' : 'nonaktif',
                'created_at' => $a->created_at?->format('d M Y'),
            ]);

        $recentCosts = CostEntry::with('project')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(fn ($c) => [
                'id' => $c->id_cost,
                'project' => $c->project?->nama_project ?? '—',
                'nominal' => $c->total,
                'tanggal' => $c->created_at?->format('d M Y'),
            ]);

        $recentIncomes = IncomeEntry::with('project')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(fn ($i) => [
                'id' => $i->id_income,
                'project' => $i->project?->nama_project ?? '—',
                'nominal' => $i->total,
                'tanggal' => $i->created_at?->format('d M Y'),
            ]);

        return view('super-admin.stats', [
            'title' => 'Owner Panel',
            'overview' => $overview,
            'recentUsers' => $recentUsers,
            'recentCosts' => $recentCosts,
            'recentIncomes' => $recentIncomes,
        ]);
    }
}
