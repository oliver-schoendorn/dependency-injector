<?php
namespace OS\DependencyInjector\Test;


use OS\DependencyInjector\Argument;

class ArgumentTest extends \Codeception\Test\Unit
{
    /**
     * @var \OS\DependencyInjector\Test\UnitTester
     */
    protected $tester;

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