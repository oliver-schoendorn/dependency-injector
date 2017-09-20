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


namespace OS\DependencyInjector\Test\Helper;

function callableFunction($foo)
{
    return true;
}

class CallableClass
{
    public function callableMethod($foo)
    {
        return true;
    }
}

class CallableClassWithInvoke
{
    public function __invoke($foo)
    {
        return true;
    }
}

global $closure;
$closure = function($foo)
{
    return true;
};

class CallableStaticClass
{
    public static function callableStaticMethod($foo)
    {
        return true;
    }
}
