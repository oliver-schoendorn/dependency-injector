<?php
/**
 * Copyright (c) 2017 Oliver Schöndorn
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace OS\DependencyInjector\Test\_support\Helper;


class TestClass01
{
    public $didCallConstructor = false;
    public $constructorArgument;

    public function __construct($optional = null)
    {
        $this->didCallConstructor = true;
        $this->constructorArgument = $optional;
    }

    public function testGetMethodParameters(string $foo, int $bar = 23)
    {

    }

    public function testMethod(TestClass02 $testClass02)
    {
        return $testClass02;
    }

    public static function staticTestMethod(TestClass01 $self)
    {

    }

    public static function typeTest(
        self $self,
        array $array,
        callable $callable,
//        iterable $iterable, // php 7.1
        bool $bool,
        float $float,
        int $int,
        string $string,
        ...$variadic
    ) {

    }

    public function defaultValueTest(
        int $int = 1,
        float $float = 10.2,
        string $string = 'string',
        bool $bool = false,
        array $array = [ 'foo' => 'bar' ]
    ) {

    }

    public function defaultValueNullTest(
        int $int = null,
        float $float = null,
        string $string = null,
        bool $bool = null,
        array $array = null
    ) {

    }
}
