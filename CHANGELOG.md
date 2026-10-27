# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/en/1.0.0/)
and this project adheres to [Semantic Versioning](http://semver.org/spec/v2.0.0.html).

This is a fork of [24Slides/laravel-saml2](https://github.com/24Slides/laravel-saml2). Everything up to and including 2.4.1 is the
history of the upstream package; the fork starts with 2.5.1.

## [Unreleased]

## [2.6.0] - 2026-10-27

### Changed
- `login`, `logout` and `sls` redirect with a Laravel response instead of letting php-saml redirect and `exit()`: the middleware runs
  and the session is saved (e.g. a listener of `SignedOut` that logs the user out is kept). The answer to a logout request of the IdP
  is available with `Auth::getSlsRedirectUrl()`.
- `acs` and `sls` handle exceptions of php-saml (no `SAMLResponse`, an assertion that can not be decrypted, ...) like invalid messages: the
  error is logged and the user is redirected to the error route instead of getting an error page.

### Added
- `security.wantAssertionsEncrypted` and `security.allowRepeatAttributeName` are listed in the config (php-saml options that were already passed through).
- `metadata.validUntil` and `metadata.cacheDuration` configure the validity of the SP metadata.

## [2.5.2] - 2026-07-28

### Added
- `sp.assertionConsumerService.url` and `sp.singleLogoutService.url` are configurable with `SAML2_SP_ACS_URL` and `SAML2_SP_SLS_URL`
  (e.g. behind a reverse proxy).

### Changed
- `acs` redirects to the intended URL with `redirect()->intended()`, `sls` to the intended URL or the `logoutRoute`.
- `Saml2Controller` has no return types and no `declare(strict_types=1)` anymore.
- `composer.json`: the package `replaces` `24slides/laravel-saml2` instead of conflicting with it.

## [2.5.1] - 2026-03-26

First release of the fork. It is no longer a drop-in replacement of 24slides/laravel-saml2.

### Added
- Support for Laravel 13.
- `saml2.serverPort` (default `APP_PORT`): the port php-saml uses for the URL of the application, e.g. behind a docker container
  with another port (PR [#68](https://github.com/24Slides/laravel-saml2/pull/68) of upstream).
- `Helpers\Uuid` generates UUIDv7 for new tenants (taken from scaler-tech/laravel-saml2). `ramsey/uuid` is not needed anymore.

### Changed
- **Breaking:** the package is `niklasschmitt/laravel-saml2`, the namespace is `NiklasSchmitt\Saml2` (was `Slides\Saml2`), the facade
  alias is `NiklasSchmitt\Saml2\Facades\Auth`. Update `config/saml2.php` (`tenantModel`) and every reference to the old namespace.
- **Breaking:** requires PHP `>=8.4`, Laravel 11, 12 or 13 (Laravel 9 and 10 are dropped) and `onelogin/php-saml` `^4.3.1` (the package
  conflicts with older versions, with `24slides/laravel-saml2` and with `scaler-tech/laravel-saml2`).
- **Breaking:** the migrations are consolidated: `create_saml2_tenants_table` includes `relay_state_url` and `name_id_format`, the
  migrations of 2020 are removed and an additional migration adds a unique index for `uuid` and an index for `key`. The files are
  renamed to `2026_03_17_create_saml2_tenants_table.php` and `2026_03_18_add_index_to_saml2_tenants_table.php` and are anonymous
  classes. An existing installation has already created the table: do not run the create migration again (mark it as run), run the
  index migration.
- The `sls` route accepts `POST` besides `GET`, so a logout request of the IdP that uses the POST binding is handled
  (taken from scaler-tech/laravel-saml2).
- Refactored the code base: `declare(strict_types=1)`, typed signatures, constructor promotion, `OneLoginBuilder::bootstrap(Tenant $tenant)`
  replaces `withTenant()->bootstrap()`. `Auth` and `OneLogin_Saml2_Auth` are bound per request instead of being singletons.
- The README is reduced to a pointer to the documentation of 24Slides, the license names the new copyright holder.
- `minimum-stability` is `stable`.

### Fixed
- `saml2:update-tenant` does not overwrite the Name ID format with the default anymore if `--nameIdFormat` is not given.

### Removed
- The test suite (`tests/`, `phpunit.xml`), the PHPUnit dev dependencies, `.travis.yml` and `.scrutinizer.yml`.
- `composer.json`: the branch alias `dev-master`.


## [24Slides/laravel-saml2](https://github.com/24Slides/laravel-saml2)
here starts the changelog of the origin package

---


## [2.4.1] - 2025-02-04

### Added
- Add explicit nullable type in a few places to avoid "PHP Deprecated" warning (PR [#113](https://github.com/24Slides/laravel-saml2/pull/113), @andreypopov)

## [2.4.0] - 2024-04-13

### Added
- Add support for Laravel 11 (PR #85, @diegofonseca)

## [2.3.0] - 2023-11-26

### Added
- Add a possibility to skip loading default database migrations (PR #76, @abublih)

### Fixed
- Add .gitattributes to ensure that unnecessary files aren't exported when downloading via Composer (PR #75, @owenvoke)

## [2.2.1] - 2023-09-28

### Added
- Add uuid of tenant to `salm2.error_detail`; makes logs more informative (PR #74, @vopolonc)

## [2.2.0] - 2023-02-20

### Added
- Add support for Laravel 10 (PR #56, @danijelk)

## [2.1.0] - 2023-02-11

### Added
- Add an ability to customize Tenant model (PR #49, @dmyers)

### Fixed

- Change idpKey to uuid in saml2.php (PR #45, @joelpittet)

## [2.0.11] - 2022-09-13

### Fixed
- Improve sls handling of errors to match acs action (PR #35, @dmyers)
- Fix querying tenants via console commands when using PostgreSQL (issue #22)

## [2.0.10] - 2022-04-14

### Added

- Add support for Laravel 9 (pr #20, @SimplyCorey)

## [2.0.9] - 2021-03-30

### Added

- Add SignedIn event accessors, to match the docs (#10, @darynmitchell)

## [2.0.8] - 2020-11-12

### Fixed
- Version require ramsey/uuid and phpunit/phpunit
- Update branch aliases

## [2.0.7] - 2020-10-28

### Added
- Laravel 8 support

## [2.0.6] - 2020-10-23

### Fixed
- Setting Name ID Format on SP bootstrap

## [2.0.5] - 2020-10-23

### Added
- The ability to customize Name ID Format for different Identity Providers

## [2.0.4] - 2020-10-22

### Added
- Custom Relay State URL per Tenant (to specify a redirection URL after sign in)

## [2.0.3] - 2020-07-01

### Added
- Support for Laravel 7 (#4)
- Add branch-aliases in composer.json

## [2.0.2] - 2020-03-24

### Fixed
- Only log debug messages when debug is enabled in config file (#3)

## [2.0.1] - 2019-10-17

### Added
- Support for Laravel 6 (#1)

### Fixed
- Typos in README.md (#2)

## [2.0.0] - 2019-06-26

### Added
- Completely changed the way of supporting multiple Identity Providers by adding Tenants
- Helper functions `saml_url()`, `saml_route()`, `saml_tenant_uuid()`
- Initializing SP in middleware
- Database migrations
- Console commands `saml2:create-tenant`, `saml2:update-tenant`, `saml2:delete-tenant`,
  `saml2:restore-tenant`, `saml2:list-tenants`, `saml2:tenant-credentials`

### Fixed
- Routes are now accepting UUID of tenants instead of `idpKey`

### Removed
- IdP Resolver, now it resolves by `ResolveTenant` middleware by matching UUID on routes
- Building SSO SP in Laravel ServiceProvider

## [1.2.0] - 2019-06-20

### Added
- Refactored the way of resolving identity provider, now we take it from URL
- Implemented helper saml_idp_key() to retrieve a resolved IdP
- Implemented helpers saml_url(), saml_route() to generate SSO-friendly links (fx. on emails)

### Fixed
- Fixed redirecting to a custom URL on login request using the `returnTo` query parameter

### Removed
- Removed referrer URLs from config parameters

## [1.1.3] - 2019-02-25

### Added
- Implemented keeping resolved IdP (`Saml2::getResolvedIdPKey()`)

## [1.1.2] - 2019-02-17

### Fixed
- Fixed tests
- PHPUnit version

## [1.1.1] - 2019-02-15

### Added
- Added CHANGELOG.md

### Fixed
- Restricted support from Laravel 5.4+
- Restricted support PHP 7.0+
- Updated README.md

## [1.1.0] - 2019-01-28

### Added
- Support of multiple IdPs (Identity Providers)

### Fixed
- Renamed configuration file from `saml2_settings` to `saml2`
- Replaced underscores with dots in routes
- Minor refactoring, formatting

[Unreleased]: https://github.com/NiklasSchmitt/laravel-saml2/compare/2.6.0...HEAD
[2.6.0]: https://github.com/NiklasSchmitt/laravel-saml2/compare/2.5.2...2.6.0
[2.5.2]: https://github.com/NiklasSchmitt/laravel-saml2/compare/2.5.1...2.5.2
[2.5.1]: https://github.com/NiklasSchmitt/laravel-saml2/commit/106b308
[2.4.1]: https://github.com/24Slides/laravel-saml2/compare/2.4.0...2.4.1
[2.4.0]: https://github.com/24Slides/laravel-saml2/compare/2.3.0...2.4.0
[2.3.0]: https://github.com/24Slides/laravel-saml2/compare/2.2.1...2.3.0
[2.2.1]: https://github.com/24Slides/laravel-saml2/compare/2.2.0...2.2.1
[2.2.0]: https://github.com/24Slides/laravel-saml2/compare/2.1.0...2.2.0
[2.1.0]: https://github.com/24Slides/laravel-saml2/compare/2.0.10...2.1.0
[2.0.10]: https://github.com/24Slides/laravel-saml2/compare/2.0.9...2.0.10
[2.0.9]: https://github.com/24Slides/laravel-saml2/compare/2.0.8...2.0.9
[2.0.8]: https://github.com/24Slides/laravel-saml2/compare/2.0.7...2.0.8
[2.0.7]: https://github.com/24Slides/laravel-saml2/compare/2.0.6...2.0.7
[2.0.6]: https://github.com/24Slides/laravel-saml2/compare/2.0.5...2.0.6
[2.0.5]: https://github.com/24Slides/laravel-saml2/compare/2.0.4...2.0.5
[2.0.4]: https://github.com/24Slides/laravel-saml2/compare/2.0.3...2.0.4
[2.0.3]: https://github.com/24Slides/laravel-saml2/compare/2.0.2...2.0.3
[2.0.2]: https://github.com/24Slides/laravel-saml2/compare/2.0.1...2.0.2
[2.0.1]: https://github.com/24Slides/laravel-saml2/compare/2.0.0...2.0.1
[2.0.0]: https://github.com/24Slides/laravel-saml2/compare/1.2.0...2.0.0
[1.2.0]: https://github.com/24Slides/laravel-saml2/compare/1.1.3...1.2.0
[1.1.3]: https://github.com/24Slides/laravel-saml2/compare/1.1.2...1.1.3
[1.1.2]: https://github.com/24Slides/laravel-saml2/compare/1.1.1...1.1.2
[1.1.1]: https://github.com/24Slides/laravel-saml2/compare/1.1.0...1.1.1
[1.1.0]: https://github.com/24Slides/laravel-saml2/compare/1.0.0...1.1.0
