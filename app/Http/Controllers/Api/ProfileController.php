<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user();
        $profile = $user->pengguna;

        return response()->json([
            'username' => $user->username,
            'role' => $user->role,
            'nama_lengkap' => $profile?->nama_lengkap,
            'jabatan' => $profile?->jabatan,
            'no_hp' => $profile?->no_hp,
            'alamat' => $profile?->alamat,
            'id_perusahaan' => $user->id_perusahaan,
            'profile_photo_url' => $user->profile_photo_url,
        ]);
    }

    public function updateData(Request $request)
    {
        $user = $request->user();
        $profile = $user->pengguna;

        $request->validate([
            'nama_lengkap' => 'required|string|max:255',
            'jabatan' => 'nullable|string|max:100',
            'no_hp' => 'nullable|string|max:20',
            'alamat' => 'nullable|string',
        ]);

        if ($profile) {
            $profile->update([
                'nama_lengkap' => $request->nama_lengkap,
                'jabatan' => $request->jabatan,
                'no_hp' => $request->no_hp,
                'alamat' => $request->alamat,
            ]);
        }

        return response()->json(['message' => 'Data pribadi berhasil diperbarui.']);
    }

    public function updatePassword(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'current_password' => 'required',
            'new_password' => 'required|string|min:6|confirmed',
        ]);

        if (! Hash::check($request->current_password, $user->password)) {
            return response()->json(['message' => 'Kata sandi saat ini tidak sesuai.'], 422);
        }

        if ($request->new_password === $user->username) {
            return response()->json(['message' => 'Kata sandi tidak boleh sama dengan username.'], 422);
        }

        $user->update([
            'password' => Hash::make($request->new_password),
            'change_password' => ($user->change_password ?? 0) + 1,
        ]);

        return response()->json(['message' => 'Kata sandi berhasil diubah.']);
    }
}
