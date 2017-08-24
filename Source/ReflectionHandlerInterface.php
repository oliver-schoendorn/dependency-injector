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

namespace OS\DependencyInjector;


use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

interface ReflectionHandlerInterface
{
    /**
     * @param string $classId
     *
     * @return DependencyContainer
     */
    public function getDependencyContainer(string $classId): DependencyContainer;

    /**
     * Will return a list with all arguments
     *
     * The return array has the argument names as key
     * and the argument class id as value (null in case of a non-class like string).
     *
     * @param string $classId
     * @param string $methodName
     *
     * @return array
     */
    public function getMethodParameters(string $classId, string $methodName = '__constructor'): array;

    /**
     * @param callable $callable
     *
     * @return Argument[]
     */
    public function getCallableParameters(callable $callable): array;
}
