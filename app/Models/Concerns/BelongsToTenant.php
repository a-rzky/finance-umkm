<?php

namespace App\Models\Concerns;

use App\Models\Scopes\TenantScope;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use RuntimeException;

/**
 * Mengikat model ke satu tenant: setiap query otomatis tersaring dan
 * tenant_id diisi dari user yang login, tidak pernah dari input request.
 */
trait BelongsToTenant
{
    public static function bootBelongsToTenant(): void
    {
        static::addGlobalScope(new TenantScope);

        static::creating(function (self $model): void {
            if ($model->tenant_id !== null) {
                return;
            }

            $tenantId = auth()->user()?->tenant_id;

            if ($tenantId === null) {
                throw new RuntimeException(
                    sprintf('Tidak bisa membuat %s tanpa tenant aktif.', static::class)
                );
            }

            $model->tenant_id = $tenantId;
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
