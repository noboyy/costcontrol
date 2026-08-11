<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $profile = $user->pengguna;

        return view('profile.index', [
            'title' => 'Profil',
            'profile' => $profile,
            'user' => $user,
        ]);
    }

    public function updateData(Request $request)
    {
        $user = auth()->user();
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

        return redirect()->route('profil')->with('success', 'Data pribadi berhasil diperbarui.');
    }

    public function updatePassword(Request $request)
    {
        $user = auth()->user();

        $request->validate([
            'current_password' => 'required',
            'new_password' => 'required|string|min:6|confirmed',
        ]);

        if (! Hash::check($request->current_password, $user->password)) {
            return back()->with('error', 'Kata sandi saat ini tidak sesuai.');
        }

        if ($request->new_password === $user->username) {
            return back()->with('error', 'Kata sandi tidak boleh sama dengan username.');
        }

        $user->update([
            'password' => $request->new_password,
            'change_password' => ($user->change_password ?? 0) + 1,
        ]);

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/')->with('success', 'Kata sandi berhasil diubah. Silakan login kembali.');
    }

    public function photo()
    {
        $user = auth()->user();

        // Try to find user photo
        $path = $this->getProfilePhotoPath($user->id_akun);

        if (! $path) {
            $path = public_path('img/icon/user.svg');
        }

        if (! file_exists($path)) {
            // Return a simple 1x1 transparent PNG as fallback
            return response('', 200, ['Content-Type' => 'image/png']);
        }

        return response()->file($path, [
            'Content-Type' => mime_content_type($path),
            'Cache-Control' => 'private, max-age=86400',
        ]);
    }

    public function updatePhoto(Request $request)
    {
        $user = auth()->user();

        $request->validate([
            'photo' => 'required|image|mimes:jpeg,png,webp|max:2048',
        ]);

        $file = $request->file('photo');

        // Delete old photos
        $dir = storage_path('app/public/profile');
        if (! is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        foreach (glob($dir."/profile_{$user->id_akun}.*") ?: [] as $old) {
            @unlink($old);
        }

        $ext = strtolower($file->getClientOriginalExtension());
        if ($ext === 'jpeg') {
            $ext = 'jpg';
        }

        $filename = "profile_{$user->id_akun}.{$ext}";
        $file->storeAs('profile', $filename, 'public');

        return response()->json([
            'success' => true,
            'message' => 'Foto profil berhasil diperbarui.',
            'photoUrl' => route('profil.photo').'?v='.time(),
        ]);
    }

    private function getProfilePhotoPath(int $userId): ?string
    {
        $dir = storage_path('app/public/profile');
        $pattern = $dir."/profile_{$userId}.*";
        $files = glob($pattern);

        if ($files) {
            $file = reset($files);
            if ($file && is_file($file)) {
                return $file;
            }
        }

        return null;
    }
}
