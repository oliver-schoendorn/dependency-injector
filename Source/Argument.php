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


class Argument
{
    /**
     * @var string
     */
    public $name;

    /**
     * @var string|null
     */
    public $type;

    /**
     * @var string|null
     */
    public $classId;

    /**
     * @var bool
     */
    public $optional;

    /**
     * @var mixed|null
     */
    public $defaultValue;

    /**
     * Argument constructor.
     *
     * @param string $name
     * @param string|null $type
     * @param string|null $classId
     * @param bool $isOptional
     * @param mixed|null $defaultValue
     */
    public function __construct(string $name, string $type = null, string $classId = null, bool $isOptional = false, $defaultValue = null)
    {
        $this->name = $name;
        $this->type = $type;
        $this->classId = $classId;
        $this->optional = $isOptional;
        $this->defaultValue = $defaultValue;
    }
}
