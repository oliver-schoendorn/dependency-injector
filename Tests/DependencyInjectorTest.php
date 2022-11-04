<?php
namespace OS\DependencyInjector\Tests;


use OS\DependencyInjector\DependencyInjector;
use OS\DependencyInjector\ReflectionHandler;
use OS\DependencyInjector\ReflectionHandlerInterface;
use OS\DependencyInjector\Tests\Helper\TestClass01;
use OS\DependencyInjector\Tests\Helper\TestClass02;
use OS\DependencyInjector\Tests\Helper\TestClass03;
use OS\DependencyInjector\Tests\Helper\TestClass04;
use OS\DependencyInjector\Tests\Helper\TestClass05;
use OS\DependencyInjector\Tests\Helper\TestClassCircular01;
use OS\DependencyInjector\Tests\Helper\TestInterface;
use OS\DependencyInjector\Tests\Helper\TestInterfaceImplementation;
use OS\DependencyInjector\Tests\Helper\CallableClass;
use OS\DependencyInjector\Tests\Helper\CallableClassWithInvoke;
use OS\DependencyInjector\Tests\Helper\CallableStaticClass;
use OS\DependencyInjector\Tests\Helper\ClassAccessor;
use PHPUnit\Framework\TestCase;
use function verify;
use const DIRECTORY_SEPARATOR;

class DependencyInjectorTest extends TestCase
{
    private ReflectionHandlerInterface $handler;
    
    public function setUp(): void
    {
        $this->handler = new ReflectionHandler();
    }

    public function testShare()
    {
        verify($this->handler)->instanceOf(ReflectionHandlerInterface::class);
        $instance = new TestClass01();
        $di = new DependencyInjector($this->handler);
        $accessor = new ClassAccessor($di);

        verify($di->share($instance))->same($di);

        $shares = $accessor->getProperty('sharedInstances');
        verify(isset($shares[TestClass01::class]))->true();
        verify($shares[TestClass01::class])->same($instance);
    }

    public function testShareWithAlias()
    {
        verify($this->handler)->instanceOf(ReflectionHandlerInterface::class);
        $alias = 'a random alias';
        $instance = new TestClass01();
        $di = new DependencyInjector($this->handler);
        $accessor = new ClassAccessor($di);

        verify($di->share($instance, $alias))->same($di);

        $shares = $accessor->getProperty('sharedInstances');
        verify(isset($shares[TestClass01::class]))->true();
        verify($shares[TestClass01::class])->same($instance);

        $aliases = $accessor->getProperty('aliases');
        verify(isset($aliases[$alias]))->true();
        verify($aliases[$alias])->equals(TestClass01::class);
    }

    public function testDelegate()
    {
        $delegate = function() {};

        $di = new DependencyInjector($this->handler);
        $accessor = new ClassAccessor($di);

        verify($di->delegate(TestClass01::class, $delegate))->same($di);

        $delegates = $accessor->getProperty('delegates')[TestClass01::class];
        verify($delegates)->same($delegate);
    }

    public function testAlias()
    {
        $di = new DependencyInjector($this->handler);
        $accessor = new ClassAccessor($di);

        verify($di->alias(TestClass01::class, TestClass02::class))->same($di);

        $alias = $accessor->getProperty('aliases')[TestClass01::class];
        verify($alias)->equals(TestClass02::class);
    }

    public function testInterfaceSubstitution()
    {
        $di = new DependencyInjector($this->handler);

        $di->alias(TestInterface::class, TestInterfaceImplementation::class);
        $instance = $di->resolve(TestInterface::class);

        verify($instance)->instanceOf(TestInterface::class);
        verify($instance)->instanceOf(TestInterfaceImplementation::class);
    }

    public function testSharedDelegate()
    {
        $di = new DependencyInjector($this->handler);

        $sharedInstance = new TestInterfaceImplementation();
        $di->share($sharedInstance, TestInterface::class);

        verify($di->resolve(TestInterface::class))->same($sharedInstance);
        verify($di->resolve(TestInterfaceImplementation::class))->same($sharedInstance);
    }

    public function testConfigure()
    {
        $arguments = [ ':optional' => TestClass05::class ];
        $di = new DependencyInjector($this->handler);

        $response = $di->configure(TestClass01::class, $arguments);
        $instance = $di->resolve(TestClass01::class);

        verify($response)->same($di);
        verify($instance->constructorArgument)->instanceOf(TestClass05::class);
    }

    public function testConfigureCanBeOverwrittenWithPlainKey()
    {
        $arguments = [ ':optional' => TestClass03::class ];
        $di = new DependencyInjector($this->handler);

        $response = $di->configure(TestClass01::class, $arguments);
        $instance = $di->resolve(TestClass01::class, [ 'optional' => 'bar' ]);

        verify($response)->same($di);
        verify($instance->constructorArgument)->equals('bar');
    }

    public function testConfigureCanBeOverwrittenWithClassKey()
    {
        $arguments = [ ':optional' => TestClass03::class ];
        $di = new DependencyInjector($this->handler);

        $response = $di->configure(TestClass01::class, $arguments);
        $instance = $di->resolve(TestClass01::class,
            [ ':optional' => TestClass04::class, 'requiredParam' => true ]);

        verify($response)->same($di);
        verify($instance->constructorArgument)->instanceOf(TestClass04::class);
    }

    public function testResolveSharedInstance()
    {
        $instance = new TestClass01();
        $di = new DependencyInjector($this->handler);
        $di->share($instance);

        $actualInstance = $di->resolve(TestClass01::class);
        verify($actualInstance)->same($instance);
    }

    public function testResolveSharedAndAliasedInstance()
    {
        $instance = new TestClass01();
        $di = new DependencyInjector($this->handler);
        $di->alias('ClassAlias', TestClass01::class);
        $di->share($instance);

        $actualInstance = $di->resolve('ClassAlias');
        verify($actualInstance)->same($instance);
    }

    public function testResolveAliasedInstance()
    {
        $di = new DependencyInjector($this->handler);
        $di->alias('foo', TestClass01::class);

        verify($di->resolve('foo'))->instanceOf(TestClass01::class);
    }

    public function testResolveWithSimpleDependency()
    {
        $di = new DependencyInjector($this->handler);
        $instance = $di->resolve(TestClass02::class);

        verify($instance)->instanceOf(TestClass02::class);
        verify($instance->constructorArguments['test'])->instanceOf(TestClass01::class);
        verify($instance->constructorArguments['test']->didCallConstructor)->equals(true);
        verify($instance->constructorArguments['foo'])->equals('empty');
        verify($instance->constructorArguments['bar'])->equals(12);
    }

    public function testResolveWithSimpleDependencyAndSimpleArguments()
    {
        $di = new DependencyInjector($this->handler);
        $instance = $di->resolve(TestClass02::class, [ 'foo' => 'not empty', 'bar' => 23]);

        verify($instance)->instanceOf(TestClass02::class);
        verify($instance->constructorArguments['test'])->instanceOf(TestClass01::class);
        verify($instance->constructorArguments['test']->didCallConstructor)->equals(true);
        verify($instance->constructorArguments['foo'])->equals('not empty');
        verify($instance->constructorArguments['bar'])->equals(23);
    }

    public function testResolveWithComplexArguments()
    {
        $di = new DependencyInjector($this->handler);
        $instance = $di->resolve(TestClass02::class, [ 'foo' => TestClass01::class ]);
        verify($instance->constructorArguments['test'])->instanceOf(TestClass01::class);
        verify($instance->constructorArguments['test']->didCallConstructor)->equals(true);
        verify($instance->constructorArguments['foo'])->equals(TestClass01::class);
        verify($instance->constructorArguments['bar'])->equals(12);
    }

    public function testResolveWillPassArgumentsToChildDependencies()
    {
        $di = new DependencyInjector($this->handler);
        $instance = $di->resolve(TestClass02::class, [ 'optional' => 'value' ]);
        verify($instance->constructorArguments['test'])->instanceOf(TestClass01::class);
        verify($instance->constructorArguments['test']->didCallConstructor)->equals(true);
        verify($instance->constructorArguments['test']->constructorArgument)->equals('value');
        verify($instance->constructorArguments['foo'])->equals('empty');
        verify($instance->constructorArguments['bar'])->equals(12);
    }

    public function testResolveWithDelegate()
    {
        $di = new DependencyInjector($this->handler);
        $instance = new class {};
        $di->delegate('foo', function(DependencyInjector $injector, string $classId, array $arguments = []) use ($di, $instance)
        {
            verify($injector)->same($di);
            verify($classId)->equals('foo');
            verify($arguments)->equals([ 'test' => 'argument' ]);

            return $instance;
        });
        $response = $di->resolve('foo', ['test' => 'argument']);
        verify($response)->same($instance);
    }

    public function testResolveWithValidClassIdAsArguments()
    {
        $di = new DependencyInjector($this->handler);
        $instance = $di->resolve(TestClass02::class, [ ':test' => TestClass03::class ]);
        verify($instance->constructorArguments['test'])->instanceOf(TestClass03::class);
        verify($instance->constructorArguments['test']->didCallConstructor)->equals(true);
        verify($instance->constructorArguments['foo'])->equals('empty');
        verify($instance->constructorArguments['bar'])->equals(12);
    }

    public function testResolveWithInvalidClassIdAsArguments()
    {
        $this->expectException('TypeError');
        $di = new DependencyInjector($this->handler);
        $di->resolve(TestClass02::class, [ 'test' => TestClass01::class ]);
    }

    public function testResolveCircularDependency()
    {
        $this->expectException('RuntimeException');
        $di = new DependencyInjector($this->handler);
        $di->resolve(TestClassCircular01::class);
    }

    public function testResolveWithMissingArgument()
    {
        $this->expectException('InvalidArgumentException');
        $di = new DependencyInjector($this->handler);
        $di->resolve(TestClass04::class);
    }

    public function invocationDataProvider(): array
    {
        $helperDir = __DIR__ . DIRECTORY_SEPARATOR . 'Helper';
        $callableFile = $helperDir . DIRECTORY_SEPARATOR . 'callable.php';
        require_once $callableFile;

        global $closure;

        return array_map(function($value) { return [ $value ]; }, [
            'emptyFunc' => function(string $foo) { return true; },
            'object' => [(new CallableClass()), 'callableMethod'],
            'globalFunc' => 'OS\DependencyInjector\Tests\Helper\callableFunction',
            'staticString' => CallableStaticClass::class . '::callableStaticMethod',
            'staticArray' => [CallableStaticClass::class, 'callableStaticMethod'],
            'array' => [ CallableClass::class, 'callableMethod' ],
            'instanceInvoke' => new CallableClassWithInvoke(),
            'closureInvoke' => $closure,
            // -> Only supported by DI
            'stringInvoke' => CallableClassWithInvoke::class,
        ]);
    }

    /**
     * @dataProvider invocationDataProvider
     * @param callable|string|array $callable
     */
    public function testInvoke($callable)
    {
        $di = new DependencyInjector($this->handler);
        verify($di->invoke($callable, [ ':foo' => function() { return 'bar'; } ]))->equals(true);
    }
}
