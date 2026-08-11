<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

class TenantScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        if (Auth::check()) {
            $user = Auth::user();

            // SUPER ADMIN sees all records
            if ($user->isSuperAdmin()) {
                return;
            }

            // Get company ID via factory method, not relation
            $idPengguna = $user->getAttribute('id_pengguna');

            if (! $idPengguna) {
                return;
            }

            // Direct query for company to avoid relation loop
            $company = \App\Models\Pengguna::select('id_pengguna', 'id_perusahaan')->find($idPengguna);

            if ($company?->id_perusahaan) {
                $builder->where($model->getTable().'.id_perusahaan', $company->id_perusahaan);
            }
        }
    }
}
