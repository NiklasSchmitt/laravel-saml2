<?php

declare(strict_types=1);

namespace NiklasSchmitt\Saml2;

use OneLogin\Saml2\Auth as OneLoginAuth;
use OneLogin\Saml2\Error as OneLoginError;
use NiklasSchmitt\Saml2\Events\SignedOut;
use NiklasSchmitt\Saml2\Models\Tenant;

class Auth
{
    public function __construct(
        protected OneLoginAuth $base,
        protected Tenant $tenant
    )
    {
    }

    public function isAuthenticated(): bool
    {
        return $this->base->isAuthenticated();
    }

    public function getSaml2User(): Saml2User
    {
        return new Saml2User($this->base, $this->tenant);
    }

    public function getLastMessageId(): ?string
    {
        return $this->base->getLastMessageId();
    }

    /**
     * Initiate a saml2 login flow.
     *
     * It will redirect! Before calling this, check if user is
     * authenticated (here in saml2). That would be true when the assertion was received this request.
     *
     * @param string|null $returnTo The target URL the user should be returned to after login.
     * @param array $parameters Extra parameters to be added to the GET
     * @param bool $forceAuthn When true the AuthNReuqest will set the ForceAuthn='true'
     * @param bool $isPassive When true the AuthNReuqest will set the Ispassive='true'
     * @param bool $stay True if we want to stay (returns the url string) False to redirect
     * @param bool $setNameIdPolicy When true the AuthNReuqest will set a nameIdPolicy element
     *
     * @return string|null If $stay is True, it return a string with the SLO URL + LogoutRequest + parameters
     *
     * @throws OneLoginError
     */
    public function login(
        ?string $returnTo = null,
        array $parameters = [],
        bool $forceAuthn = false,
        bool $isPassive = false,
        bool $stay = false,
        bool $setNameIdPolicy = true
    ): ?string
    {
        return $this->base->login($returnTo, $parameters, $forceAuthn, $isPassive, $stay, $setNameIdPolicy);
    }

    /**
     * Initiate a saml2 logout flow. It will close session on all other SSO services.
     * You should close local session if applicable.
     *
     * @param string|null $returnTo The target URL the user should be returned to after logout.
     * @param string|null $nameId The NameID that will be set in the LogoutRequest.
     * @param string|null $sessionIndex The SessionIndex (taken from the SAML Response in the SSO process).
     * @param string|null $nameIdFormat The NameID Format will be set in the LogoutRequest.
     * @param bool $stay True if we want to stay (returns the url string) False to redirect
     * @param string|null $nameIdNameQualifier The NameID NameQualifier will be set in the LogoutRequest.
     *
     * @return string|null If $stay is True, it return a string with the SLO URL + LogoutRequest + parameters
     *
     * @throws OneLoginError
     */
    public function logout(
        ?string $returnTo = null,
        ?string $nameId = null,
        ?string $sessionIndex = null,
        ?string $nameIdFormat = null,
        bool $stay = false,
        ?string $nameIdNameQualifier = null
    ): ?string
    {
        return $this->base->logout($returnTo, [], $nameId, $sessionIndex, $stay, $nameIdFormat, $nameIdNameQualifier);
    }

    /**
     * Process the SAML Response sent by the IdP.
     *
     * @return array|null
     *
     * @throws OneLoginError
     * @throws \OneLogin\Saml2\ValidationError
     */
    public function acs(): ?array
    {
        $this->base->processResponse();

        $errors = $this->base->getErrors();

        if (!empty($errors)) {
            return $errors;
        }

        if (!$this->base->isAuthenticated()) {
            return ['error' => 'Could not authenticate'];
        }

        return null;
    }

    /**
     * Process the SAML Logout Response / Logout Request sent by the IdP.
     *
     * Returns an array with errors if it can not logout.
     *
     * @param bool $retrieveParametersFromServer
     *
     * @return array
     *
     * @throws \OneLogin\Saml2\Error
     */
    public function sls(bool $retrieveParametersFromServer = false): array
    {
        $this->base->processSLO(false, null, $retrieveParametersFromServer, function () {
            event(new SignedOut());
        });

        $errors = $this->base->getErrors();

        return $errors;
    }

    /**
     * Get metadata about the local SP. Use this to configure your Saml2 IdP.
     *
     * @return string
     *
     * @throws \OneLogin\Saml2\Error
     * @throws \Exception
     * @throws \InvalidArgumentException If metadata is not correctly set
     */
    public function getMetadata(): string
    {
        $settings = $this->base->getSettings();
        $metadata = $settings->getSPMetadata();
        $errors = $settings->validateMetadata($metadata);

        if (!count($errors)) {
            return $metadata;
        }

        throw new \InvalidArgumentException(
            'Invalid SP metadata: ' . implode(', ', $errors),
            OneLoginError::METADATA_SP_INVALID
        );
    }

    /**
     * Get the last error reason from \OneLogin_Saml2_Auth, useful for error debugging.
     *
     * @see \OneLogin_Saml2_Auth::getLastErrorReason()
     *
     * @return string
     */
    public function getLastErrorReason(): string
    {
        return $this->base->getLastErrorReason();
    }

    public function getBase(): OneLoginAuth
    {
        return $this->base;
    }

    public function setTenant(Tenant $tenant): void
    {
        $this->tenant = $tenant;
    }

    public function getTenant(): Tenant
    {
        return $this->tenant;
    }
}
