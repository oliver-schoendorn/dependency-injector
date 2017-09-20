# PHP7 Dependency Injector

[![Build Status](https://travis-ci.org/oliver-schoendorn/dependency-injector.svg?branch=master)](https://travis-ci.org/oliver-schoendorn/dependency-injector)

## Usage

This readme will get better over time, I promise.

```php
<?php

use OS\DependencyInjector\DependencyInjector;
use OS\DependencyInjector\ReflectionHandler;
use OS\DependencyInjector\Test\_support\Helper\TestClass01;

$reflectionHandler = new ReflectionHandler();
$dependencyInjector = new DependencyInjector($reflectionHandler);

// Basic class resolving (+ passing an argument)
$instance = $dependencyInjector->resolve(TestClass01::class, [ 'optional' => 'some value']);
assert($instance->constructorArgument === 'some value');

// Resolve dependencies
class SomeClassWithDependencies
{
    public $someOtherClass;
    public function __construct(SomeOtherClass $someOtherClass) {
        $this->someOtherClass = $someOtherClass;
    }
}

class SomeOtherClass
{
    
}

$instance = $dependencyInjector->resolve(SomeClassWithDependencies::class);
assert($instance->someOtherClass instanceof SomeOtherClass);

// Alias
$dependencyInjector->alias(SomeOtherClass::class, SomeClassWithDependencies::class);
$instance = $dependencyInjector->resolve(SomeClassWithDependencies::class);
assert($instance instanceof SomeOtherClass);

// Configure
class YetAnotherClass extends SomeOtherClass
{
    
}

$dependencyInjector->configure(SomeClassWithDependencies::class, [ ':someOtherClass' => YetAnotherClass::class ]);
$instance = $dependencyInjector->resolve(SomeClassWithDependencies::class);
assert($instance->someOtherClass instanceof YetAnotherClass);

// Delegate
class ClassWithSetters
{
    public $logger;
    public function setLogger(Psr\Log\LoggerInterface $logger)
    {
        $this->logger = $logger;
    }
}

// -> the parameters of the delegate method will get resolved by the dependency injector
$delegate = function(Monolog\Logger $logger)
{
    $instance = new ClassWithSetters();
    $instance->setLogger($logger);
    return $instance;
};

$dependencyInjector->delegate(ClassWithSetters::class, $delegate);
$instance = $dependencyInjector->resolve(ClassWithSetters::class);
assert($instance->logger instanceof Monolog\Logger);

```