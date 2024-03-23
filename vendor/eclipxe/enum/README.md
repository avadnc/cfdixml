# `eclipxe/enum`

[![Source Code][badge-source]][source]
[![Latest Version][badge-release]][release]
[![Software License][badge-license]][license]
[![Build Status][badge-build]][build]
[![Scrutinizer][badge-quality]][quality]
[![Coverage Status][badge-coverage]][coverage]
[![Total Downloads][badge-downloads]][downloads]

> Enum based on the Brent Roose enum idea https://stitcher.io/blog/php-enums

After reading the article [PHP Enums from Brent Roose](https://stitcher.io/blog/php-enums) and review
the implementation made on [spatie/enum](https://github.com/spatie/enum) I think that it overloaded
my expectations. Maybe spatie/enum version 1.0 was more close to what I needed.

So, I created this framework agnostic implementation library about the same concept.

As of PHP 8.1 enums will be part of the language, it means that this library will not be required anymore. 
Check the RFC <https://wiki.php.net/rfc/enumerations> and adapt your code to use PHP Enums.

## Installation

Use [composer](https://getcomposer.org/), install using:

```shell
composer require eclipxe/enum
```

## Usage

*Enum* in other languages are `TEXT` for code, `INTEGER` for values.

There are two meaningful information: *index* (`integer`) and *value* (`string`).

This library provides `Eclipxe\Enum` *abstract class* to be extended.
The *value* is the method's *name* as declared in docblock.
The *index* is the position (starting at zero) in the docblock.

Values are registered one by one taking the overridden value, or the method's name.

Indices are registered one by one taking the overridden index, or the maximum registered value plus 1.

### Enum example

```php
<?php
/**
 * This is a common use case enum sample
 * source: tests/Fixtures/Stages.php
 *
 * @method static self created()
 * @method static self published()
 * @method static self reviewed()
 * @method static self purged()
 *
 * @method bool isCreated()
 * @method bool isPublished()
 * @method bool isReviewed()
 * @method bool isPurged()
 */
final class Stages extends Eclipxe\Enum\Enum
{
}
```

### Creation of instances

You can create new instances from values using construct with value, construct with index or static method name.

```php
<?php
use Eclipxe\Enum\Tests\Fixtures\Stages;

// create from value
$purged = new Stages('purged');
 
// create from index
$purged = new Stages(3);

// create from an object that can be converted to string and contains the value
$other = new Stages($purged);

// create from static method
$purged = Stages::purged();

// create from static method is not case sensitive as methods are not
$purged = Stages::{'PURGED'}();

// throws a BadMethodCallException because foobar is not part of the enum
$purged = Stages::{'FOOBAR'}();
```

### List all the options

The only static method exposed on the Enum is `Enum::toArray(): array` that export the list of registered
possible values as an array of indices and values.

```php
<?php
use Eclipxe\Enum\Tests\Fixtures\Stages;

var_export(Stages::toArray());
/*
[
    0 => 'created',
    1 => 'published',
    2 => 'reviewed',
    3 => 'purged',
] 
*/
```

### Check if instance is of certain type

Use the methods `is<name>()` to compare to specific value.

You have to define these methods in your docblock to let your IDE or code analyzer detect what you are doing.

```php
<?php
use Eclipxe\Enum\Tests\Fixtures\Stages;

$stage = Stages::purged();

$stage->isPurged(); // true
$stage->isPublished(); // false

$stage->{'isSomethingElse'}(); // false, even when SomethingElse is not defined
$stage->{'SomethingElse'}(); // throw BadMethodCallException
```

Or use weak comparison (equality, not identity):

```php
<?php
use Eclipxe\Enum\Tests\Fixtures\Stages;

$stage = Stages::purged();
var_export($stage === Stages::purged()); // false, is not the same identity
var_export($stage == Stages::purged()); // true
var_export($stage == Stages::published()); // false
var_export($stage->value() === Stages::purged()->value()); // true (compare using value)
var_export($stage->index() === Stages::purged()->index()); // true (compare using index)
```

### Overriding values or indices

You can override values or indices by overriding the methods `overrideValues()` or `overrideIndices()`.

Rules:

- Return `array` key must be the name of the method as it was defined in the docblock section (case-sensitive).
- If override's value is `null` then it will not be overridden.
- When override a value, if previous value exists then will throw a `ValueOverrideException`.
- When override an index, if previous value exists then will throw a `IndexOverrideException`.

```php
<?php
/**
 * This is an enum case where names and values are overridden
 *
 * @method static self monday()
 * @method static self tuesday()
 * @method static self wednesday()
 * @method static self thursday()
 * @method static self friday()
 * @method static self saturday()
 * @method static self sunday()
 */
final class WeekDays extends \Eclipxe\Enum\Enum
{
    protected static function overrideValues(): array
    {
        return [
            'monday' => 'Monday',
            'tuesday' => 'Tuesday',
            'wednesday' => 'Wednesday',
            'thursday' => 'Thursday',
            'friday' => 'Friday',
            'saturday' => 'Saturday',
            'sunday' => 'Sunday',
        ];
    }

    protected static function overrideIndices(): array
    {
        return [
            'monday' => 1,
        ];
    }
}

```

This will define these `array<index, value>`, retrieved using static method `WeekDays::toArray()`:

```php
[
    1 => 'Monday',
    2 => 'Tuesday',
    3 => 'Wednesday',
    4 => 'Thursday',
    5 => 'Friday',
    6 => 'Saturday',
    7 => 'Sunday',
];
```

Remember that Enum creation depends on registered values and indices,
if and invalid value or index is used then an exception is thrown:

```php
<?php
use Eclipxe\Enum\Tests\Fixtures\WeekDays;

new WeekDays(0); // throws IndexNotFoundException
new WeekDays(1); // WeekDays {value: 'Monday', index: 1}

new WeekDays('sunday'); // throws ValueNotFoundException (it is case sensitive)
new WeekDays('Sunday'); // WeekDays {value: 'Sunday', index: 7}
```

### Extending

When creating an Enum extending from other, the parent Enum have priority on indices and values.
You cannot override indices or values of previous classes.

I recommend you to declare your Enum classes as `final` to disable extension.

If using class extension, do not use `@method static self name()` syntax,
use `@method static static name()` syntax instead to help analysis tools.

See examples at `tests/Fixtures/ColorsBasic.php`, `tests/Fixtures/ColorsExtended.php`
and `tests/Fixtures/ColorsExtendedWithBlackAndWhite.php`.

### Exceptions

Exceptions thrown from this package implements the empty interface `Eclipxe\Enum\Exceptions\EnumExceptionInterface`.

## PHP Support

This library is compatible with at least the oldest [PHP Supported Version](https://php.net/supported-versions.php)
with **active** support. Please, try to use PHP full potential.

We adhere to [Semantic Versioning](https://semver.org/).
We will not introduce any compatibility backwards change on major versions.

Internal classes (using `@internal` annotation) are not part of this agreement
as they must only exist inside this project. Do not use them in your project.


## Contributing

Contributions are welcome! Please read [CONTRIBUTING][] for details
and don't forget to take a look in the [TODO][] and [CHANGELOG][] files.


## Copyright and License

The `eclipxe/enum` library is copyright © [Carlos C Soto](https://eclipxe.com.mx/)
and licensed for use under the MIT License (MIT). Please see [LICENSE][] for more information.


[contributing]: https://github.com/eclipxe13/enum/blob/main/CONTRIBUTING.md
[changelog]: https://github.com/eclipxe13/enum/blob/main/docs/CHANGELOG.md
[todo]: https://github.com/eclipxe13/enum/blob/main/docs/TODO.md

[source]: https://github.com/eclipxe13/enum
[release]: https://github.com/eclipxe13/enum/releases
[license]: https://github.com/eclipxe13/enum/blob/main/LICENSE
[build]: https://github.com/eclipxe13/enum/actions/workflows/build.yml?query=branch:main
[quality]: https://scrutinizer-ci.com/g/eclipxe13/enum/
[coverage]: https://scrutinizer-ci.com/g/eclipxe13/enum/code-structure/main/code-coverage
[downloads]: https://packagist.org/packages/eclipxe/enum

[badge-source]: https://img.shields.io/badge/source-eclipxe13/enum-blue?style=flat-square
[badge-release]: https://img.shields.io/github/release/eclipxe13/enum?style=flat-square
[badge-license]: https://img.shields.io/github/license/eclipxe13/enum?style=flat-square
[badge-build]: https://img.shields.io/github/workflow/status/eclipxe13/enum/build/main?style=flat-square
[badge-quality]: https://img.shields.io/scrutinizer/g/eclipxe13/enum/main?style=flat-square
[badge-coverage]: https://img.shields.io/scrutinizer/coverage/g/eclipxe13/enum/main?style=flat-square
[badge-downloads]: https://img.shields.io/packagist/dt/eclipxe/enum?style=flat-square
