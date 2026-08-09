<?php

namespace App\Http\Controllers;

use App\Services\MasterDataModuleService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ModuleController extends Controller
{
    public function download(Request $request)
    {
        $user = auth()->user();

        if ($user->isSuperAdmin()) {
            return back()->with('error', 'Super Admin mengelola modul global secara langsung.');
        }

        $companyId = $user->id_perusahaan;

        if (! $companyId) {
            return back()->with('error', 'Akun tidak terikat ke perusahaan manapun.');
        }

        $request->validate([
            'modules' => 'required|array|min:1',
            'modules.*' => 'in:'.implode(',', array_keys(MasterDataModuleService::MODULES)),
            'mode' => 'nullable|in:'.implode(',', MasterDataModuleService::MODES),
        ], [
            'modules.required' => 'Pilih minimal satu modul untuk diimport.',
        ]);

        $stats = DB::transaction(function () use ($companyId, $request) {
            return app(MasterDataModuleService::class)
                ->copyModulesToCompany($companyId, $request->input('modules'), $request->input('mode', 'add'));
        });

        $summary = "Data master berhasil diimport. {$stats['added']} baru ditambahkan, {$stats['existing']} sudah ada.";
        if ($stats['updated'] > 0) {
            $summary .= " {$stats['updated']} diperbarui.";
        }
        if ($stats['deleted'] > 0) {
            $summary .= " {$stats['deleted']} dihapus karena tidak ada di modul.";
        }

        return back()->with('success', $summary);
    }
}