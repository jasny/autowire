<?php

namespace Jasny\Autowire;

/**
 * Interface for autowire service
 */
interface AutowireInterface
{
    /**
     * Instantiate a new object
     *
     * @param string $class
     * @return object
     */
    public function instantiate(string $class);

    /**
     * Must be an alias of the `instantiate` method
     *
     * @param string $class
     * @return object
     */
    public function __invoke($class);
}
