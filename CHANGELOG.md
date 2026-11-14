# Changelog

## [Unreleased]

## [1.0.2] — 2026-11-14

### Changed
- Accepts omnisense/omnibase 6.0 beside 5.1: `omnisense/omnibase` `^5.1 || ^6.0` and `omnibridge/symfony` `^1.1 || ^2.0`. The same code runs on both lines.

## [1.0.1] — 2026-11-14

### Changed
- The current names of what moved to omnibridge/symfony, in place of the former ones the core keeps as aliases only
  until 6.0: `Base\Bundle\AbstractBaseBundle` → `Omnibridge\Symfony\Omnibase\Bundle\AbstractBaseBundle`;
  `Base\Bundle\AbstractBaseConfiguration` → `Omnibridge\Symfony\Omnibase\Bundle\AbstractBaseConfiguration`;
  `Base\Bundle\AbstractBaseExtension` → `Omnibridge\Symfony\Omnibase\Bundle\AbstractBaseExtension`.
  No behaviour change; the bundle no longer needs any alias of the core's `former-names*.php`.

## [1.0.0] — 2026-11-13

First stable release.

### Changed
- Stable requirements, so that `composer require omnibase/gallery` installs at Packagist's default stability:
  `omnisense/omnibase` ^5.1 (in place of `3.x || 4.0 || 5.0` dev branches), `omnibridge/symfony` ^1.1, `doctrine/doctrine-bundle` ^2.10 || ^3.0;
  no `minimum-stability: dev`; the branch alias `dev-main` → `1.x-dev`.
- CI: `.github/workflows/ci.yml` (glitchr-studio/ci's php-package workflow, pinned: the bundle installed alone,
  composer validate, PHPUnit, a consumer install without require-dev with `src/` linted and the bundle class
  autoloaded) and `release.yml` (that CI on a tag, then the release).
- require-dev omnibase/admin (CrudRequiredFieldsTest); `config.allow-plugins` for omnibridge/composer.
- Neutral example data in docs and tests.
