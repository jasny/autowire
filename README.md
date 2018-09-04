Jasny Autowire
===

[![Build Status](https://travis-ci.org/jasny/autowire.svg?branch=master)](https://travis-ci.org/jasny/autowire)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/jasny/autowire/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/jasny/autowire/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/jasny/autowire/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/jasny/autowire/?branch=master)
[![SensioLabsInsight](https://insight.sensiolabs.com/projects/6c5ec45d-5570-4e50-87ce-39cabc237f2b/mini.png)](https://insight.sensiolabs.com/projects/6c5ec45d-5570-4e50-87ce-39cabc237f2b)
[![Packagist Stable Version](https://img.shields.io/packagist/v/jasny/autowire.svg)](https://packagist.org/packages/jasny/autowire)
[![Packagist License](https://img.shields.io/packagist/l/jasny/autowire.svg)](https://packagist.org/packages/jasny/autowire)

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
use Jasny\Autowire\ReflectionAutowire();

$autowire = new ReflectionAutowire($container);

$foo = $autowire->instantiate(Foo::class);
// OR
$foo = $autowire(Foo::class);
```

_The library works with any PSR-11 compatible container, not just [jasny\container](https://github.com/jasny/container)._


It also parses the [doc comment](http://php.net/reflectionclass.getdoccomment) and can get entry name
from `@param`. Entry names must be the first part of the description and surrounded by double quotes.

```php
class Bar
{
    /**
     * Class constructor
     *
     * @param string              $color       "config:bar_color"
     * @param ConnectionInterface $connection  "db_connections.default"
     */
    public function __construct(string $color, ConnectionInterface $connection)
    {
        // ...
    }
}
```

_The type from `@param` is not considered. If the type is a single interface (or class), there is little reason not to
use type hints in the method parameters. Parsing them is difficult, because it requires converting a class to a
fully-qualified-class-name (FQCN), which requires looking at the namespace and `use` statements._

This library deliberately doesn't support autowiring for properties or methods. Please explicitly call those methods in
the container function or use an abstract factory.

### Non-wired parameters

Additional arguments in `instantiate()` are passed directly to the constructor. No autowiring is applied to these
parameters.  

```php
class Bar
{
    /**
     * Class constructor
     *
     * @param string              $color       A color
     * @param ConnectionInterface $connection  "db_connections.default"
     */
    public function __construct(string $color, ConnectionInterface $connection)
    {
        // ...
    }
}
```

```php
use Jasny\Autowire\ReflectionAutowire();

$autowire = new ReflectionAutowire($container);

$foo = $autowire->instantiate(Foo::class, 'blue');
```

_The constructor MUST begin with these parameters. It's not possible to cherry-pick the parameters than need to be
autowired._
