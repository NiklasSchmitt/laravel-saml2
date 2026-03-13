<?php

declare(strict_types=1);

namespace Slides\Saml2;

use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\URL;
use OneLogin\Saml2\Auth as OneLoginAuth;
use OneLogin\Saml2\Utils as OneLoginUtils;
use Slides\Saml2\Models\Tenant;

class OneLoginBuilder
{
    public function __construct(protected Container $app)
    {
    }

    public function bootstrap(Tenant $tenant): void
    {
        if ($this->app['config']->get('saml2.proxyVars', false)) {
            OneLoginUtils::setProxyVars(true);
        }

        if ($this->app['config']->get('saml2.serverPort')) {
            OneLoginUtils::setSelfPort($this->app['config']->get('saml2.serverPort'));
        }

        $this->app->instance(Tenant::class, $tenant);
        $this->app->instance('saml2.tenant', $tenant);
        $this->app->forgetInstance('OneLogin_Saml2_Auth');
        $this->app->forgetInstance(Auth::class);

        $this->app->bind('OneLogin_Saml2_Auth', fn (Container $app): OneLoginAuth => $this->makeOneLoginAuth($app, $tenant));
        $this->app->bind(Auth::class, fn (Container $app): Auth => new Auth($app->make('OneLogin_Saml2_Auth'), $tenant));
    }

    protected function makeOneLoginAuth(Container $app, Tenant $tenant): OneLoginAuth
    {
        $config = $app['config']['saml2'];

        $this->setConfigDefaultValues($config, $tenant);

        $config['idp'] = [
            'entityId' => $tenant->idp_entity_id,
            'singleSignOnService' => ['url' => $tenant->idp_login_url],
            'singleLogoutService' => ['url' => $tenant->idp_logout_url],
            'x509cert' => $tenant->idp_x509_cert,
        ];

        $config['sp']['NameIDFormat'] = $this->resolveNameIdFormatPrefix($tenant->name_id_format);

        return new OneLoginAuth($config);
    }

    protected function setConfigDefaultValues(array &$config, Tenant $tenant): void
    {
        foreach ($this->configDefaultValues($tenant) as $key => $default) {
            if (! Arr::get($config, $key)) {
                Arr::set($config, $key, $default);
            }
        }
    }

    protected function configDefaultValues(Tenant $tenant): array
    {
        return [
            'sp.entityId' => URL::route('saml.metadata', ['uuid' => $tenant->uuid]),
            'sp.assertionConsumerService.url' => URL::route('saml.acs', ['uuid' => $tenant->uuid]),
            'sp.singleLogoutService.url' => URL::route('saml.sls', ['uuid' => $tenant->uuid]),
        ];
    }

    protected function resolveNameIdFormatPrefix(string $format): string
    {
        switch ($format) {
            case 'emailAddress':
            case 'X509SubjectName':
            case 'WindowsDomainQualifiedName':
            case 'unspecified':
                return 'urn:oasis:names:tc:SAML:1.1:nameid-format:' . $format;
            default:
                return 'urn:oasis:names:tc:SAML:2.0:nameid-format:' . $format;
        }
    }
}