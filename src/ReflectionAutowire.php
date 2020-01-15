<?php

declare(strict_types=1);

namespace Jasny\Autowire;

use Jasny\ReflectionFactory\ReflectionFactory;
use Jasny\ReflectionFactory\ReflectionFactoryInterface;
use Psr\Container\ContainerInterface;

/**
 * Autowiring using reflection and annotations.
 */
class ReflectionAutowire implements AutowireInterface
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var ReflectionFactoryInterface
     */
    protected $reflection;

    /**
     * Class constructor.
     */
    public function __construct(ContainerInterface $container, ?ReflectionFactoryInterface $reflection = null)
    {
        $this->container = $container;
        $this->reflection = $reflection ?? new ReflectionFactory();
    }

    /**
     * Get container used for loading dependencies.
     */
    public function getContainer(): ContainerInterface
    {
        return $this->container;
    }


    /**
     * Assert that type can be used as container id.
     *
     * @throws AutowireException
     */
    protected function checkReflType(string $class, string $param, ?\ReflectionType $reflType): \ReflectionNamedType
    {
        if ($reflType === null || !$reflType instanceof \ReflectionNamedType) {
            throw new AutowireException("Unable to autowire {$class}: Unknown type for parameter '{$param}'.");
        }

        if ($reflType->isBuiltin()) {
            throw new AutowireException("Unable to autowire {$class}: "
                . "Build-in type '" . $reflType->getName() . "' for parameter '{$param}' can't be used as container "
                . "id. Please use annotations.");
        }

        return $reflType;
    }

    /**
     * Get annotations for the constructor parameters.
     * Annotated parameter types are not considered. Turning the class to a FQCN is more work than it's worth.
     *
     * @param string $docComment
     * @return array<string|null>
     */
    protected function extractParamAnnotations(string $docComment): array
    {
        $pattern = '/@param(?:\s+([^$"]\S+))?(?:\s+\$(\w+))?(?:\s+"([^"]++)")?/';

        if (!(bool)preg_match_all($pattern, $docComment, $matches, PREG_SET_ORDER)) {
            return [];
        }

        $annotations = [];

        foreach ($matches as $index => $match) {
            $annotations[$index] = isset($match[3]) && $match[3] !== '' ? $match[3] : null;
        }

        return $annotations;
    }

    /**
     * Get the declared type of a parameter.
     *
     * @template T of object
     * @phpstan-param \ReflectionClass<T>  $class
     * @phpstan-param \ReflectionParameter $param
     * @phpstan-return string
     */
    protected function getParamType(\ReflectionClass $class, \ReflectionParameter $param): string
    {
        $type = $this->checkReflType($class->getName(), $param->getName(), $param->getType());

        return $type->getName();
    }

    /**
     * Get all dependencies for a class constructor.
     *
     * @param \ReflectionClass $class
     * @param int              $skip   Number of parameters to skip
     * @return array[]
     * @throws \ReflectionException
     *
     * @template T of object
     * @phpstan-param \ReflectionClass<T> $class
     * @phpstan-param int                 $skip
     * @phpstan-return array<array{key:string,optional:bool}>
     */
    protected function determineDependencies(\ReflectionClass $class, int $skip): array
    {
        if (!$class->hasMethod('__construct')) {
            return [];
        }

        $constructor = $class->getMethod('__construct');
        $docComment = $constructor->getDocComment();
        $annotations = is_string($docComment) ? $this->extractParamAnnotations($docComment) : [];

        $identifiers = [];

        $params = $constructor->getParameters();
        $consideredParams = $skip === 0 ? $params : array_slice($params, $skip, null, true);

        foreach ($consideredParams as $index => $param) {
            $identifiers[$index] = [
                'key' => $annotations[$index] ?? $this->getParamType($class, $param),
                'optional' => $param->allowsNull(),
            ];
        }

        return $identifiers;
    }

    /**
     * Get dependencies from the container.
     *
     * @param array[] $identifiers
     * @return mixed[]
     *
     * @phpstan-param array<array{key:string,optional:bool}> $identifiers
     * @phpstan-return mixed[]
     */
    protected function getDependencies(array $identifiers): array
    {
        $dependencies = [];

        foreach ($identifiers as $index => $identifier) {
            $dependencies[$index] = !$identifier['optional'] || $this->container->has($identifier['key'])
                ? $this->container->get($identifier['key'])
                : null;
        };

        return $dependencies;
    }

    /**
     * Instantiate a new object.
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
    public function instantiate(string $class, ...$args): object
    {
        try {
            /** @phpstan-var \ReflectionClass<T> $refl */
            $refl = $this->reflection->reflectClass($class);

            $dependencyIds = $this->determineDependencies($refl, count($args));
            $dependencies = $args + $this->getDependencies($dependencyIds);
        } catch (\ReflectionException $exception) {
            throw new AutowireException("Unable to autowire {$class}", 0, $exception);
        }

        return $refl->newInstanceArgs($dependencies);
    }

    /**
     * @inheritDoc
     */
    final public function __invoke(string $class, ...$args): object
    {
        return $this->instantiate($class, ...$args);
    }
}
