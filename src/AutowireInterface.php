<?php

declare(strict_types=1);

namespace Jasny\Autowire;

/**
 * Interface for autowiring service
 */
interface AutowireInterface
{
    /**
     * Instantiate a new object
     *
     * @param string $class
     * @param mixed  ...$args
     * @return object
     * @throws AutowireException
     *
     * @template T of object
     * @phpstan-param class-string<T> $class
     * @phpstan-param mixed           ...$args
     * @phpstan-return T
     */
    public function instantiate(string $class, ...$args): object;

    /**
     * Must be an alias of the `instantiate` method
     *
     * @param string $class
     * @param mixed  ...$args
     * @return object
     * @throws AutowireException
     *
     * @template T of object
     * @phpstan-param class-string<T> $class
     * @phpstan-param mixed           ...$args
     * @phpstan-return T
     */
    public function __invoke(string $class, ...$args): object;
}
