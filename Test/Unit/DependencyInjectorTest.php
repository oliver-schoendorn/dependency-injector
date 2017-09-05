<?php
namespace OS\DependencyInjector\Test;


use Codeception\Configuration;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Monolog\Processor\PsrLogMessageProcessor;
use OS\DependencyInjector\DependencyInjector;
use OS\DependencyInjector\ReflectionHandler;
use OS\DependencyInjector\ReflectionHandlerInterface;
use OS\DependencyInjector\Test\_support\Helper\TestClass01;
use OS\DependencyInjector\Test\_support\Helper\TestClass02;
use OS\DependencyInjector\Test\_support\Helper\TestClass03;
use OS\DependencyInjector\Test\_support\Helper\TestClass04;
use OS\DependencyInjector\Test\_support\Helper\TestClass05;
use OS\DependencyInjector\Test\_support\Helper\TestClassCircular01;
use OS\DependencyInjector\Test\Helper\CallableClass;
use OS\DependencyInjector\Test\Helper\CallableClassWithInvoke;
use OS\DependencyInjector\Test\Helper\CallableStaticClass;
use OS\DependencyInjector\Test\Helper\ClassAccessor;
use Psr\Log\LoggerInterface;

class DependencyInjectorTest extends \Codeception\Test\Unit
{
    /**
     * @var \OS\DependencyInjector\Test\UnitTester
     */
    protected $tester;

    /**
     * @var ReflectionHandlerInterface
     */
    private $handler;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var Logger
     */
    private static $testLogger;

    public static function setUpBeforeClass()
    {
        $outputDir = codecept_output_dir();
        $logFile = $outputDir . DIRECTORY_SEPARATOR . 'DependencyInjector.log';

        $handler = new StreamHandler(fopen($logFile, 'w'));
        static::$testLogger = new \Monolog\Logger('', [ $handler ]);
        static::$testLogger->pushProcessor(new PsrLogMessageProcessor());

        static::$testLogger->useMicrosecondTimestamps(true);
        static::$testLogger->notice('Started unit test');

        parent::setUpBeforeClass();
    }

    public function _before()
    {
        $this->logger = static::$testLogger;
        $this->logger->notice('START {testName}', [ 'testName' => $this->getName() ]);
        $this->handler = new ReflectionHandler();
    }

    public function _after()
    {
        $this->logger->notice('FINISH {testName}' . PHP_EOL, [ 'testName' => $this->getName() ]);
        $this->logger->notice('=============================================================================');
    }

    public function testShare()
    {
        verify($this->handler)->isInstanceOf(ReflectionHandlerInterface::class);
        $instance = new TestClass01();
        $di = new DependencyInjector($this->handler, $this->logger);
        $accessor = new ClassAccessor($di);

        verify($di->share($instance))->same($di);

        $shares = $accessor->getProperty('sharedInstances');
        verify(isset($shares[TestClass01::class]))->true();
        verify($shares[TestClass01::class])->same($instance);
    }

    public function testShareWithAlias()
    {
        verify($this->handler)->isInstanceOf(ReflectionHandlerInterface::class);
        $alias = 'a random alias';
        $instance = new TestClass01();
        $di = new DependencyInjector($this->handler, $this->logger);
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

        $di = new DependencyInjector($this->handler, $this->logger);
        $accessor = new ClassAccessor($di);

        verify($di->delegate(TestClass01::class, $delegate))->same($di);

        $delegates = $accessor->getProperty('delegates')[TestClass01::class];
        verify($delegates)->same($delegate);
    }

    public function testAlias()
    {
        $di = new DependencyInjector($this->handler, $this->logger);
        $accessor = new ClassAccessor($di);

        verify($di->alias(TestClass01::class, TestClass02::class))->same($di);

        $alias = $accessor->getProperty('aliases')[TestClass02::class];
        verify($alias)->equals(TestClass01::class);
    }

    public function testConfigure()
    {
        $arguments = [ ':optional' => TestClass05::class ];
        $di = new DependencyInjector($this->handler, $this->logger);

        $response = $di->configure(TestClass01::class, $arguments);
        $instance = $di->resolve(TestClass01::class);

        verify($response)->same($di);
        verify($instance->constructorArgument)->isInstanceOf(TestClass05::class);
    }

    public function testConfigureCanBeOverwrittenWithPlainKey()
    {
        $arguments = [ ':optional' => TestClass03::class ];
        $di = new DependencyInjector($this->handler, $this->logger);

        $response = $di->configure(TestClass01::class, $arguments);
        $instance = $di->resolve(TestClass01::class, [ 'optional' => 'bar' ]);

        verify($response)->same($di);
        verify($instance->constructorArgument)->equals('bar');
    }

    public function testConfigureCanBeOverwrittenWithClassKey()
    {
        $arguments = [ ':optional' => TestClass03::class ];
        $di = new DependencyInjector($this->handler, $this->logger);

        $response = $di->configure(TestClass01::class, $arguments);
        $instance = $di->resolve(TestClass01::class,
            [ ':optional' => TestClass04::class, 'requiredParam' => true ]);

        verify($response)->same($di);
        verify($instance->constructorArgument)->isInstanceOf(TestClass04::class);
    }

    public function testResolveSharedInstance()
    {
        $instance = new TestClass01();
        $di = new DependencyInjector($this->handler, $this->logger);
        $di->share($instance);

        $actualInstance = $di->resolve(TestClass01::class);
        verify($actualInstance)->same($instance);
    }

    public function testResolveSharedAndAliasedInstance()
    {
        $instance = new TestClass01();
        $di = new DependencyInjector($this->handler, $this->logger);
        $di->alias(TestClass01::class, 'ClassAlias');
        $di->share($instance);

        $actualInstance = $di->resolve('ClassAlias');
        verify($actualInstance)->same($instance);
    }

    public function testResolveAliasedInstance()
    {
        $di = new DependencyInjector($this->handler, $this->logger);
        $di->alias(TestClass01::class, 'foo');

        verify($di->resolve('foo'))->isInstanceOf(TestClass01::class);
    }

    public function testResolveWithSimpleDependency()
    {
        $di = new DependencyInjector($this->handler, $this->logger);
        $instance = $di->resolve(TestClass02::class);

        verify($instance)->isInstanceOf(TestClass02::class);
        verify($instance->constructorArguments['test'])->isInstanceOf(TestClass01::class);
        verify($instance->constructorArguments['test']->didCallConstructor)->equals(true);
        verify($instance->constructorArguments['foo'])->equals('empty');
        verify($instance->constructorArguments['bar'])->equals(12);
    }

    public function testResolveWithSimpleDependencyAndSimpleArguments()
    {
        $di = new DependencyInjector($this->handler, $this->logger);
        $instance = $di->resolve(TestClass02::class, [ 'foo' => 'not empty', 'bar' => 23]);

        verify($instance)->isInstanceOf(TestClass02::class);
        verify($instance->constructorArguments['test'])->isInstanceOf(TestClass01::class);
        verify($instance->constructorArguments['test']->didCallConstructor)->equals(true);
        verify($instance->constructorArguments['foo'])->equals('not empty');
        verify($instance->constructorArguments['bar'])->equals(23);
    }

    public function testResolveWithComplexArguments()
    {
        $di = new DependencyInjector($this->handler, $this->logger);
        $instance = $di->resolve(TestClass02::class, [ 'foo' => TestClass01::class ]);
        verify($instance->constructorArguments['test'])->isInstanceOf(TestClass01::class);
        verify($instance->constructorArguments['test']->didCallConstructor)->equals(true);
        verify($instance->constructorArguments['foo'])->equals(TestClass01::class);
        verify($instance->constructorArguments['bar'])->equals(12);
    }

    public function testResolveWillPassArgumentsToChildDependencies()
    {
        $di = new DependencyInjector($this->handler, $this->logger);
        $instance = $di->resolve(TestClass02::class, [ 'optional' => 'value' ]);
        verify($instance->constructorArguments['test'])->isInstanceOf(TestClass01::class);
        verify($instance->constructorArguments['test']->didCallConstructor)->equals(true);
        verify($instance->constructorArguments['test']->constructorArgument)->equals('value');
        verify($instance->constructorArguments['foo'])->equals('empty');
        verify($instance->constructorArguments['bar'])->equals(12);
    }

    public function testResolveWithDelegate()
    {
        $di = new DependencyInjector($this->handler, $this->logger);
        $di->delegate('foo', function(DependencyInjector $injector, string $classId, array $arguments = []) use ($di)
        {
            verify($injector)->same($di);
            verify($classId)->equals('foo');
            verify($arguments)->equals([ 'test' => 'argument' ]);

            return 123456;
        });
        $response = $di->resolve('foo', ['test' => 'argument']);
        verify($response)->equals(123456);
    }

    public function testResolveWithValidClassIdAsArguments()
    {
        $di = new DependencyInjector($this->handler, $this->logger);
        $instance = $di->resolve(TestClass02::class, [ ':test' => TestClass03::class ]);
        verify($instance->constructorArguments['test'])->isInstanceOf(TestClass03::class);
        verify($instance->constructorArguments['test']->didCallConstructor)->equals(true);
        verify($instance->constructorArguments['foo'])->equals('empty');
        verify($instance->constructorArguments['bar'])->equals(12);
    }

    public function testResolveWithInvalidClassIdAsArguments()
    {
        $this->expectException('TypeError');
        $di = new DependencyInjector($this->handler, $this->logger);
        $di->resolve(TestClass02::class, [ 'test' => TestClass01::class ]);
    }

    public function testResolveCircularDependency()
    {
        $this->expectException('RuntimeException');
        $di = new DependencyInjector($this->handler, $this->logger);
        $di->resolve(TestClassCircular01::class);
    }

    public function testResolveWithMissingArgument()
    {
        $this->expectException('InvalidArgumentException');
        $di = new DependencyInjector($this->handler, $this->logger);
        $di->resolve(TestClass04::class);
    }

    public function invocationDataProvider()
    {
        $helperDir = Configuration::supportDir() . DIRECTORY_SEPARATOR . 'Helper';
        $callableFile = $helperDir . DIRECTORY_SEPARATOR . 'callable.php';
        require_once $callableFile;

        global $closure;

        return array_map(function($value) { return [ $value ]; }, [
            'emptyFunc' => function(string $foo) { return true; },
            'object' => [(new CallableClass()), 'callableMethod'],
            'globalFunc' => 'OS\DependencyInjector\Test\Helper\callableFunction',
            'staticString' => CallableStaticClass::class . '::callableStaticMethod',
            'staticArray' => [CallableStaticClass::class, 'callableStaticMethod'],
            'instanceInvoke' => new CallableClassWithInvoke(),
            'closureInvoke' => $closure,
            // -> Only supported by DI
            'stringInvoke' => CallableClassWithInvoke::class,
            'stringClass' => CallableClass::class . '::callableMethod',
        ]);
    }

    /**
     * @dataProvider invocationDataProvider
     * @param callable $callable
     */
    public function testInvoke($callable)
    {
        $di = new DependencyInjector($this->handler, $this->logger);
        verify($di->invoke($callable, [ ':foo' => function() { return 'bar'; } ]))->equals(true);
    }
}