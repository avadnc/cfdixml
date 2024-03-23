# CHANGELOG

## About SemVer

In summary, [SemVer](https://semver.org/) can be viewed as `[ Breaking ].[ Feature ].[ Fix ]`, where:

- Breaking version = includes incompatible changes to the API
- Feature version = adds new feature(s) in a backwards-compatible manner
- Fix version = includes backwards-compatible bug fixes

**Version `0.x.x` doesn't have to apply any of the SemVer rules**

## Version 0.1.3 2022-07-18

Add type template to `MicroCatalog<TEntry>`.

Fix GitHub workflow by configure `infection/extension-installer` plugin.

Fix Scrutinizer CI running on PHP 7.4 to allow code coverage creation.

This release includes also the following previously unreleased changes.

### Unreleased 2022-05-23

Project Maintenance:

- CI: Fix PSalm configuration file.
- Move from `develop/install-development-tools` to `phive`.
- Update license year.
- Update code style standard to PSR 12 and configuration files.

### Unreleased 2021-11-20

Fixed CI. New phpstan version complains about weak return types on example test classes.

### Unreleased 2021-09-25

Fixed CI. Infection fails because it is not working on PHP 7.4.
PHPUnit cannot create code coverage for infection on PHP 8.0; so, upgrade to PHP 8.0 is not a solution.
Install and run Infection throught Composer is the right workaround.

Remove unused extensions on GitHub Actions.

Move code coverage generation to Scrutinizer.

### Unreleased 2021-06-18

Fix description on `composer dev:build`.

PHPUnit should not be verbose by default.

### Unreleased 2021-06-17

Migrate from Travis-CI to GitHub Actions. Thanks Travis-CI!
Code coverage is build on GitHub and uploaded to Scrutinizer.

### Unreleased 2021-06-13

Maintenance on development environment, didn't change any source inside `src/`.

## Version 0.1.2 2020-01-13

- Fix phpdoc on `MicroCatalog::getEntriesArray()`, the return type was defining key type, but it shouldn't.
- Do not include in distribution package folder `/develop`

## Version 0.1.1 2020-01-09

- Update license year.
- Update `psalm` to `level 1`.
- Fix issues detected by `phpstan` and `psalm` recent versions.
- Fix Scrutinizer-CI build.
- Fix Travis-CI build, move from PHP `7.4snapshot` to `7.4`.

## Version 0.1.0 2019-09-30

- Initial release
