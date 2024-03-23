# `eclipxe/micro-catalog`

[![Source Code][badge-source]][source]
[![Latest Version][badge-release]][release]
[![Software License][badge-license]][license]
[![Build Status][badge-build]][build]
[![Scrutinizer][badge-quality]][quality]
[![Coverage Status][badge-coverage]][coverage]
[![Total Downloads][badge-downloads]][downloads]

> Micro catalog PHP template class, useful to create value objects based on a known small catalog.

Several times I have the need for a catalog, by example, when receiving a result code and correlate to
specific related values. This library helps me to resolve this common problem, and it may help you too.

## Installation

Use [composer](https://getcomposer.org/), install using:

```shell
composer require eclipxe/micro-catalog
```

## Usage

`MicroCatalog` is a template class, so you need to extend it to use it. Strictly speaking, you only
need to implement two abstract methods: `getEntriesArray(): array` and  `getEntryValueOnUndefined(): mixed`.

The sugar lives on `is<Type>(): bool` and `get<Property>(): bool`, with the special case of `isUndefined(): bool`.
The `is<Type>()` methods allow to distinct a type by its index.
The `get<Property>()` methods allow to get a *string key* from the *value array* when it has content.

See the following examples:

- [`ResultCodes`](https://github.com/eclipxe13/micro-catalog/blob/main/tests/Fixtures/ResultCodes.php)
  Common usage with value as `scalar`, allows undefined properties.
- [`ComplexArray`](https://github.com/eclipxe13/micro-catalog/blob/main/tests/Fixtures/ComplexArray.php)
  Common usage with value as `array`, get property, allows undefined properties.
- [`ComplexObject`](https://github.com/eclipxe13/micro-catalog/blob/main/tests/Fixtures/ComplexObject.php)
  Common usage with value as `object`, get property, disable undefined properties.

### `MicroCatalog` example

```php
<?php

declare(strict_types=1);

use Eclipxe\MicroCatalog\MicroCatalog;

/**
 * ResultCodes is an example class to show the basic usage
 *
 * @method bool isOk()
 * @method bool isWarning()
 * @method bool isError()
 */
final class ResultCodes extends MicroCatalog
{
    public static function getEntriesArray(): array
    {
        return [
            0 => 'Ok',
            1 => 'Warning',
            2 => 'Error',
        ];
    }

    public function getEntryValueOnUndefined(): string
    {
        return '#N/A';
    }

    public function getEntryId(): string
    {
        return strval($this->getEntryValue());
    }
}

$warning = new ResultCodes(1);
var_dump($warning->isWarning());        // bool(true)
var_dump($warning->getEntryIndex());    // int(1)
var_dump($warning->getEntryValue());    // string(7) "Warning"
var_dump($warning->getEntryId());       // string(7) "Warning" (method was overriden)
var_dump($warning->isUndefined());      // bool(false)

$other = new ResultCodes(99);
var_dump($other->isUndefined());        // bool(true)
var_dump($other->getEntryIndex());      // int(99)
var_dump($other->getEntryValue());      // string(4) "#N/A"
```

### Undefined entry

When creating an instance of `MicroCatalog`, when the key isn't found in the list of known values
it will use the value from `getValueOnUndefined()`.

If you don't want to allow unknown instances you can override and throw an exception on `getValueOnUndefined()`.

### Check if instance is of certain entry

Use the methods `is<name>()` to compare to specific value.

It will work by default when the indexes are strings, in example: `isFoo()`
will evaluate if the current index is `foo` (with the first character as lower case).

You can redefine whits behavior by overriding the `getEntryId(): string` function. So `isFoo()` will compare
`foo` with the returned value of `getEntryId(): string`.

You have to define these methods in your docblock to let your IDE or code analyzer detect what you are doing.

### Get a magic property

If your known values array constains an array with string keys or a standard object with public variables then
`getSomething()` will return what is defined on `something` key/property.

Even when PHP supports method with different case, this class use exact case, so `getSomething()` is different
from `getSomeThing()`. The only transformation made to locate the key/property is to lower first char, then
`getSomething()` will look up for `something` into local value. Anyhow, you can override the method
`getEntryValueWithKey(string $key): mixed` to perform other transformation on key/property name.

### Extending

I recommend you to declare your `MicroCatalog` classes as `final` to disable extension.

When creating a `MicroCatalog` extending from other, the parent `MicroCatalog` have priority on indices and values.
You cannot override indices or values of previous classes.

### Exceptions

Exceptions thrown from this package implements the empty interface `Eclipxe\MicroCatalog\Exceptions\MicroCatalogException`.

### Creational patterns

You can write other creational patterns, the implemented constructor uses the value as index entry. If you want
to create an entry based on some value or other type of evaluation I don't recommend to you to override the
constructor, instead you can use static method calls.

## PHP Support

This library is compatible with at least the oldest [PHP Supported Version](http://php.net/supported-versions.php)
with **active** support. Please, try to use PHP full potential.

We adhere to [Semantic Versioning](https://semver.org/).
We will not introduce any compatibility backwards change on major versions.

Internal classes (using `@internal` annotation) are not part of this agreement
as they must only exist inside this project. Do not use them in your project.

## Contributing

Contributions are welcome! Please read [CONTRIBUTING][] for details
and don't forget to take a look in [TODO][] and [CHANGELOG][] files.

## Copyright and License

The `eclipxe/micro-catalog` library is copyright © [Carlos C Soto](http://eclipxe.com.mx/)
and licensed for use under the MIT License (MIT). Please see [LICENSE][] for more information.

[contributing]: https://github.com/eclipxe13/micro-catalog/blob/main/CONTRIBUTING.md
[changelog]: https://github.com/eclipxe13/micro-catalog/blob/main/docs/CHANGELOG.md
[todo]: https://github.com/eclipxe13/micro-catalog/blob/main/docs/TODO.md

[source]: https://github.com/eclipxe13/micro-catalog
[release]: https://github.com/eclipxe13/micro-catalog/releases
[license]: https://github.com/eclipxe13/micro-catalog/blob/main/LICENSE
[build]: https://github.com/eclipxe13/micro-catalog/actions/workflows/build.yml?query=branch:main
[quality]: https://scrutinizer-ci.com/g/eclipxe13/micro-catalog/
[coverage]: https://scrutinizer-ci.com/g/eclipxe13/micro-catalog/code-structure/main/code-coverage
[downloads]: https://packagist.org/packages/eclipxe/micro-catalog

[badge-source]: https://img.shields.io/badge/source-eclipxe/micro--catalog-blue?style=flat-square
[badge-release]: https://img.shields.io/github/release/eclipxe13/micro-catalog?style=flat-square
[badge-license]: https://img.shields.io/github/license/eclipxe13/micro-catalog?style=flat-square
[badge-build]: https://img.shields.io/github/workflow/status/eclipxe13/micro-catalog/build/main?style=flat-square
[badge-quality]: https://img.shields.io/scrutinizer/g/eclipxe13/micro-catalog/main?style=flat-square
[badge-coverage]: https://img.shields.io/scrutinizer/coverage/g/eclipxe13/micro-catalog/main?style=flat-square
[badge-downloads]: https://img.shields.io/packagist/dt/eclipxe/micro-catalog?style=flat-square
