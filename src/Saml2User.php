<?php

declare(strict_types=1);

namespace NiklasSchmitt\Saml2;

use OneLogin\Saml2\Auth as OneLoginAuth;
use NiklasSchmitt\Saml2\Models\Tenant;

class Saml2User
{
    protected array $parsedAttributes = [];

    public function __construct(
        protected OneLoginAuth $auth,
        protected Tenant $tenant
    )
    {
    }

    public function getUserId(): ?string
    {
        return $this->auth->getNameId();
    }

    public function getAttributes(): array
    {
        return $this->auth->getAttributes();
    }

    /**
     * Returns the requested SAML attribute
     *
     * @param string $name The requested attribute of the user.
     *
     * @return array|null Requested SAML attribute ($name).
     */
    public function getAttribute(string $name): ?array
    {
        return $this->auth->getAttribute($name);
    }

    public function getAttributesWithFriendlyName(): array
    {
        return $this->auth->getAttributesWithFriendlyName();
    }

    public function getRawSamlAssertion(): ?string
    {
        return app('request')->input('SAMLResponse');
    }

    public function getIntendedUrl(): ?string
    {
        $relayState = app('request')->input('RelayState');

        $url = app('Illuminate\Contracts\Routing\UrlGenerator');

        if (! is_string($relayState) || $relayState === '') {
            return null;
        }

        if ($url->full() === $relayState) {
            return null;
        }

        $relayHost = parse_url($relayState, PHP_URL_HOST);
        $currentHost = parse_url($url->full(), PHP_URL_HOST);

        if ($relayHost !== null && $currentHost !== null && $relayHost !== $currentHost) {
            return null;
        }

        return $relayState;
    }

    /**
     * Parses a SAML property and adds this property to this user or returns the value.
     *
     * @param string $samlAttribute
     * @param string $propertyName
     *
     * @return array|null
     */
    public function parseUserAttribute(?string $samlAttribute = null, ?string $propertyName = null): ?array
    {
        if (empty($samlAttribute)) {
            return null;
        }

        if (empty($propertyName)) {
            return $this->getAttribute($samlAttribute);
        }

        return $this->parsedAttributes[$propertyName] = $this->getAttribute($samlAttribute);
    }

    /**
     * Parse the SAML attributes and add them to this user.
     *
     * @param array $attributes Array of properties which need to be parsed, like ['email' => 'urn:oid:0.9.2342.19200300.100.1.3']
     *
     * @return void
     */
    public function parseAttributes(array $attributes = []): void
    {
        foreach ($attributes as $propertyName => $samlAttribute) {
            $this->parseUserAttribute($samlAttribute, $propertyName);
        }
    }

    public function getParsedAttributes(): array
    {
        return $this->parsedAttributes;
    }

    public function getParsedAttribute(string $propertyName): ?array
    {
        return $this->parsedAttributes[$propertyName] ?? null;
    }

    public function __get(string $name): mixed
    {
        return $this->parsedAttributes[$name] ?? null;
    }

    public function __isset(string $name): bool
    {
        return array_key_exists($name, $this->parsedAttributes);
    }

    public function getSessionIndex(): ?string
    {
        return $this->auth->getSessionIndex();
    }

    public function getNameId(): ?string
    {
        return $this->auth->getNameId();
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
