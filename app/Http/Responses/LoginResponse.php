<?php

namespace App\Http\Responses;

use Illuminate\Http\Request;
use Laravel\Fortify\Http\Responses\LoginResponse as FortifyLoginResponse;

class LoginResponse extends FortifyLoginResponse
{
    public function toResponse($request)
    {
        $user = $request->user();

        if ($user && $user->isSuperAdmin()) {
            return $request->wantsJson()
                ? response()->json(['two_factor' => false])
                : redirect('/super-admin/stats');
        }

        return parent::toResponse($request);
    }
}
