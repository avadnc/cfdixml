# eclipxe/XmlResourceRetriever

[![Source Code][badge-source]][source]
[![Latest Version][badge-release]][release]
[![Software License][badge-license]][license]
[![Build Status][badge-build]][build]
[![Scrutinizer][badge-quality]][quality]
[![Coverage Status][badge-coverage]][coverage]
[![Total Downloads][badge-downloads]][downloads]

> XSD and XLST resource downloader for local storage

The purpose of this library is to download recursively XML resources from internet to a local storage for further usage.
In this moment it only allows Schemas (XSL) and Transformations (XSLT) but is easely extensible implementing the
`RetrieverInterface` interface or extending the `AbstractXmlRetriever` class.

For every downloaded file it will override its dependences to a relative location, in this way, every dependence
should be available to work offline.

You can use the local object `PhpDownloader` that simply uses `copy` function to get and store a file from internet.
You can also use your own implementation of the `DownloaderInterface` according to your needs.
If you built a configurable and useful downloader class feel free to contribute it to this project. 

## Installation

Use [composer](https://getcomposer.org/), so please run
```shell
composer require eclipxe/xmlresourceretriever
```

## Basic usage

```php
<?php
/*
 * This will download the file into
 * /project/cache/www.sat.gob.mx/sitio_internet/cfd/3/cadenaoriginal_3_3/cadenaoriginal_3_3.xslt
 * and all its includes and imports (currently 27 files)
 */
use XmlResourceRetriever\XsltRetriever;
$xslt = new XsltRetriever('/project/cache');
$local = $xslt->retrieve('http://www.sat.gob.mx/sitio_internet/cfd/3/cadenaoriginal_3_3/cadenaoriginal_3_3.xslt');
echo $local; /* /project/cache/www.sat.gob.mx/sitio_internet/cfd/3/cadenaoriginal_3_3/cadenaoriginal_3_3.xslt */
```

## Retriever more information

This methods apply to `XslRetriever` and `XsltRetriever` 

- `retrieve($url)`: Download recursively an url and store it into the retriever base path,
  it changes the child elements that contains references to other files.
- `download($url)`:  Download an url and store it into the retriever base path.
  It does not validate the file for xml errors. It does not download dependences.
- `buildPath($url)`: Return the location of were a file should be stored according to the base path.
- `setDownloader($downloader)`: Change the default `PhpDownloader` to a custom implementation.

`XsdRetriever` search for namespace `http://www.w3.org/2001/XMLSchema` elements `import` and `include`.

`XsltRetriever` search for namespace `http://www.w3.org/1999/XSL/Transform` elements `import` and `include`.

## PHP Support

This library is compatible with latest [PHP supported version](https://www.php.net/supported-versions.php) and above.
Please, try to use the full potential of the language.

## Contributing

Contributions are welcome! Please read [CONTRIBUTING][] for details
and don't forget to take a look in the [TODO][] and [CHANGELOG][] files.

## Copyright and License

The `eclipxe/XmlResourceRetriever` library is copyright © [Carlos C Soto](http://eclipxe.com.mx)
and licensed for use under the MIT License (MIT). Please see [LICENSE][] for more information.

[contributing]: https://github.com/eclipxe13/XmlResourceRetriever/blob/master/CONTRIBUTING.md
[changelog]: https://github.com/eclipxe13/XmlResourceRetriever/blob/master/docs/CHANGELOG.md
[todo]: https://github.com/eclipxe13/XmlResourceRetriever/blob/master/docs/TODO.md

[source]: https://github.com/eclipxe13/XmlResourceRetriever
[release]: https://github.com/eclipxe13/XmlResourceRetriever/releases
[license]: https://github.com/eclipxe13/XmlResourceRetriever/blob/master/LICENSE
[build]: https://travis-ci.org/eclipxe13/XmlResourceRetriever?branch=master
[quality]: https://scrutinizer-ci.com/g/eclipxe13/XmlResourceRetriever/
[coverage]: https://scrutinizer-ci.com/g/eclipxe13/XmlResourceRetriever/code-structure/master/code-coverage
[downloads]: https://packagist.org/packages/eclipxe/XmlResourceRetriever

[badge-source]: https://img.shields.io/badge/source-eclipxe13/XmlResourceRetriever-blue.svg?style=flat-square
[badge-release]: https://img.shields.io/github/release/eclipxe13/XmlResourceRetriever.svg?style=flat-square
[badge-license]: https://img.shields.io/github/license/eclipxe13/XmlResourceRetriever.svg?style=flat-square
[badge-build]: https://img.shields.io/travis/eclipxe13/XmlResourceRetriever/master.svg?style=flat-square
[badge-quality]: https://img.shields.io/scrutinizer/g/eclipxe13/XmlResourceRetriever/master.svg?style=flat-square
[badge-coverage]: https://img.shields.io/scrutinizer/coverage/g/eclipxe13/XmlResourceRetriever/master.svg?style=flat-square
[badge-downloads]: https://img.shields.io/packagist/dt/eclipxe/XmlResourceRetriever.svg?style=flat-square
