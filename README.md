Jasny autowire
===

[![Build Status](https://travis-ci.org/jasny/autowire.svg?branch=master)](https://travis-ci.org/jasny/{{library}})
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/jasny/autowire/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/jasny/{{library}}/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/jasny/autowire/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/jasny/{{library}}/?branch=master)
[![SensioLabsInsight](https://insight.sensiolabs.com/projects/6c5ec45d-5570-4e50-87ce-39cabc237f2b/mini.png)](https://insight.sensiolabs.com/projects/6c5ec45d-5570-4e50-87ce-39cabc237f2b)
[![BCH compliance](https://bettercodehub.com/edge/badge/jasny/autowire?branch=master)](https://bettercodehub.com/)
[![Packagist Stable Version](https://img.shields.io/packagist/v/jasny/autowire.svg)](https://packagist.org/packages/jasny/{{library}})
[![Packagist License](https://img.shields.io/packagist/l/jasny/autowire.svg)](https://packagist.org/packages/jasny/{{library}})

Instantiate an object (instead of using `new`), automatically determining the dependencies and getting them from a PSR-11 container.

Installation
---

    composer require jasny/autowire

Usage
---

The `ReflectionAutowire` implementation using reflection to determine the type of type constructor parameters.


```php
class Foo
{
    public function __construct(ColorInterface $color)
    {
        // ...
    }
}
```

Create a new `Foo` object with autowiring:

```php
use Jasny\Container;
use Jasny\Autowire\ReflectionAutowire();

$autowire = new ReflectionAutowire($container);

$foo = $autowire->instantiate(Foo::class);
// OR
$foo = $autowire(Foo::class);
```

_The library works with any PSR-11 compatible container, not just [jasny\container](https://github.com/jasny/container)._


It also parses the [doc comment](http://php.net/reflectionclass.getdoccomment) and can get either type or entry name
from `@param`. Entry names must be the first part of the description and surrounded by double quotes.

```php
class Bar
{
    /**
     * Class constructor
     *
     * @param ConnectionInterface $connection  "default-db-connection"
     * @param BarValidation       $validation
     */
    public function __construct(ConnectionInterface $connection, ValidationInterface $validation)
    {
        // ...
    }
}
```

This library deliberately doesn't support autowiring for properties or methods. Please explicitly call those methods in
the container function or use an abstract factory.

