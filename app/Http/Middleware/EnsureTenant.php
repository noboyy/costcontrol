<?php

namespace App\Http\Middleware;

use App\Services\TenantResolver;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenant
{
    public function __construct(private TenantResolver $tenants)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $companyId = $this->tenants->companyId();

        // null = super admin (no filter) -> allowed.
        // 0 (BLOCKED_COMPANY_ID) = authenticated but companyless -> deny.
        if ($companyId === TenantResolver::BLOCKED_COMPANY_ID) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Akun Anda tidak terhubung ke perusahaan. Silakan hubungi administrator.',
                ], 403);
            }

            auth()->logout();

            return redirect('/login')
                ->with('error', 'Akun Anda tidak terhubung ke perusahaan. Silakan hubungi administrator.');
        }

        return $next($request);
    }
}
