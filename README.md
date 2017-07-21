# Slender Framework

[![License](https://poser.pugx.org/slim/slim/license)](https://packagist.org/packages/slim/slim)

Slender is a fork of the excellent [Slim Framework 3.x](https://github.com/slimphp/Slim) 

Slender is a PHP micro-framework that helps you quickly write simple yet powerful RESTful APIs.

These are the primary differences between Slim and Slender:
- PHP 7.1+ is an absolute requirement.
- Slender implements PHP 7.1 features where it makes sense to do so
  (scalar type declarations, scoped constants, spaceship operator, null coalesce operator, etc.).
- Slender removes the Pimple Dependency Injection (DI) in favor of PHP-DI.
- PHP-DI is "baked in" meaning that the code from [PHP-DI/Slim-Bridge (1.0.3)](https://github.com/PHP-DI/Slim-Bridge)
  is **HARD CODED** into Slender.
  
  This is necessary because Slim-Bridge extends Slim and (of course) not Slender.
- Slender tries (but does not guarantee) to keep up with changes to the Slim 3.x branch.
- Work is starting on Slim 4.x as features and bug fixes are added Slender may try to these if it makes sense to do so.

## Why use Slender instead of Slim?

Firstly Slim is recommended over Slender unless you want or need the following:

- PHP 7.1 robust coding standards are enforced:
    - The `declare(strict_types=1);` heads **every** Slender PHP file.
    - Scalar type hints and return types are declared in **every** method 
      unless it makes sense not to do so.
- Slender leverages other PHP 7.1 features:
    - Scoped constants
    - Null coalescing operator
    - Anonymous classes (used in unit tests)
    - `Closure::call()` (WIP)
    - Asserts as a language construct (WIP)
    - Group `use` declarations
    - `[]` Array destructuring
    - Negative string offsets
- Slender takes advantage of PHP-DI's auto-wiring and other 
Dependency Injection features.  


## Installation

It's recommended that you use [Composer](https://getcomposer.org/) to install Slender.

```bash
$ composer require ryannerd/slender "^1.0"
```

This will install Slender and all required dependencies. Slender requires PHP 7.1 or newer.

## Usage

Create an index.php file with the following contents:

```php
<?php
use Slender\App;

require 'vendor/autoload.php';

$app = new Slender\App();

$app->get('/hello/{name}', function ($request, $response, $args) {
    return $response->write("Hello, " . $args['name']);
});

$app->run();
```

You may quickly test this using the built-in PHP server:
```bash
$ php -S localhost:8000
```

Going to http://localhost:8000/hello/world will now display "Hello, world".

For more information on how to configure your web server, see the [Documentation](https://www.slimframework.com/docs/start/web-servers.html).

## Tests

To execute the test suite, you'll need phpunit.

```bash
$ phpunit
```

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Learn More

Learn more at these links:

- [Website](https://www.slimframework.com)
- [Documentation](https://www.slimframework.com/docs/start/installation.html)
- [Support Forum](http://discourse.slimframework.com)
- [Twitter](https://twitter.com/slimphp)
- [Resources](https://github.com/xssc/awesome-slim)

## Security

If you discover security related issues, please email security@slimframework.com instead of using the issue tracker.

## Credits
**Slender** 
- [Ryan Jentzsch](https://github.com/RyanNerd)

**Slim**
- [Josh Lockhart](https://github.com/codeguy)
- [Andrew Smith](https://github.com/silentworks)
- [Rob Allen](https://github.com/akrabat)
- [Gabriel Manricks](https://github.com/gmanricks)
- [All Contributors](../../contributors)

## License

The Slender Framework is licensed under the MIT license. See [License File](LICENSE.md) for more information.
