<?php declare(strict_types=1);

namespace Jasny\Autowire\Tests;

use Jasny\Autowire\ReflectionAutowire;
use Jasny\Autowire\Tests\Support\ConnectionInterface;
use Jasny\Autowire\Tests\Support\ValidationInterface;
use Jasny\Autowire\Tests\Support\Foo;
use Jasny\Autowire\Tests\Support\Bar;
use Jasny\ReflectionFactory\ReflectionFactory;
use Jasny\ReflectionFactory\ReflectionFactoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Container\ContainerInterface;
use PHPUnit\Framework\TestCase;

class ReflectionAutowireTest extends TestCase
{
    public function testConstruct()
    {
        $container = $this->createMock(ContainerInterface::class);
        $autowire = new ReflectionAutowire($container);

        $this->assertAttributeSame($container, 'container', $autowire);
        $this->assertAttributeInstanceOf(ReflectionFactory::class, 'reflection', $autowire);
    }

    /**
     * @return MockObject|\ReflectionClass
     */
    protected function createReflectionClassMock(string $class, $docComment, array $params)
    {
        $reflParams = [];

        foreach ($params as $name => $type) {
            $reflType = isset($type) ? $this->createConfiguredMock(\ReflectionNamedType::class,
                ['getName' => $type, 'isBuiltin' => $type === 'string']) : null;

            $reflParams[] = $this->createConfiguredMock(\ReflectionParameter::class,
                ['getName' => $name, 'getType' => $reflType]);
        }

        $reflConstruct = $this->createMock(\ReflectionMethod::class);
        $reflConstruct->method('getDocComment')->willReturn($docComment);
        $reflConstruct->method('getParameters')->willReturn($reflParams);

        $reflClass = $this->createMock(\ReflectionClass::class);
        $reflClass->method('hasMethod')->with('__construct')->willReturn(true);
        $reflClass->method('getMethod')->with('__construct')->willReturn($reflConstruct);

        $reflClass->method('getName')->willReturn($class);

        return $reflClass;
    }

    public function docCommentProvider()
    {
        return [
            //[false],
            //[''],
            ["/**\n * Lorem ipsum\n */"]
        ];
    }

    /**
     * @dataProvider docCommentProvider
     */
    public function testInstantiate($docComment)
    {
        $color = (object)[];
        $hue = (object)[];
        $foo = (object)[];

        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->exactly(2))->method('get')
            ->withConsecutive(['ColorInterface'], ['HueInterface'])
            ->willReturnOnConsecutiveCalls($color, $hue);

        $reflClass = $this->createReflectionClassMock('Foo', $docComment,
            ['color' => 'ColorInterface', 'hue' => 'HueInterface']);
        $reflClass->expects($this->once())->method('newInstanceArgs')
            ->with($this->identicalTo([$color, $hue]))
            ->willReturn($foo);

        $reflection = $this->createMock(ReflectionFactoryInterface::class);
        $reflection->expects($this->once())->method('reflectClass')->with('Foo')->willReturn($reflClass);

        $autowire = new ReflectionAutowire($container, $reflection);
        $result = $autowire->instantiate('Foo');

        $this->assertSame($foo, $result);
    }

    public function testInstantiateAnnotations()
    {
        $color = (object)[];
        $hue = 22;
        $foo = (object)[];

        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->exactly(2))->method('get')
            ->withConsecutive(['ColorInterface'], ['config.hue'])
            ->willReturnOnConsecutiveCalls($color, $hue);

        $docComment = <<<DOC_COMMENT
/**
 * Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed lacinia tellus ut dui blandit, at pretium sapien
 * pharetra. In ut nibh est. Donec auctor dolor a dolor aliquam accumsan.
 * @see https://jasny.net/
 *
 * @param ColorInterface \$color
 * @param int|string     \$hue    "config.hue"  The hue setting
 */
DOC_COMMENT;

        $reflClass = $this->createReflectionClassMock('Foo', $docComment,
            ['color' => 'ColorInterface', 'hue' => null]);
        $reflClass->expects($this->once())->method('newInstanceArgs')
            ->with($this->identicalTo([$color, $hue]))
            ->willReturn($foo);

        $reflection = $this->createMock(ReflectionFactoryInterface::class);
        $reflection->expects($this->once())->method('reflectClass')->with('Foo')->willReturn($reflClass);

        $autowire = new ReflectionAutowire($container, $reflection);
        $result = $autowire->instantiate('Foo');

        $this->assertSame($foo, $result);
    }

    public function testInstantiateParams()
    {
        $hue = 22;
        $foo = (object)[];

        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->once())->method('get')->with('config.hue')->willReturn($hue);

        $docComment = <<<DOC_COMMENT
/**
 * Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed lacinia tellus ut dui blandit, at pretium sapien
 * pharetra. In ut nibh est. Donec auctor dolor a dolor aliquam accumsan.
 * @see https://jasny.net/
 *
 * @param string     \$color
 * @param int|string \$hue    "config.hue"  The hue setting
 */
DOC_COMMENT;

        $reflClass = $this->createReflectionClassMock('Foo', $docComment, ['color' => null, 'hue' => null]);
        $reflClass->expects($this->once())->method('newInstanceArgs')
            ->with($this->identicalTo(['blue', $hue]))
            ->willReturn($foo);

        $reflection = $this->createMock(ReflectionFactoryInterface::class);
        $reflection->expects($this->once())->method('reflectClass')->with('Foo')->willReturn($reflClass);

        $autowire = new ReflectionAutowire($container, $reflection);
        $result = $autowire->instantiate('Foo', 'blue');

        $this->assertSame($foo, $result);
    }
    public function testInstantiateNoConstructor()
    {
        $foo = (object)[];

        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->never())->method('get');

        $reflClass = $this->createMock(\ReflectionClass::class);
        $reflClass->method('hasMethod')->with('__construct')->willReturn(false);
        $reflClass->method('getName')->willReturn('Foo');

        $reflClass->expects($this->once())->method('newInstanceArgs')->with([])->willReturn($foo);

        $reflection = $this->createMock(ReflectionFactoryInterface::class);
        $reflection->expects($this->once())->method('reflectClass')->with('Foo')->willReturn($reflClass);

        $autowire = new ReflectionAutowire($container, $reflection);
        $result = $autowire->instantiate('Foo');

        $this->assertSame($foo, $result);
    }

    /**
     * @expectedException \Jasny\Autowire\AutowireException
     * @expectedExceptionMessage Unable to autowire Foo: Unknown type for parameter 'hue'.
     */
    public function testInstantiateUnknownType()
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->never())->method('get');

        $reflClass = $this->createReflectionClassMock('Foo', false, ['color' => 'ColorInterface', 'hue' => null]);
        $reflClass->expects($this->never())->method('newInstanceArgs');

        $reflection = $this->createMock(ReflectionFactoryInterface::class);
        $reflection->expects($this->once())->method('reflectClass')->with('Foo')->willReturn($reflClass);

        $autowire = new ReflectionAutowire($container, $reflection);
        $autowire->instantiate('Foo');
    }

    /**
     * @expectedException \Jasny\Autowire\AutowireException
     * @expectedExceptionMessage Build-in type 'string' for parameter 'hue' can't be used as container id. Please use annotations.
     */
    public function testInstantiateBuiltinType()
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->never())->method('get');

        $reflClass = $this->createReflectionClassMock('Foo', false, ['color' => 'ColorInterface', 'hue' => 'string']);
        $reflClass->expects($this->never())->method('newInstanceArgs');

        $reflection = $this->createMock(ReflectionFactoryInterface::class);
        $reflection->expects($this->once())->method('reflectClass')->with('Foo')->willReturn($reflClass);

        $autowire = new ReflectionAutowire($container, $reflection);
        $autowire->instantiate('Foo');
    }

    public function testInvoke()
    {
        $object = new \stdClass();

        $autowire = $this->createPartialMock(ReflectionAutowire::class, ['instantiate']);
        $autowire->expects($this->once())->method('instantiate')->with('stdClass')->willReturn($object);

        $autowire->__invoke('stdClass');
    }
}
