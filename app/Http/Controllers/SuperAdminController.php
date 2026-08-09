<?php

namespace App\Http\Controllers;

use App\Models\Akun;
use App\Models\Asset;
use App\Models\AssetMaintenance;
use App\Models\CostCategory;
use App\Models\CostEntry;
use App\Models\CostType;
use App\Models\DailyClose;
use App\Models\FixedCost;
use App\Models\IncomeCategory;
use App\Models\IncomeEntry;
use App\Models\IncomeType;
use App\Models\Pengguna;
use App\Models\Perusahaan;
use App\Models\Project;
use App\Models\ProjectAdmin;
use App\Models\ProjectCostPlan;
use App\Models\ProjectIncomePlan;
use App\Models\ProjectInvestor;
use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SuperAdminController extends Controller
{
    public function stats(Request $request)
    {
        $totalUsers = Akun::count();
        $inactiveAccounts = Akun::where('is_active', '0')->count();
        $totalCompanies = Perusahaan::count();

        $akunWithPengguna = Akun::with('pengguna')->get();
        $activeUsers = $akunWithPengguna->filter(fn ($a) => $a->isActiveUser())->count();
        $expiredTrial = $akunWithPengguna->filter(fn ($a) => $a->isTrialExpired())->count();
        $inTrial = $akunWithPengguna->filter(fn ($a) => $a->hasTrial() && ! $a->isTrialExpired())->count();

        $byPlan = [
            'total' => $totalUsers,
            'aktif' => $activeUsers,
            'trial_berjalan' => $inTrial,
            'trial_habis' => $expiredTrial,
            'nonaktif' => $inactiveAccounts,
            'perusahaan' => $totalCompanies,
            'investor' => Akun::where('role', 'INVESTOR')->count(),
            'proyek' => Project::count(),
        ];

        $perCompany = Perusahaan::withCount(['pengguna as pengguna_count'])
            ->withCount(['projects as project_count'])
            ->get()
            ->map(function ($p) use ($akunWithPengguna) {
                $active = $akunWithPengguna
                    ->filter(fn ($a) => $a->pengguna && $a->pengguna->id_perusahaan === $p->id_perusahaan)
                    ->filter(fn ($a) => $a->isActiveUser())
                    ->count();

                $investors = ProjectInvestor::whereHas('akun.pengguna', fn ($q) => $q->where('id_perusahaan', $p->id_perusahaan))
                    ->count();

                $adminAkun = Akun::where('role', 'ADMIN')
                    ->whereHas('pengguna', fn ($q) => $q->where('id_perusahaan', $p->id_perusahaan))
                    ->orderBy('created_at')
                    ->first();

                return [
                    'id' => $p->id_perusahaan,
                    'nama' => $p->nama_perusahaan,
                    'owner' => $p->owner,
                    'pengguna' => $p->pengguna_count,
                    'aktif' => $active,
                    'proyek' => $p->project_count ?? 0,
                    'investor' => $investors,
                    'id_akun' => $adminAkun?->id_akun,
                ];
            })
            ->sortByDesc('aktif')
            ->values();

        $investors = Akun::with('pengguna.perusahaan')
            ->where('role', 'INVESTOR')
            ->orderBy('created_at', 'desc')
            ->limit(15)
            ->get()
            ->map(fn ($a) => [
                'id' => $a->id_akun,
                'nama' => $a->pengguna?->nama_lengkap ?? $a->username,
                'username' => $a->username,
                'perusahaan' => $a->pengguna?->perusahaan?->nama_perusahaan ?? '—',
                'is_active' => $a->is_active,
                'status' => $a->is_active === '1' ? 'aktif' : 'nonaktif',
                'created_at' => $a->created_at?->format('d M Y'),
            ]);

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
            'title' => 'Dashboard Super Admin',
            'byPlan' => $byPlan,
            'perCompany' => $perCompany,
            'recentUsers' => $recentUsers,
            'investors' => $investors,
        ]);
    }

    public function extendTrial(Request $request, $id)
    {
        $request->validate([
            'days' => 'required|integer|min:1|max:3650',
        ]);

        $akun = Akun::findOrFail($id);

        if ($akun->isSuperAdmin()) {
            return back()->with('error', 'Akun Super Admin tidak memiliki trial.');
        }

        $base = $akun->trial_ends_at && $akun->trial_ends_at->isFuture()
            ? $akun->trial_ends_at
            : now();

        $akun->trial_ends_at = $base->copy()->addDays((int) $request->days);
        $akun->save();

        return back()->with('success', "Trial diperpanjang {$request->days} hari untuk {$akun->username}.");
    }

    public function deleteUser(Request $request, $id)
    {
        $akun = Akun::findOrFail($id);

        if ($akun->isSuperAdmin() || $akun->id_akun === auth()->id()) {
            return back()->with('error', 'Tidak dapat menghapus akun Super Admin / akun sendiri.');
        }

        DB::transaction(function () use ($akun) {
            ProjectAdmin::where('id_pengguna', $akun->id_pengguna)->delete();
            ProjectInvestor::where('id_akun', $akun->id_akun)->delete();

            $akun->delete();

            $remaining = Akun::where('id_pengguna', $akun->id_pengguna)->exists();
            if (! $remaining) {
                Pengguna::where('id_pengguna', $akun->id_pengguna)->delete();
            }
        });

        return back()->with('success', 'User berhasil dihapus.');
    }

    public function deleteTenant(Request $request, $id)
    {
        $perusahaan = Perusahaan::findOrFail($id);

        $superAdminCompanyIds = Akun::where('role', 'SUPER ADMIN')
            ->with('pengguna')
            ->get()
            ->pluck('pengguna.id_perusahaan')
            ->filter()
            ->unique();

        if ($superAdminCompanyIds->contains($perusahaan->id_perusahaan)) {
            return back()->with('error', 'Tidak dapat menghapus perusahaan penampung akun Super Admin.');
        }

        DB::transaction(function () use ($perusahaan) {
            $projectIds = Project::where('id_perusahaan', $perusahaan->id_perusahaan)->pluck('id_project');

            if ($projectIds->isNotEmpty()) {
                ProjectInvestor::whereIn('id_project', $projectIds)->delete();
                ProjectAdmin::whereIn('id_project', $projectIds)->delete();
                ProjectCostPlan::whereIn('id_project', $projectIds)->delete();
                ProjectIncomePlan::whereIn('id_project', $projectIds)->delete();
                DailyClose::whereIn('id_project', $projectIds)->delete();
                FixedCost::whereIn('id_project', $projectIds)->delete();
                CostEntry::whereIn('id_project', $projectIds)->delete();
                IncomeEntry::whereIn('id_project', $projectIds)->delete();
                Project::whereIn('id_project', $projectIds)->delete();
            }

            $assetIds = Asset::where('id_perusahaan', $perusahaan->id_perusahaan)->pluck('id_asset');
            if ($assetIds->isNotEmpty()) {
                AssetMaintenance::whereIn('id_asset', $assetIds)->delete();
                Asset::whereIn('id_asset', $assetIds)->forceDelete();
            }

            $penggunaIds = Pengguna::where('id_perusahaan', $perusahaan->id_perusahaan)->pluck('id_pengguna');
            if ($penggunaIds->isNotEmpty()) {
                ProjectAdmin::whereIn('id_pengguna', $penggunaIds)->delete();
                Akun::whereIn('id_pengguna', $penggunaIds)->delete();
                Pengguna::whereIn('id_pengguna', $penggunaIds)->delete();
            }

            CostCategory::where('id_perusahaan', $perusahaan->id_perusahaan)->delete();
            CostType::where('id_perusahaan', $perusahaan->id_perusahaan)->delete();
            IncomeCategory::where('id_perusahaan', $perusahaan->id_perusahaan)->delete();
            IncomeType::where('id_perusahaan', $perusahaan->id_perusahaan)->delete();
            Unit::where('id_perusahaan', $perusahaan->id_perusahaan)->delete();

            $perusahaan->delete();
        });

        return back()->with('success', 'Tenant beserta seluruh datanya berhasil dihapus.');
    }
}
