<?php
namespace OS\DependencyInjector\Tests;


use OS\DependencyInjector\Argument;
use PHPUnit\Framework\TestCase;

class ArgumentTest extends TestCase
{
    Public function testConstructor()
    {
        $name = 'foo';
        $type = 'bar';
        $class = 'foobar';
        $optional = true;
        $defaultValue = [ 'baz' ];

        $argument = new Argument($name, $type, $class, $optional, $defaultValue);
        verify($argument->name)->equals($name);
        verify($argument->type)->equals($type);
        verify($argument->classId)->equals($class);
        verify($argument->optional)->equals($optional);
        verify($argument->defaultValue)->equals($defaultValue);
    }
}
