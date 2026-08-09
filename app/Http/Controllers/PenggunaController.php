<?php

namespace App\Http\Controllers;

use App\Models\Akun;
use App\Models\Pengguna;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class PenggunaController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $companyId = $user->id_perusahaan;

        $pengguna = Pengguna::with(['akun', 'perusahaan'])
            ->when($companyId, function ($q) use ($companyId) {
                $q->where('id_perusahaan', $companyId);
            })
            ->get();

        return view('pengguna.index', [
            'title' => 'Pengguna',
            'pengguna' => $pengguna,
        ]);
    }

    public function store(Request $request)
    {
        $user = auth()->user();
        $companyId = $user->id_perusahaan;

        $request->validate([
            'nama_lengkap' => 'required|string|max:255',
            'no_hp' => 'nullable|string|max:20',
            'jabatan' => 'nullable|string|max:100',
            'username' => 'required|string|max:50|unique:akun,username',
            'password' => 'required|string|min:6|confirmed',
        ]);

        try {
            DB::beginTransaction();

            $pengguna = Pengguna::create([
                'id_perusahaan' => $companyId,
                'nama_lengkap' => $request->nama_lengkap,
                'no_hp' => $request->no_hp,
                'jabatan' => $request->jabatan,
            ]);

            Akun::create([
                'id_pengguna' => $pengguna->id_pengguna,
                'username' => $request->username,
                'role' => 'ADMIN',
                'password' => Hash::make($request->password),
                'is_active' => '1',
                'change_password' => 1,
            ]);

            DB::commit();

            return redirect()->route('pengguna.index')->with('success', 'Pengguna berhasil ditambahkan.');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->withInput()->with('error', 'Gagal menambah pengguna: '.$e->getMessage());
        }
    }

    public function update(Request $request, $id)
    {
        $user = auth()->user();
        $companyId = $user->id_perusahaan;

        $pengguna = Pengguna::when($companyId, fn ($q) => $q->where('id_perusahaan', $companyId))
            ->findOrFail($id);

        $request->validate([
            'nama_lengkap' => 'required|string|max:255',
            'no_hp' => 'nullable|string|max:20',
            'jabatan' => 'nullable|string|max:100',
            'username' => 'nullable|string|max:50|unique:akun,username,'.$pengguna->akun?->id_akun.',id_akun',
            'password' => 'nullable|string|min:6',
            'is_active' => 'nullable|in:0,1',
        ]);

        try {
            DB::beginTransaction();

            $pengguna->update([
                'nama_lengkap' => $request->nama_lengkap,
                'no_hp' => $request->no_hp,
                'jabatan' => $request->jabatan,
            ]);

            if ($pengguna->akun) {
                $updateData = [];

                if ($request->filled('username')) {
                    $updateData['username'] = $request->username;
                }

                if ($request->has('is_active')) {
                    $updateData['is_active'] = $request->is_active;
                }

                if ($request->password) {
                    $updateData['password'] = Hash::make($request->password);
                    $updateData['change_password'] = ($pengguna->akun->change_password ?? 0) + 1;
                }

                if (! empty($updateData)) {
                    $pengguna->akun->update($updateData);
                }
            }

            DB::commit();

            return redirect()->route('pengguna.index')->with('success', 'Pengguna berhasil diperbarui.');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->withInput()->with('error', 'Gagal mengubah pengguna: '.$e->getMessage());
        }
    }

    public function delete($id)
    {
        $user = auth()->user();
        $companyId = $user->id_perusahaan;

        $pengguna = Pengguna::when($companyId, fn ($q) => $q->where('id_perusahaan', $companyId))
            ->findOrFail($id);

        // Cannot delete self
        if ($pengguna->id_pengguna === $user->pengguna?->id_pengguna) {
            return back()->with('error', 'Tidak bisa menghapus akun sendiri.');
        }

        // Cannot delete SUPER ADMIN
        if ($pengguna->akun && $pengguna->akun->role === 'SUPER ADMIN') {
            return back()->with('error', 'Pengguna SUPER ADMIN tidak boleh dihapus.');
        }

        try {
            DB::beginTransaction();

            if ($pengguna->akun) {
                $pengguna->akun->delete();
            }
            $pengguna->delete();

            DB::commit();

            return redirect()->route('pengguna.index')->with('success', 'Pengguna berhasil dihapus.');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('error', 'Gagal menghapus pengguna: '.$e->getMessage());
        }
    }
}
