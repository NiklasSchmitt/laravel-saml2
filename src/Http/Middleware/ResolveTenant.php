<?php

namespace NiklasSchmitt\Saml2\Http\Middleware;

use Illuminate\Support\Facades\Log;
use NiklasSchmitt\Saml2\OneLoginBuilder;
use NiklasSchmitt\Saml2\Repositories\TenantRepository;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class ResolveTenant
 */
class ResolveTenant
{
    /** @var TenantRepository */
    protected $tenants;

    /** @var OneLoginBuilder */
    protected $builder;

    /**
     * ResolveTenant constructor.
     */
    public function __construct(TenantRepository $tenants, OneLoginBuilder $builder)
    {
        $this->tenants = $tenants;
        $this->builder = $builder;
    }

    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @throws NotFoundHttpException
     */
    public function handle($request, \Closure $next)
    {
        if (!$tenant = $this->resolveTenant($request)) {
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
        $this->builder->withTenant($tenant)->bootstrap();

        return $next($request);
    }

    /**
     * Resolve a tenant by a request.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \NiklasSchmitt\Saml2\Models\Tenant|null
     */
    protected function resolveTenant($request)
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

        $tenant = $this->tenants->findByUUID($uuid);

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
                    'deleted_at' => $tenant->deleted_at->toDateTimeString(),
                ]);
            }

            return null;
        }

        return $tenant;
    }
}
