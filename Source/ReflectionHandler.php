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

class ReflectionHandler implements ReflectionHandlerInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * DependencyReflectionHandler constructor.
     *
     * @param LoggerInterface|null        $logger
     */
    public function __construct(LoggerInterface $logger = null)
    {
        $this->logger = $logger ?? new NullLogger();
    }

    /**
     * @param string $classId
     *
     * @return DependencyContainer
     */
    public function getDependencyContainer(string $classId): DependencyContainer
    {
        $this->logger->debug('Create new DependencyContainer for {classId}', [ 'classId' => $classId ]);
        return new DependencyContainer($classId);
    }

    /**
     * Will return a list with all arguments
     *
     * The return array has the argument names as key
     * and the argument class id as value (null in case of a non-class like string).
     *
     * @param string $classId
     * @param string $methodName
     *
     * @return Argument[]
     */
    public function getMethodParameters(string $classId, string $methodName = '__constructor'): array
    {
        $container = $this->getDependencyContainer($classId);
        if ( ! $container->hasMethod($methodName)) {
            $this->reflectMethod($container, $methodName);
        }
        return $container->getParameters($methodName);
    }

    /**
     * @param callable $callable
     *
     * @return Argument[]
     */
    public function getCallableParameters(callable $callable): array
    {
        $reflection = $this->getReflectionByCallable($callable);
        return $this->reflectArguments($reflection);
    }

    private function getReflectionByCallable(callable $callable): \ReflectionFunctionAbstract
    {
        if (is_string($callable)) {
            // Simple function call
            if (function_exists($callable)) {
                return new \ReflectionFunction($callable);
            }

            if(strpos($callable, '::') !== false) {
                list($classId, $method) = explode('::', $callable, 2);
                return new \ReflectionMethod($classId, $method);
            }
        }

        if ($callable instanceof \Closure) {
            return new \ReflectionMethod($callable, '__invoke');
        }

        if (is_callable($callable)) {
            // __invoke call
            if (is_object($callable)) {
                return new \ReflectionMethod($callable, '__invoke');
            }
            if (is_array($callable) && count($callable) === 2) {
                return new \ReflectionMethod($callable[0], $callable[1]);
            }
        }

        throw new \RuntimeException('Failed to perform an invocation: "' . $callable . '".'); // @codeCoverageIgnore
    }

    protected function reflectMethod(DependencyContainer $container, string $methodName): DependencyContainer
    {
        $this->logger->debug('Will reflect method {classId}::{methodName}',
            [ 'classId' => $container->getClassId(), 'methodName' => $methodName ]);

        $methodReflection = $methodName === '__constructor'
            ? (new \ReflectionClass($container->getClassId()))->getConstructor()
            : new \ReflectionMethod($container->getClassId(), $methodName);

        // If no constructor was defined, return empty argument list
        $parameters = $methodReflection
            ? $this->reflectArguments($methodReflection)
            : [];

        return $container->addMethod($methodName, $parameters);
    }

    private function reflectArguments(\ReflectionFunctionAbstract $reflection): array
    {
        // The parsed method parameters will be stored in here
        $methodParameters = [];

        // Get parameter reflection array and parse its arguments
        foreach ($reflection->getParameters() as $parameter) {
            $methodParameters[$parameter->getName()] = $this->reflectArgument($parameter);
        }

        return $methodParameters;
    }

    private function reflectArgument(\ReflectionParameter $parameter): Argument
    {
        $argument = new Argument($parameter->getName());

        if ($parameter->isVariadic()) {
            $argument->type = 'variadic';
        }
        elseif ($parameterClass = $parameter->getClass()) {
            $argument->type = 'object';
            $argument->classId = $parameterClass->getName();
        }
        elseif ($parameter->hasType() && $parameterType = $parameter->getType()) {
            $argument->type = (string) $parameterType;
            $argument->optional = (bool) $parameterType->allowsNull();
        }

        if ($parameter->isDefaultValueAvailable()) {
            $argument->optional = true;
            $argument->defaultValue = $parameter->getDefaultValue();
        }

        return $argument;
    }
}
