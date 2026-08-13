<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AkunResource;
use App\Models\Akun;
use App\Models\ProjectInvestor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|string',
            'password' => 'required|string',
        ]);

        $account = trim($request->email);

        $user = Akun::where('email', $account)
            ->orWhere('username', $account)
            ->first();

        // BUG-03: cek is_active sebelum password agar tidak bocor info akun valid
        if (! $user || ! $user->is_active) {
            return response()->json([
                'message' => 'Email atau kata sandi salah.',
            ], 401);
        }

        if (! Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Email atau kata sandi salah.',
            ], 401);
        }

        if ($user->isTrialExpired()) {
            return response()->json([
                'message' => 'Masa trial Anda telah berakhir. Silakan hubungi administrator untuk perpanjang.',
            ], 403);
        }

        // BUG-06: hapus token lama agar tidak menumpuk di DB
        $user->tokens()->where('name', 'api-token')->delete();
        $token = $user->createToken('api-token')->plainTextToken;

        $extra = [];
        if ($user->isInvestor()) {
            $relation = ProjectInvestor::where('id_akun', $user->id_akun)->first();
            $extra['project_id'] = $relation?->id_project;
        }

        return response()->json(array_merge([
            'token' => $token,
            'user' => new AkunResource($user),
        ], $extra));
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Berhasil logout.',
        ]);
    }

    public function me(Request $request)
    {
        return new AkunResource($request->user());
    }
}
