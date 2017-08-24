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


use Psr\Cache\CacheItemInterface;

class DependencyCacheItem implements CacheItemInterface
{
    /**
     * @var string
     */
    protected $key;

    /**
     * @var DependencyContainer
     */
    protected $value;

    /**
     * @var bool
     */
    protected $hasValue;

    /**
     * @var \DateTimeInterface|null
     */
    protected $expires;

    /**
     * DependencyCacheItem constructor.
     *
     * @param string                   $key
     * @param bool                     $hasValue
     * @param DependencyContainer|null $value
     * @param \DateTimeInterface|null  $expiresAt
     */
    public function __construct(
        string $key,
        bool $hasValue,
        DependencyContainer $value = null,
        \DateTimeInterface $expiresAt = null
    ) {
        $this->key = $key;
        $this->value = $value;
        $this->hasValue = $hasValue;
        $this->expires = $expiresAt;
    }

    /**
     * Returns the key for the current cache item.
     *
     * The key is loaded by the Implementing Library, but should be available to
     * the higher level callers when needed.
     *
     * @return string
     *   The key string for this cache item.
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * Retrieves the value of the item from the cache associated with this object's key.
     *
     * The value returned must be identical to the value originally stored by set().
     *
     * If isHit() returns false, this method MUST return null. Note that null
     * is a legitimate cached value, so the isHit() method SHOULD be used to
     * differentiate between "null value was found" and "no value was found."
     *
     * @return DependencyContainer|null
     *   The value corresponding to this cache item's key, or null if not found.
     */
    public function get()
    {
        if ( ! $this->isHit()) {
            return null;
        }

        return $this->value;
    }

    /**
     * Confirms if the cache item lookup resulted in a cache hit.
     *
     * Note: This method MUST NOT have a race condition between calling isHit()
     * and calling get().
     *
     * @return bool
     *   True if the request resulted in a cache hit. False otherwise.
     */
    public function isHit()
    {
        if ($this->isExpired()) return false;

        return $this->hasValue;
    }

    /**
     * Sets the value represented by this cache item.
     *
     * The $value argument may be any item that can be serialized by PHP,
     * although the method of serialization is left up to the Implementing
     * Library.
     *
     * @param mixed $value
     *   The serializable value to be stored.
     *
     * @return static
     *   The invoked object.
     */
    public function set($value)
    {
        $this->value = $value;

        if ( ! $this->isHit()) {
            $this->hasValue = true;
            $this->expires = null;
        }

        return $this;
    }

    /**
     * Tests the expiry date time against the current date time.
     *
     * @return bool
     */
    public function isExpired()
    {
        // No expiry set
        if ( ! $this->expires) {
            return false;
        }

        // Compare the expiry date time with the current date time
        return $this->expires <= new \DateTimeImmutable('now');
    }

    /**
     * Sets the expiration time for this cache item.
     *
     * @param \DateTimeInterface|null $expiration
     *   The point in time after which the item MUST be considered expired.
     *   If null is passed explicitly, a default value MAY be used. If none is set,
     *   the value should be stored permanently or for as long as the
     *   implementation allows.
     *
     * @return static
     *   The called object.
     */
    public function expiresAt($expiration)
    {
        if ( ! ($expiration instanceof \DateTimeInterface) && $expiration !== null) {
            throw new \InvalidArgumentException('Expires at must be of type DateTimeInterface or null.');
        }

        $this->expires = $expiration;
        return $this;
    }

    /**
     * Sets the expiration time for this cache item.
     *
     * @param int|\DateInterval|null $time
     *   The period of time from the present after which the item MUST be considered
     *   expired. An integer parameter is understood to be the time in seconds until
     *   expiration. If null is passed explicitly, a default value MAY be used.
     *   If none is set, the value should be stored permanently or for as long as the
     *   implementation allows.
     *
     * @return static
     *   The called object.
     */
    public function expiresAfter($time)
    {
        if (is_int($time)) {
            $this->expires = new \DateTimeImmutable('+' . $time . ' seconds');
        }
        else if($time instanceof \DateInterval) {
            $this->expires = (new \DateTimeImmutable())->add($time);
        }
        else {
            $this->expires = null;
        }

        return $this;
    }
}
