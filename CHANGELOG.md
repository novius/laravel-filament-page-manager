# Changelog

All notable changes to `novius/laravel-filament-page-manager` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.1.0](https://github.com/novius/laravel-filament-page-manager/compare/1.0.13...main)

### Fixed

- Register the page route as a fallback route, tried after all the others. With one domain per locale (localized
  routes bound to a domain), Laravel tried it before the routes without domain, and it took the GET routes of the
  other packages, such as the script of Livewire: the login form of Filament was submitted as GET and the back office
  could not be reached.

### Changed

- As the page route is now a fallback route, the routes of the project always win over it, even when they are declared
  after `PageManager::routes()`: a route of the project with the same path as the slug of a page now takes the request.
- **Upgrade note**: a project that registers its own `Route::fallback()` before `PageManager::routes()` now gets every
  page request in its fallback, and no page is served anymore. Call `PageManager::routes()` before `Route::fallback()`.

## [1.0.13](https://github.com/novius/laravel-filament-page-manager/compare/1.0.12...1.0.13) - 2026-10-22

### Fixed

- Only exclude the admin path prefix from the page route.

### Dependencies

- Bump `shivammathur/setup-php` from 2.37.2 to 2.40.0.

## [1.0.12](https://github.com/novius/laravel-filament-page-manager/compare/1.0.11...1.0.12) - 2026-09-28

### Fixed

- Make the page route cacheable on Laravel 13.

### Changed

- Use the Laravel package skeleton (tooling, CI, Pest tests, workbench).
- Drop PHP 8.2 from the CI matrix.

## [1.0.11](https://github.com/novius/laravel-filament-page-manager/compare/1.0.10...1.0.11) - 2026-06-26

### Changed

- Improve the seeder to use the configured page model class.
- Drop Laravel 11 from the CI matrix.

## [1.0.10](https://github.com/novius/laravel-filament-page-manager/compare/1.0.9...1.0.10) - 2026-06-02

### Fixed

- Fix configs for Filament navigation.

## [1.0.9](https://github.com/novius/laravel-filament-page-manager/compare/1.0.8...1.0.9) - 2026-06-02

### Added

- Add configs for Filament navigation.

## [1.0.8](https://github.com/novius/laravel-filament-page-manager/compare/1.0.7...1.0.8) - 2026-05-13

### Fixed

- Fix cache serialisation on Laravel 13.

## [1.0.7](https://github.com/novius/laravel-filament-page-manager/compare/1.0.6...1.0.7) - 2026-05-12

### Added

- Laravel 13 compatibility.

## [1.0.6](https://github.com/novius/laravel-filament-page-manager/compare/1.0.5...1.0.6) - 2026-03-19

### Fixed

- Fix `EditPage` when not all fields are set in extras.

## [1.0.5](https://github.com/novius/laravel-filament-page-manager/compare/1.0.4...1.0.5) - 2026-03-09

### Fixed

- Fix `getSpecialPage()` when no locale is passed.

## [1.0.4](https://github.com/novius/laravel-filament-page-manager/compare/1.0.3...1.0.4) - 2026-03-06

### Changed

- Improve the page seeder.

## [1.0.3](https://github.com/novius/laravel-filament-page-manager/compare/1.0.2...1.0.3) - 2026-03-03

### Fixed

- Fix `getSpecialPage()` when running tests.

## 1.0.2 - 2026-02-19

### Added

- Filament 5 support.
- Add `SpecialPage::addRouteGet()` and `SpecialPage::addRoutePost()` methods.

### Fixed

- Fix `locale_parent_id` foreign key delete cascade.
- Fix `PageSeeder` handling of `locale_parent_id`.
- Fix `getGuardIcon()`.
