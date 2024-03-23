# CHANGELOG

## About SemVer

In summary, [SemVer](https://semver.org/) can be viewed as `[ Breaking ].[ Feature ].[ Fix ]`, where:

- Breaking version = includes incompatible changes to the API
- Feature version = adds new feature(s) in a backwards-compatible manner
- Fix version = includes backwards-compatible bug fixes

**Version `0.x.x` doesn't have to apply any of the SemVer rules**

## Version 0.2.6 2020-06-17

There are no significant code changes, only some refactoring to improve testing and type understanding.

Integrate `psalm` and `infection` to build pipeline.

Change default branch from `master` to `main`

Move continuous integration to GitHub Actions. Thanks Travis-CI!

## Version 0.2.5 2021-06-08

Code changes:

- Remove creational abstract static methods for exceptions.

Development changes:

- Upgrade to `friendsofphp/php-cs-fixer:^3.0`.

CI:

- Add PHP 8.0 to Travis matrix build.
- Do not upgrade composer on scrutinizer since it is on a read-only file system.

## Version 0.2.4 2020-01-09

- It is not intented to create a breaking change, but strictly speaking there is one:
  The classes `GenericOverrideException` and `GenericNotFoundException` have changed making the class and `create` method
  `abstract`, also removed `TYPE_NAME` constant. It would only affect you in case that you are extending this clases.  
- Development:
    - Add `psalm` to `composer dev:build`.
    - Change `phpstan/phpstan-shim` to `phpstan/phpstan`.
    - Upgrade `phpstan` to `^0.12`.
    - Scrutinizer-CI: remove all development dependences but `phpunit`. 
- Update license year.
- Add more examples to compare two enums, use https on links.

## Version 0.2.3 2019-12-06

- Improve development environment
- Add PHP 7.4 to Travis CI

## Version 0.2.2 2019-09-30

- Allow syntax `@method static static name()`.
- Improve library type system, [psalm](https://github.com/vimeo/psalm) is 100% clean,
  not included as dev dependency yet. This fixes all issues at scrutinizer.
- Create one more tests to probe inherit classes type system.
- Package: include support information.

## Version 0.2.1 2019-09-20

- Fix possible bug calling no-static method as static.
- Allow `@method` declarations with lead spaces, tabs and asterisks.
- Simplify travis builds, build coverage on Scrutinizer.
- Improve development environment and dist package.

## Version 0.2.0 2019-03-25

- Rewrite with indices and values in mind

## Version 0.1.0 2019-03-20

- Initial working release for testing with friends
