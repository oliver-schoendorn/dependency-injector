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


trait Php7Helper
{
    /**
     * @return bool
     */
    public function isPhp7()
    {
        return version_compare(PHP_VERSION, '7.0.0') >= 0;
    }

    /**
     * @param string $type
     *
     * @return null
     */
    public function returnTypeIfPhp7($type)
    {
        return $this->isPhp7() ? $type : null;
    }
}
