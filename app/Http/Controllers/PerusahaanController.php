<?php

namespace App\Http\Controllers;

use App\Models\Perusahaan;
use Illuminate\Http\Request;

class PerusahaanController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        // Super admin sees all; admin sees own company only
        if ($user->isSuperAdmin()) {
            $list = Perusahaan::orderBy('nama_perusahaan')->get();
        } else {
            $list = Perusahaan::where('id_perusahaan', $user->id_perusahaan)->get();
        }

        return view('perusahaan.index', [
            'title' => 'Perusahaan',
            'list' => $list,
            'canManageAll' => $user->isSuperAdmin(),
        ]);
    }

    public function store(Request $request)
    {
        if (!auth()->user()->isSuperAdmin()) {
            abort(403);
        }

        $request->validate([
            'nama_perusahaan' => 'required|string|max:255',
            'alamat_lengkap' => 'nullable|string',
            'owner' => 'nullable|string|max:255',
        ]);

        Perusahaan::create($request->only('nama_perusahaan', 'alamat_lengkap', 'owner'));

        return redirect()->route('perusahaan.index')->with('success', 'Perusahaan ditambahkan.');
    }

    public function update(Request $request, $id)
    {
        $user = auth()->user();
        $company = Perusahaan::findOrFail($id);

        if (!$user->isSuperAdmin() && (int) $user->id_perusahaan !== (int) $company->id_perusahaan) {
            abort(403);
        }

        $request->validate([
            'nama_perusahaan' => 'required|string|max:255',
            'alamat_lengkap' => 'nullable|string',
            'owner' => 'nullable|string|max:255',
        ]);

        $company->update($request->only('nama_perusahaan', 'alamat_lengkap', 'owner'));

        return redirect()->route('perusahaan.index')->with('success', 'Perusahaan diperbarui.');
    }

    public function delete($id)
    {
        if (!auth()->user()->isSuperAdmin()) {
            abort(403);
        }

        $company = Perusahaan::findOrFail($id);
        if ($company->projects()->exists() || $company->pengguna()->exists()) {
            return back()->with('error', 'Perusahaan masih punya data. Hapus/pindahkan dulu.');
        }
        $company->delete();

        return redirect()->route('perusahaan.index')->with('success', 'Perusahaan dihapus.');
    }
}
