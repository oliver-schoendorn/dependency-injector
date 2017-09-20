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


use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;

interface DependencyInjectorInterface
{

    /**
     * DependencyInjectorInterface constructor.
     *
     * The dependency injector will cache most of the reflection calls.
     *
     * @param ReflectionHandlerInterface $reflectionHandler
     * @param LoggerInterface|null $logger
     */
    public function __construct(ReflectionHandlerInterface $reflectionHandler, LoggerInterface $logger = null);

    /**
     * Registers a new class instance.
     *
     * The given class instance will be treated like a singleton (at least until overloaded).
     *
     * @param object $classInstance
     * @param string|null $alias
     *
     * @return DependencyInjectorInterface
     */
    public function share($classInstance, $alias = null);

    /**
     * Registers a new class id and a factory in order to create instances of the referenced class.
     *
     * @param string   $classId
     * @param callable $factory
     *
     * @return DependencyInjectorInterface
     */
    public function delegate($classId, callable $factory);

    /**
     * Registers a new class id alias.
     *
     * @param string $original
     * @param string $alias
     *
     * @return DependencyInjectorInterface
     */
    public function alias($original, $alias);

    /**
     * Will return the requested class instance.
     *
     * If you want to use custom constructor arguments, you can define them in an associative array, where the key has
     * to match the constructor argument name.
     *
     * @param string $classId
     * @param array  $arguments
     *
     * @return object
     */
    public function resolve($classId, array $arguments = []);
}
