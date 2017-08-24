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

class CachedReflectionHandler extends ReflectionHandler
{

    /**
     * @var CacheItemPoolInterface|null
     */
    private $cachePool;

    /**
     * @var int
     */
    private $cacheTTL;

    /**
     * CachedReflectionHandler constructor.
     *
     * @param CacheItemPoolInterface|null $cachePool
     * @param int $cacheTTL Cache item lifetime (in seconds)
     * @param LoggerInterface|null $logger
     */
    public function __construct(CacheItemPoolInterface $cachePool, int $cacheTTL = 86400, LoggerInterface $logger = null)
    {
        parent::__construct($logger);

        $this->cachePool = $cachePool;
        $this->cacheTTL = $cacheTTL;
    }

    /**
     * @param string $classId
     *
     * @return DependencyContainer
     */
    public function getDependencyContainer(string $classId): DependencyContainer
    {
        if ($container = $this->getDependencyFromCache($classId)) {
            $this->logger->debug('Returned DependencyContainer of {classId} from cache', [ 'classId' => $classId ]);
            return $container;
        }

        return parent::getDependencyContainer($classId);
    }

    /**
     * @param string $classId
     * @return DependencyContainer|null
     */
    private function getDependencyFromCache(string $classId)
    {
        if ( ! ($container = $this->cachePool->getItem($classId)) || ! $container->isHit()) {
            return null;
        }
        else {
            return $container->get();
        }
    }

    protected function reflectMethod(DependencyContainer $container, string $methodName): DependencyContainer
    {
        $container = parent::reflectMethod($container, $methodName);
        $this->saveToCache($container);
        return $container;
    }

    private function saveToCache(DependencyContainer $container)
    {
        $this->logger->debug('Updating cache item of class "{classId}"', [ 'classId' => $container->getClassId() ]);
        $this->cachePool->save(new DependencyCacheItem($container->getClassId(), true, $container,
            new \DateTimeImmutable('+' . $this->cacheTTL . ' seconds')));
    }
}
