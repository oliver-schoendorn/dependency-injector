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

/**
 * Class DependencyContainer
 *
 * This is a serializable dependency container. It contains all reflection calls, that have been done so far.
 *
 * @package OS\DependencyInjector
 */
class DependencyContainer implements \Serializable
{
    /**
     * @var string
     */
    private $classId;

    /**
     * @var array[]
     */
    private $store = [];

    /**
     * DependencyContainer constructor.
     *
     * @param string $classId
     * @param array  $store
     */
    public function __construct(string $classId, array $store = [])
    {
        $this->classId = $classId;
        $this->store = $store;
    }

    /**
     * Returns the class id this dependency container was created for.
     *
     * @return string
     */
    public function getClassId(): string
    {
        return $this->classId;
    }

    /**
     * Checks, whether the given method name was already reflected.
     *
     * @param string $methodName
     *
     * @return bool
     */
    public function hasMethod(string $methodName): bool
    {
        return isset($this->store[$methodName]);
    }

    /**
     * Adds a method to the internal store.
     *
     * @param string $methodName
     * @param array  $parameters
     *
     * @return DependencyContainer
     */
    public function addMethod(string $methodName, array $parameters = []): DependencyContainer
    {
        $this->store[$methodName] = $parameters;
        return $this;
    }

    /**
     * @param string $methodName
     * @param string $parameterName
     * @param string $parameterClass
     *
     * @return DependencyContainer
     */
    public function addParameter(string $methodName, string $parameterName, string $parameterClass = null): DependencyContainer
    {
        // Create a key for the current method in the internal store, if necessary
        if ( ! isset($this->store[$methodName])) {
            $this->store[$methodName] = [];
        }

        // Add the parameter to the method store
        $this->store[$methodName][$parameterName] = $parameterClass;

        return $this;
    }

    /**
     * @param string $methodName
     *
     * @return array
     */
    public function getParameters(string $methodName): array
    {
        if ( ! isset($this->store[$methodName])) {
            return [];
        }

        return $this->store[$methodName];
    }

    /**
     * String representation of object
     * @link  http://php.net/manual/en/serializable.serialize.php
     * @return string the string representation of the object or null
     * @since 5.1.0
     */
    public function serialize()
    {
        return serialize([
            'classId' => $this->classId,
            'store'   => $this->store
        ]);
    }

    /**
     * Constructs the object
     * @link  http://php.net/manual/en/serializable.unserialize.php
     *
     * @param string $serialized The string representation of the object.
     *
     * @return void
     * @since 5.1.0
     */
    public function unserialize($serialized)
    {
        $serialized = unserialize($serialized);
        $this->classId = $serialized['classId'];
        $this->store   = $serialized['store'];
    }
}
