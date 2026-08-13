<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Project;
use App\Services\TenantResolver;
use Illuminate\Database\Eloquent\Builder;

trait ResolvesTenant
{
    /**
     * Current tenant company id.
     *
     * null = super admin (no filter). int = restrict. 0 = blocked (matches nothing).
     */
    protected function companyId(): ?int
    {
        return app(TenantResolver::class)->companyId();
    }

    /**
     * Apply tenant filter to a query builder.
     *
     * Uses strict null check: only super admin (null) skips the filter.
     * A companyless account resolves to 0 and therefore matches nothing.
     */
    protected function scopeToTenant(Builder $query, ?int $companyId = null, string $table = null): Builder
    {
        $companyId ??= $this->companyId();

        if ($companyId === null) {
            return $query;
        }

        $column = $table ? $table.'.id_perusahaan' : 'id_perusahaan';

        return $query->where($column, $companyId);
    }

    /**
     * Fetch a project owned by the current tenant, or 404.
     */
    protected function tenantProject($id, ?int $companyId = null): Project
    {
        $companyId ??= $this->companyId();

        return Project::query()
            ->where('id_project', $id)
            ->when($companyId !== null, fn ($q) => $q->where('id_perusahaan', $companyId))
            ->firstOrFail();
    }
}
