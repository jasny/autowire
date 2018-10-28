<?php declare(strict_types=1);

namespace Jasny\Autowire;

/**
 * Interface for autowire service
 */
interface Autowire
{
    /**
     * Instantiate a new object
     *
     * @param string $class
     * @param mixed  ...$args
     * @return object
     */
    public function instantiate(string $class, ...$args);

    /**
     * Must be an alias of the `instantiate` method
     *
     * @param string $class
     * @param mixed  ...$args
     * @return object
     */
    public function __invoke($class, ...$args);
}
