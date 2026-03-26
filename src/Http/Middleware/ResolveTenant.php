<?php

declare(strict_types=1);

namespace NiklasSchmitt\Saml2\Http\Middleware;

use Illuminate\Support\Facades\Log;
use NiklasSchmitt\Saml2\Models\Tenant;
use NiklasSchmitt\Saml2\OneLoginBuilder;
use NiklasSchmitt\Saml2\Repositories\TenantRepository;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ResolveTenant
{
    public function __construct(
        protected TenantRepository $tenants,
        protected OneLoginBuilder $builder
    )
    {
    }

    public function handle($request, \Closure $next): mixed
    {
        $tenant = $this->resolveTenant($request);

        if (! $tenant) {
            throw new NotFoundHttpException();
        }

        if (config('saml2.debug')) {
            Log::debug('[Saml2] Tenant resolved', [
                'uuid' => $tenant->uuid,
                'id' => $tenant->id,
                'key' => $tenant->key
            ]);
        }

        session()->flash('saml2.tenant.uuid', $tenant->uuid);
        $this->builder->bootstrap($tenant);

        return $next($request);
    }

    protected function resolveTenant($request): ?Tenant
    {
        $uuid = $request->route('uuid');

        if (! $uuid) {
            if (config('saml2.debug')) {
                Log::debug('[Saml2] Tenant UUID is not present in the URL so cannot be resolved', [
                    'url' => $request->fullUrl(),
                ]);
            }

            return null;
        }

        $tenant = $this->tenants->findByUUID((string) $uuid);

        if (! $tenant) {
            if (config('saml2.debug')) {
                Log::debug('[Saml2] Tenant doesn\'t exist', [
                    'uuid' => $uuid,
                ]);
            }

            return null;
        }

        if ($tenant->trashed()) {
            if (config('saml2.debug')) {
                Log::debug('[Saml2] Tenant #' . $tenant->id . ' resolved but marked as deleted', [
                    'id' => $tenant->id,
                    'uuid' => $uuid,
                    'deleted_at' => $tenant->deleted_at?->toDateTimeString(),
                ]);
            }

            return null;
        }

        return $tenant;
    }
}
