<?php

namespace App\Services;

use App\Models\Akun;

class TenantResolver
{
    public const BLOCKED_COMPANY_ID = 0;

    /**
     * Company ID for the current tenant scope.
     *
     * null  = no filter (super admin or unauthenticated context e.g. console)
     * int   = restrict to this company
     * 0     = authenticated but companyless account: matches nothing
     */
    public function companyId(): ?int
    {
        $user = auth()->user();

        if (! $user instanceof Akun) {
            return null;
        }

        if ($user->isSuperAdmin()) {
            return null;
        }

        // Direct column access, avoid accessor/relation
        $idPengguna = $user->getAttribute('id_pengguna');
        
        if (! $idPengguna) {
            return self::BLOCKED_COMPANY_ID;
        }

        return \App\Models\Pengguna::find($idPengguna)?->id_perusahaan ?? self::BLOCKED_COMPANY_ID;
    }
}
