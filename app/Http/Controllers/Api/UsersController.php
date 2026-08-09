<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Akun;
use App\Models\Pengguna;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UsersController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $companyId = $user->id_perusahaan;

        $pengguna = Pengguna::with(['akun', 'perusahaan'])
            ->when($companyId, fn ($q) => $q->where('id_perusahaan', $companyId))
            ->get();

        return response()->json([
            'users' => $pengguna->map(fn ($p) => [
                'id_pengguna' => $p->id_pengguna,
                'id_perusahaan' => $p->id_perusahaan,
                'nama_lengkap' => $p->nama_lengkap,
                'no_hp' => $p->no_hp,
                'alamat' => $p->alamat,
                'jabatan' => $p->jabatan,
                'username' => $p->akun?->username,
                'role' => $p->akun?->role,
                'is_active' => $p->akun?->is_active,
            ]),
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        $companyId = $user->id_perusahaan;

        $request->validate([
            'nama_lengkap' => 'required|string|max:255',
            'no_hp' => 'nullable|string|max:20',
            'jabatan' => 'nullable|string|max:100',
            'username' => 'required|string|max:50|unique:akun,username',
            'password' => 'required|string|min:6',
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

            return response()->json(['message' => 'Pengguna berhasil ditambahkan.'], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json(['message' => 'Gagal menambah pengguna: '.$e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $user = $request->user();
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

            return response()->json(['message' => 'Pengguna berhasil diperbarui.']);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json(['message' => 'Gagal mengubah pengguna: '.$e->getMessage()], 500);
        }
    }

    public function delete(Request $request, $id)
    {
        $user = $request->user();
        $companyId = $user->id_perusahaan;

        $pengguna = Pengguna::when($companyId, fn ($q) => $q->where('id_perusahaan', $companyId))
            ->findOrFail($id);

        if ($pengguna->id_pengguna === $user->pengguna?->id_pengguna) {
            return response()->json(['message' => 'Tidak bisa menghapus akun sendiri.'], 422);
        }

        if ($pengguna->akun && $pengguna->akun->role === 'SUPER ADMIN') {
            return response()->json(['message' => 'Pengguna SUPER ADMIN tidak boleh dihapus.'], 422);
        }

        try {
            DB::beginTransaction();
            if ($pengguna->akun) {
                $pengguna->akun->delete();
            }
            $pengguna->delete();
            DB::commit();

            return response()->json(['message' => 'Pengguna berhasil dihapus.']);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json(['message' => 'Gagal menghapus pengguna: '.$e->getMessage()], 500);
        }
    }
}
