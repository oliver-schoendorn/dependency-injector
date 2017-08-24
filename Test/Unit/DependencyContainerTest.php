<?php
namespace OS\DependencyInjector\Test;


use OS\DependencyInjector\DependencyContainer;
use OS\DependencyInjector\Test\Helper\ClassAccessor;

class DependencyContainerTest extends \Codeception\Test\Unit
{
    /**
     * @var \OS\DependencyInjector\Test\UnitTester
     */
    protected $tester;

    public function testConstructorStoresClassName()
    {
        $expectedClassId = 'Foo';
        $container = new DependencyContainer($expectedClassId, []);
        $storedClassId = (new ClassAccessor($container))->getProperty('classId');
        verify($storedClassId)->equals($expectedClassId);
    }

    public function testConstructorStoresMethodData()
    {
        $expectedClassId = 'Foo';
        $expectedReflections = [ 'bar' => [ 'baz' => null ]];
        $container = new DependencyContainer($expectedClassId, $expectedReflections);

        $classAccessor = new ClassAccessor($container);
        $storedClassId = $classAccessor->getProperty('classId');
        $storedReflections = $classAccessor->getProperty('store');

        verify($storedClassId)->equals($expectedClassId);
        verify($storedReflections)->equals($expectedReflections);
    }

    public function testGetClassIdWillReturnStoredClassId()
    {
        $expectedClassId = 'Foo';
        $container = new DependencyContainer($expectedClassId, []);

        $actualClassId = $container->getClassId();

        verify($expectedClassId)->equals($actualClassId);
    }

    public function testHasMethodWithUnknownMethod()
    {
        $container = new DependencyContainer('Foo', [ 'bar' => [] ]);
        verify($container->hasMethod('baz'))->equals(false);

    }

    public function testHasMethodWithKnownMethod()
    {
        $container = new DependencyContainer('Foo', [ 'bar' => [] ]);
        verify($container->hasMethod('bar'))->equals(true);
    }

    public function testAddMethodWithUnknownMethod()
    {
        $testMethodName = 'bar';
        $container = new DependencyContainer('Foo', []);
        verify('pre-condition', $container->hasMethod($testMethodName))
            ->equals(false);

        $container->addMethod($testMethodName, []);
        verify($container->hasMethod($testMethodName))->equals(true);
    }

    public function testAddMethodUsesFluidInterface()
    {
        $container = new DependencyContainer('Foo', []);
        verify($container->addMethod('bar', []))->equals($container);
    }

    public function testAddParameterWillAddParameterToUndefinedMethod()
    {
        $testMethodName = 'bar';
        $testParamName = 'baz';
        $testParamType = null;

        $container = new DependencyContainer('Foo', []);
        verify('pre-condition', $container->hasMethod($testMethodName))
            ->equals(false);

        $container->addParameter($testMethodName, $testParamName, $testParamType);

        $accessor = new ClassAccessor($container);
        verify($accessor->getProperty('store'))
            ->equals([ $testMethodName => [ $testParamName => $testParamType ]]);

    }

    public function testAddParameterWillAddParameterToDefinedMethod()
    {
        $testMethodName = 'bar';
        $testParamName = 'baz';
        $testParamType = null;

        $container = new DependencyContainer('Foo', [ $testMethodName => []]);
        verify('pre-condition', $container->hasMethod($testMethodName))
            ->equals(true);

        $container->addParameter($testMethodName, $testParamName, $testParamType);

        $accessor = new ClassAccessor($container);
        verify($accessor->getProperty('store'))
            ->equals([ $testMethodName => [ $testParamName => $testParamType ]]);
    }

    public function testGetParametersWillReturnParametersOfMethod()
    {
        $testMethodName = 'bar';
        $testParams = [ 'baz' => null ];

        $container = new DependencyContainer('Foo', [ $testMethodName => $testParams ]);
        verify($container->getParameters($testMethodName))->equals($testParams);
    }

    public function testGetParameterWillNotThrowExceptionUsingAnUndefinedMethod()
    {
        $container = new DependencyContainer('Foo', []);
        verify($container->getParameters('unknown'))->equals([]);
    }

    public function testSerializeAndUnserializeWillRestoreInstance()
    {
        $expectedClassId = 'Foo';
        $expectedParams = [ 'Bar' => [ 'Baz' => null ] ];

        $container = new DependencyContainer($expectedClassId, $expectedParams);
        /** @var DependencyContainer $testContainer */
        $testContainer = unserialize(serialize($container));

        verify($testContainer->getClassId())->equals($expectedClassId);
        verify((new ClassAccessor($testContainer))->getProperty('store'))
            ->equals($expectedParams);
    }
}