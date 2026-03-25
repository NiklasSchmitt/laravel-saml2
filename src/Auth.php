<?php

namespace NiklasSchmitt\Saml2;

use OneLogin\Saml2\Auth as OneLoginAuth;
use OneLogin\Saml2\Error as OneLoginError;
use NiklasSchmitt\Saml2\Events\SignedOut;
use NiklasSchmitt\Saml2\Models\Tenant;

class Auth
{
    protected $base;
    protected $tenant;

    public function __construct(OneLoginAuth $auth, Tenant $tenant)
    {
        $this->base = $auth;
        $this->tenant = $tenant;
    }

    public function isAuthenticated()
    {
        return $this->base->isAuthenticated();
    }

    public function getSaml2User()
    {
        return new Saml2User($this->base, $this->tenant);
    }

    public function getLastMessageId()
    {
        return $this->base->getLastMessageId();
    }

    /**
     * Initiate a saml2 login flow.
     *
     * It will redirect! Before calling this, check if user is
     * authenticated (here in saml2). That would be true when the assertion was received this request.
     *
     * @param string|null $returnTo        the target URL the user should be returned to after login
     * @param array       $parameters      Extra parameters to be added to the GET
     * @param bool        $forceAuthn      When true the AuthNReuqest will set the ForceAuthn='true'
     * @param bool        $isPassive       When true the AuthNReuqest will set the Ispassive='true'
     * @param bool        $stay            True if we want to stay (returns the url string) False to redirect
     * @param bool        $setNameIdPolicy When true the AuthNReuqest will set a nameIdPolicy element
     *
     * @return string|null If $stay is True, it return a string with the SLO URL + LogoutRequest + parameters
     *
     * @throws OneLoginError
     */
    public function login(
        $returnTo = null,
        $parameters = [],
        $forceAuthn = false,
        $isPassive = false,
        $stay = false,
        $setNameIdPolicy = true,
    ) {
        return $this->base->login($returnTo, $parameters, $forceAuthn, $isPassive, $stay, $setNameIdPolicy);
    }

    /**
     * Initiate a saml2 logout flow. It will close session on all other SSO services.
     * You should close local session if applicable.
     *
     * @param string|null $returnTo            the target URL the user should be returned to after logout
     * @param string|null $nameId              the NameID that will be set in the LogoutRequest
     * @param string|null $sessionIndex        the SessionIndex (taken from the SAML Response in the SSO process)
     * @param string|null $nameIdFormat        the NameID Format will be set in the LogoutRequest
     * @param bool        $stay                True if we want to stay (returns the url string) False to redirect
     * @param string|null $nameIdNameQualifier the NameID NameQualifier will be set in the LogoutRequest
     *
     * @return string|null If $stay is True, it return a string with the SLO URL + LogoutRequest + parameters
     *
     * @throws OneLoginError
     */
    public function logout(
        $returnTo = null,
        $nameId = null,
        $sessionIndex = null,
        $nameIdFormat = null,
        $stay = false,
        $nameIdNameQualifier = null,
    ) {
        $auth = $this->base;

        return $auth->logout($returnTo, [], $nameId, $sessionIndex, $stay, $nameIdFormat, $nameIdNameQualifier);
    }

    /**
     * Process the SAML Response sent by the IdP.
     *
     * @return array|null
     *
     * @throws OneLoginError
     * @throws \OneLogin\Saml2\ValidationError
     */
    public function acs()
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
     * @throws OneLoginError
     */
    public function sls($retrieveParametersFromServer = false)
    {
        $this->base->processSLO(false, null, $retrieveParametersFromServer, function () {
            event(new SignedOut());
        });

        return $this->base->getErrors();
    }

    /**
     * Get metadata about the local SP. Use this to configure your Saml2 IdP.
     *
     * @return string
     *
     * @throws OneLoginError
     * @throws \Exception
     * @throws \InvalidArgumentException If metadata is not correctly set
     */
    public function getMetadata()
    {
        $settings = $this->base->getSettings();
        $metadata = $settings->getSPMetadata();
        $errors = $settings->validateMetadata($metadata);

        if (!count($errors)) {
            return $metadata;
        }

        throw new \InvalidArgumentException('Invalid SP metadata: ' . implode(', ', $errors), OneLoginError::METADATA_SP_INVALID);
    }

    /**
     * Get the last error reason from \OneLogin_Saml2_Auth, useful for error debugging.
     *
     * @see \OneLogin_Saml2_Auth::getLastErrorReason()
     *
     * @return string
     */
    public function getLastErrorReason()
    {
        return $this->base->getLastErrorReason();
    }

    public function getBase()
    {
        return $this->base;
    }

    public function setTenant(Tenant $tenant)
    {
        $this->tenant = $tenant;
    }

    public function getTenant()
    {
        return $this->tenant;
    }
}
