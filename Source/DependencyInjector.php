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
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class DependencyInjector implements DependencyInjectorInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var ReflectionHandlerInterface
     */
    protected $reflectionHandler;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var string[]
     */
    protected $aliases = [];

    /**
     * @var callable[]
     */
    protected $delegates = [];

    /**
     * @var array[]
     */
    protected $configs = [];

    /**
     * @var callable[]
     */
    protected $callbacks = [];

    /**
     * @var object[]
     */
    protected $sharedInstances = [];

    /**
     * @var array
     */
    protected $processing = [];

    /**
     * DependencyInjectorInterface constructor.
     *
     * The dependency injector will cache most of the reflection calls.
     *
     * @param ReflectionHandlerInterface $reflectionHandler
     * @param LoggerInterface|null $logger
     */
    public function __construct(ReflectionHandlerInterface $reflectionHandler, LoggerInterface $logger = null)
    {
        $this->reflectionHandler = $reflectionHandler;
        $this->logger = $logger ?? new NullLogger();
        $this->share($this);
    }

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
    public function share($classInstance, string $alias = null): DependencyInjectorInterface
    {
        $classId = get_class($classInstance);
        $this->logger->debug('Registered shared instance of class {classId}', [ 'classId' => $classId ]);
        $this->sharedInstances[$classId] = $classInstance;

        if ($alias) {
            $this->alias($alias, $classId);
        }

        return $this;
    }

    /**
     * Registers a new class id and a factory in order to create instances of the referenced class.
     *
     * @param string   $classId
     * @param callable $factory
     *
     * @return DependencyInjectorInterface
     */
    public function delegate(string $classId, callable $factory): DependencyInjectorInterface
    {
        $this->logger->debug('Registered delegate for class {classId}', [ 'classId' => $classId ]);
        $this->delegates[$classId] = $factory;
        return $this;
    }

    /**
     * Registers a new class id alias.
     *
     * @param string $original
     * @param string $alias
     *
     * @return DependencyInjectorInterface
     */
    public function alias(string $original, string $alias): DependencyInjectorInterface
    {
        $this->logger->debug('Registered alias for class {classId} to {alias}', [ 'classId' => $original, 'alias' => $alias ]);
        $this->aliases[$original] = $alias;
        return $this;
    }

    /**
     * Registers default constructor arguments for the given classId.
     *
     * @param string $classId
     * @param array  $arguments
     *
     * @return DependencyInjectorInterface
     */
    public function configure(string $classId, array $arguments): DependencyInjectorInterface
    {
        $this->configs[$classId] = $arguments;
        return $this;
    }

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
    public function resolve(string $classId, array $arguments = [])
    {
        $this->logger->debug('Requested instance of class {classId}', [ 'classId' => $classId ]);
        $this->processing = [];
        return $this->resolveDependency($classId, $arguments);
    }

    /**
     * Invokes the given callback and parses the required arguments.
     *
     * Basically the same as resolve, but with a specific method instead of the constructor.
     *
     * @param callable $callable
     * @param array $arguments
     *
     * @return mixed
     */
    public function invoke($callable, array $arguments = [])
    {
        // Catch some calls that php thinks are not callable
        if (is_string($callable) && class_exists($callable)) {
            $callable = $this->resolve($callable);
        }

        $parameters = $this->reflectionHandler->getCallableParameters($callable);
        $parameters = $this->resolveParameters('callable', '__invoke', $parameters, $arguments);
        return call_user_func_array($callable, $parameters);
    }

    /**
     * Resolves the desired dependency.
     *
     * This means that we either return a shared instance, invoke a delegate or create a new instance.
     *
     * @param string $classId
     * @param array  $arguments
     *
     * @return object
     */
    protected function resolveDependency(string $classId, array $arguments = [])
    {
        $this->logger->debug('Resolving class {classId}', [ 'classId' => $classId ]);

        // Resolve alias
        $classId = $this->resolveAlias($classId);

        // If we are trying to resolve a shared instance, return it
        if (isset($this->sharedInstances[$classId])) {
            $this->logger->debug('Found shared instance for {classId}', ['classId' => $classId ]);
            return $this->sharedInstances[$classId];
        }

        // Look for delegates
        if (isset($this->delegates[$classId])) {
            $this->logger->debug('Found delegate for {classId}', ['classId' => $classId ]);
            return $this->invoke($this->delegates[$classId], [ 'classId' => $classId, 'arguments' => $arguments ]);
        }

        // Make sure we are not running in circles
        if (isset($this->processing[$classId]) && $this->processing[$classId] === true) {
            $this->logger->critical('Encountered a circular class dependency {classId}: {process}',
                [ 'classId' => $classId, 'process' => print_r($this->processing, true) ]);
            throw new \RuntimeException('Failed to create an instance of ' . $classId . ' due to a circular dependency.');
        }

        // Mark this dependency as being processed
        $this->processing[$classId] = true;

        // Add default arguments if we have some
        $arguments = $this->mergeWithConfigIfExists($classId, $arguments);

        $requiredArguments = $this->reflectionHandler->getMethodParameters($classId);

        // Parse the constructor arguments
        $constructorArguments = $this->resolveParameters($classId, '__constructor', $requiredArguments, $arguments);

        // Mark this dependency as being resolved
        $this->processing[$classId] = false;

        // Return the new instance
        return new $classId(...$constructorArguments);
    }

    /**
     * Adds default arguments, if the have been defined by the user.
     *
     * @param string $classId
     * @param array  $arguments
     *
     * @return array
     */
    protected function mergeWithConfigIfExists(string $classId, array $arguments): array
    {
        // Nothing to do if we have no default configs for this class
        if ( ! isset($this->configs[$classId])) {
            return $arguments;
        }

        $this->logger->debug('Will merge default config values for class "{class}":',
            [ 'class' => $classId, 'defaultArgs' => $this->configs[$classId], 'givenArgs' => $arguments ]);

        foreach ($this->configs[$classId] as $key => $value) {
            // Remove the : in front of the key
            $plainKey = ltrim($key, ':');

            // Only add the default value if the config key was not given
            if ( ! array_key_exists($plainKey, $arguments) && ! array_key_exists(':' . $plainKey, $arguments)) {
                $arguments[$key] = $value;
            }
        }

        return $arguments;
    }

    /**
     * Iterates through the required arguments and extracts them from the given arguments and known classes.
     *
     * @param string     $classId
     * @param string     $methodName
     * @param Argument[] $requiredArguments
     * @param array      $givenArguments
     *
     * @return array
     */
    protected function resolveParameters(string $classId, string $methodName, array $requiredArguments, array $givenArguments = []): array
    {
        // Prepare the return value
        $resolvedParameters = [];

        // Parse the method arguments
        foreach ($requiredArguments as $argument) {
            if (isset($givenArguments[':' . $argument->name])) {
                $argumentValue = $givenArguments[':' . $argument->name];
                array_push($resolvedParameters, is_callable($argumentValue)
                    ? $this->invoke($argumentValue, $givenArguments)
                    : $this->resolveDependency($argumentValue, $givenArguments));
            }
            elseif (isset($givenArguments[$argument->name])) {
                array_push($resolvedParameters, $givenArguments[$argument->name]);
            }
            elseif ($argument->classId) {
                array_push($resolvedParameters, $this->resolveDependency($argument->classId, $givenArguments));
            }
            elseif ($argument->optional) {
                array_push($resolvedParameters, $argument->defaultValue);
            }
            else {
                $message = sprintf(
                    'Unable to resolve %s::%s because argument "%s" of type "%s" was not provided and it has no default value.',
                    $classId, $methodName, $argument->name, $argument->type
                );
                $this->logger->critical($message, [ 'dependencyStack' => $this->processing ]);
                throw new \InvalidArgumentException($message);
            }
        }

        // Return the resolved parameters
        return $resolvedParameters;
    }

    /**
     * If the given class id has an alias, this method will return the referenced class id.
     *
     * @param string $classId
     *
     * @return string
     */
    protected function resolveAlias(string $classId): string
    {
        return $this->aliases[$classId] ?? $classId;
    }
}
