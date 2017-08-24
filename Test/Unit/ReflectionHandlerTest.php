<?php
namespace OS\DependencyInjector\Test;


use Codeception\Configuration;
use Monolog\Handler\StreamHandler;
use Monolog\Processor\PsrLogMessageProcessor;
use OS\DependencyInjector\Argument;
use OS\DependencyInjector\DependencyContainer;
use OS\DependencyInjector\ReflectionHandler;
use OS\DependencyInjector\Test\_support\Helper\TestClass01;
use OS\DependencyInjector\Test\_support\Helper\TestClass02;
use OS\DependencyInjector\Test\Helper\CallableClass;
use OS\DependencyInjector\Test\Helper\CallableClassWithInvoke;
use OS\DependencyInjector\Test\Helper\CallableStaticClass;
use OS\DependencyInjector\Test\Helper\ClassAccessor;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class ReflectionHandlerTest extends \Codeception\Test\Unit
{
    /**
     * @var \OS\DependencyInjector\Test\UnitTester
     */
    protected $tester;

    /**
     * @var LoggerInterface
     */
    private $logger;

    protected function setUp()
    {
        $outputDir = codecept_output_dir();
        $logFile = $outputDir . DIRECTORY_SEPARATOR . 'ReflectionHandlerTest.log';

        $handler = new StreamHandler(fopen($logFile, 'w'));
        $this->logger = new \Monolog\Logger('', [ $handler ]);
        $this->logger->pushProcessor(new PsrLogMessageProcessor());

        $this->logger->useMicrosecondTimestamps(true);
        $this->logger->notice('Started unit test');

        parent::setUp();
    }

    public function testConstructorWithoutLogger()
    {
        $logger = new NullLogger();
        $handler = new ReflectionHandler($logger);
        $accessor = new ClassAccessor($handler);

        verify($accessor->getProperty('logger'))->same($logger);
    }

    public function testConstructorWithLogger()
    {
        $handler = new ReflectionHandler();
        $accessor = new ClassAccessor($handler);

        verify($accessor->getProperty('logger'))->isInstanceOf(LoggerInterface::class);
    }

    public function testGetDependencyContainer()
    {
        $handler = new ReflectionHandler();
        $container = $handler->getDependencyContainer('FooBar');
        verify($container)->isInstanceOf(DependencyContainer::class);
    }

    public function testGetMethodParametersWithPrimitives()
    {
        $handler = new ReflectionHandler($this->logger);
        $params = $handler->getMethodParameters(TestClass01::class, 'testGetMethodParameters');

        verify($params)->equals([
            'foo' => new Argument('foo', 'string'),
            'bar' => new Argument('bar', 'int', null, true,23)
        ]);
    }

    public function testGetMethodParametersWithClasses()
    {
        $handler = new ReflectionHandler();
        $params = $handler->getMethodParameters(TestClass01::class, 'testMethod');

        verify($params)->equals([
            'testClass02' => new Argument('testClass02', 'object', TestClass02::class)
        ]);
    }

    public function testGetMethodParametersWithStaticMethod()
    {
        $handler = new ReflectionHandler();
        $params = $handler->getMethodParameters(TestClass01::class, 'staticTestMethod');

        verify($params)->equals([
            'self' => new Argument('self', 'object', TestClass01::class)
        ]);
    }

    public function phpTypesDataProvider()
    {
        return [
            'self' => [ new Argument('self', 'object', TestClass01::class) ],
            'array' => [ new Argument('array', 'array') ],
            'callable' => [ new Argument('callable', 'callable') ],
//            'iterable' => [ new Argument('iterable', 'iterable') ], // php 7.1
            'bool' => [ new Argument('bool', 'bool') ],
            'float' => [ new Argument('float', 'float') ],
            'int' => [ new Argument('int', 'int') ],
            'string' => [ new Argument('string', 'string') ],
            'variadic' => [ new Argument('variadic', 'variadic') ]
        ];
    }

    /**
     * @dataProvider phpTypesDataProvider
     *
     * @param Argument $type
     */
    public function testGetMethodParametersWithPhpType(Argument $type)
    {
        $handler = new ReflectionHandler();
        $params = $handler->getMethodParameters(TestClass01::class, 'typeTest');
        verify($params[$type->name])->equals($type);
    }

    public function defaultValuesDataProvider()
    {
        return [
            'int' => [ new Argument('int', 'int', null, true, 1) ],
            'float' => [ new Argument('float', 'float', null, true, 10.2) ],
            'string' => [ new Argument('string', 'string', null, true, 'string') ],
            'bool' => [ new Argument('bool', 'bool', null, true, false) ],
            'array' => [ new Argument('array', 'array', null, true, [ 'foo' => 'bar' ]) ],
        ];
    }

    /**
     * @dataProvider defaultValuesDataProvider
     *
     * @param Argument $type
     */
    public function testGetMethodParametersDefaultValues(Argument $type)
    {
        $handler = new ReflectionHandler();
        $params = $handler->getMethodParameters(TestClass01::class, 'defaultValueTest');
        verify($params[$type->name])->equals($type);
    }

    public function defaultValuesNullDataProvider()
    {
        return [
            'int' => [ new Argument('int', 'int', null, true, null) ],
            'float' => [ new Argument('float', 'float', null, true, null) ],
            'string' => [ new Argument('string', 'string', null, true, null) ],
            'bool' => [ new Argument('bool', 'bool', null, true, null) ],
            'array' => [ new Argument('array', 'array', null, true, null) ],
        ];
    }

    /**
     * @dataProvider defaultValuesNullDataProvider
     *
     * @param Argument $type
     */
    public function testGetMethodParametersDefaultNullValues(Argument $type)
    {
        $handler = new ReflectionHandler();
        $params = $handler->getMethodParameters(TestClass01::class, 'defaultValueNullTest');
        verify($params[$type->name])->equals($type);
    }
    
    public function callableDataProvider()
    {
        $helperDir = Configuration::supportDir() . DIRECTORY_SEPARATOR . 'Helper';
        $callableFile = $helperDir . DIRECTORY_SEPARATOR . 'callable.php';
        require_once $callableFile;

        global $closure;

        return array_map(function($value) { return [ $value ]; }, [
            'emptyFunc' => function(string $foo){},
            'object' => [(new CallableClass()), 'callableMethod'],
            'globalFunc' => 'OS\DependencyInjector\Test\Helper\callableFunction',
            'staticString' => CallableStaticClass::class . '::callableStaticMethod',
            'staticArray' => [CallableStaticClass::class, 'callableStaticMethod'],
            'instanceInvoke' => new CallableClassWithInvoke(),
            'closureInvoke' => $closure,
            // -> Only supported by DI
            // 'stringInvoke' => CallableClassWithInvoke::class,
            // 'stringClass' => CallableClass::class . '::myInstanceMethod',
        ]);
    }

    /**
     * @dataProvider callableDataProvider
     * @param callable $callable
     */
    public function testGetCallableParameters($callable)
    {
        $handler = new ReflectionHandler();
        $params = $handler->getCallableParameters($callable);

        verify($params)->equals([
            'foo' => new Argument('foo', 'string')
        ]);
    }
}