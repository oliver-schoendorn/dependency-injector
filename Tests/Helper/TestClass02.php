<?php
/*
 * Copyright (c) 2021 Oliver Schöndorn
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

namespace OS\DependencyInjector\Tests\Helper;


class TestClass02
{
    public $constructorArguments;

    public function __construct(TestClass01 $test, string $foo = 'empty', int $bar = 12)
    {
        $this->constructorArguments = [
            'test' => $test,
            'foo' => $foo,
            'bar' => $bar
        ];
    }

    public function staticMethod(TestClass01 $test, string $foo, int $bar = 12)
    {
        return [
            'test' => $test,
            'foo' => $foo,
            'bar' => $bar
        ];
    }

    private function privateMethod()
    {

    }

    public static function foo(self $test)
    {
        $test->privateMethod();
    }
}
