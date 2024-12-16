# Slpates : Super PHP Template Engine

**WIP** : fork of [league/plates](https://github.com/thephpleague/plates). The main goal is to enable PHPStan support for templates and ensure IDE code completion works seamlessly without additional effort.

[![Maintainer](http://img.shields.io/badge/maintainer-@robind4-blue.svg?style=flat-square)](https://twitter.com/robind4)
[![Source Code](http://img.shields.io/badge/source-league/plates-blue.svg?style=flat-square)](https://github.com/piedweb/splates)
[![Latest Version](https://img.shields.io/github/release/piedweb/splates.svg?style=flat-square)](https://github.com/piedweb/splates/releases)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE)
[![Build Status](https://img.shields.io/github/actions/workflow/status/piedweb/splates/php.yml?style=flat-square)](https://github.com/piedweb/splates/actions?query=workflow%3APHP+branch%3Av3)
[![Quality Score](https://img.shields.io/scrutinizer/g/piedweb/splates.svg?style=flat-square)](https://scrutinizer-ci.com/g/piedweb/splates)
[![Total Downloads](https://img.shields.io/packagist/dt/piedweb/splates.svg?style=flat-square)](https://packagist.org/packages/piedweb/splates)

Splates is a native PHP template system that's fast, easy to use and easy to extend. It's inspired by the excellent [Twig](http://twig.sensiolabs.org/) template engine and strives to bring modern template language functionality to native PHP templates. Splates is designed for developers who prefer to use native PHP templates over compiled template languages.

### Highlights

- Native PHP templates, no new [syntax](https://platesphp.com/templates/syntax/) to learn
- ... coming with native IDE autocompletion and static analysis without extra work to your templates
- Plates is a template system, not a template language
- Increase code reuse with template layouts and inheritance
- Data sharing across templates
- Preassign data to specific templates
- Built-in escaping helpers
- Framework-agnostic, will work with any project. Heavily tested with Symfony.
- Decoupled design makes templates easy to test
- Composer ready and PSR-2 compliant

### Dropped feature from `league/plates`

- Drop `insert` ➜ prefer `<?=$f(...)?>`
- Drop uri extension
- Simplify internal api dropping a lot of get* and set* for public method

## Installation

Splates is available via Composer:

```
composer require piedweb/splates
```

## Documentation

Full documentation is not writed yet. Look at plates docs .

## Developpment

```bash
composer test
composer format
composer stan
composer rector
```

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Credits

- [Robin D. ak. Pied Web](https://piedweb.com) (Current Maintener)
- [RJ Garcia](https://github.com/ragboyjr) (Current Maintainer of `league/plates`)
- [Jonathan Reinink](https://github.com/reinink) (Original Author of `league/plates`)
- [All Contributors](https://github.com/piedweb/splates/contributors)

## License

The MIT License (MIT). Please see [License File](https://github.com/piedweb/splates/blob/master/LICENSE) for more information.
