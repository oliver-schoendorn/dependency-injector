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

/**
 * Class ClassAccessor
 *
 * A helper class to access properties and methods, disregarding whether they are private or protected.
 * NOTE: This will not work with static properties or methods.
 *
 * @package OS\DependencyInjector\Test\Helper
 */
class ClassAccessor
{
    private $object;

    /**
     * Accessor constructor.
     *
     * @param object|string $object
     */
    public function __construct($object)
    {
        $this->object = $object;
    }

    /**
     * Will return the value of the given property.
     *
     * @param string $name
     * @return mixed
     */
    public function getProperty(string $name)
    {
        $closure = function($object, $name) {
            return $object->{$name};
        };

        $closure = \Closure::bind($closure, null, $this->getClassId());
        return $closure($this->object, $name);
    }

    private function getClassId(): string
    {
        return is_string($this->object) ? $this->object : get_class($this->object);
    }

    /**
     * Will return the value of the given static property.
     *
     * @param string $name
     * @return mixed
     */
    public function getStaticProperty(string $name)
    {
        $closure = function($object, $name)
        {
            return $object::$$name;
        };

        $closure = \Closure::bind($closure, null, $this->getClassId());
        return $closure($this->object, $name);
    }

    /**
     * Will set the value of the given property.
     *
     * @param string $name
     * @param mixed $value
     */
    public function setProperty(string $name, $value)
    {
        $closure = function($object, $name, $value) {
            return $object->{$name} = $value;
        };

        $closure = \Closure::bind($closure, null, $this->getClassId());
        $closure($this->object, $name, $value);
    }

    /**
     * Will set the value of the given static property.
     * 
     * @param string $name
     * @param mixed $value
     */
    public function setStaticProperty(string $name, $value)
    {
        $closure = function($object, $name, $value) {
            return $object::$$name = $value;
        };

        $closure = \Closure::bind($closure, null, $this->getClassId());
        $closure($this->object, $name, $value);
    }

    /**
     * Will invoke the given method.
     *
     * @param string $name
     * @param array $arguments
     * 
     * @return mixed
     */
    public function invokeMethod(string $name, array $arguments = [])
    {
        $closure = function($object, $name, $arguments) {
            return $object->{$name}(...$arguments);
        };

        $closure = \Closure::bind($closure, null, $this->getClassId());
        return $closure($this->object, $name, $arguments);
    }

    /**
     * Will invoke the given static method.
     *
     * @param string $name
     * @param array $arguments
     *
     * @return mixed
     */
    public function invokeStaticMethod(string $name, array $arguments = [])
    {
        $closure = function($object, $name, $arguments) {
            return $object::$$name(...$arguments);
        };

        $closure = \Closure::bind($closure, null, $this->getClassId());
        return $closure($this->object, $name, $arguments);
    }
}
