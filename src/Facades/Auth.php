<?php

namespace NiklasSchmitt\Saml2\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Class Saml2Auth
 *
 * @method static \NiklasSchmitt\Saml2\Models\Tenant|null getTenant()
 *
 * @package NiklasSchmitt\Saml2\Facades
 */
class Auth extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'NiklasSchmitt\Saml2\Auth';
    }
}