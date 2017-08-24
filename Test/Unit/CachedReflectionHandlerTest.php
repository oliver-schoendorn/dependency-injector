<?php
namespace OS\DependencyInjector\Test;


use OS\DependencyInjector\Argument;
use OS\DependencyInjector\CachedReflectionHandler;
use OS\DependencyInjector\DependencyCacheItem;
use OS\DependencyInjector\DependencyContainer;
use OS\DependencyInjector\Test\_support\Helper\TestClass01;
use OS\DependencyInjector\Test\Helper\ClassAccessor;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\NullLogger;

class CachedReflectionHandlerTest extends \Codeception\Test\Unit
{
    /**
     * @var \OS\DependencyInjector\Test\UnitTester
     */
    protected $tester;

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|CacheItemPoolInterface
     */
    private function getCacheMockBuilder(): \PHPUnit_Framework_MockObject_MockObject
    {
        return $this->getMockBuilder(CacheItemPoolInterface::class)
            ->setMethodsExcept([])
            ->getMockForAbstractClass();
    }

    public function testConstructor()
    {
        $cache = $this->getCacheMockBuilder();
        $cacheTTL = 1234;
        $logger = new NullLogger();

        $handler = new CachedReflectionHandler($cache, $cacheTTL, $logger);
        $accessor = new ClassAccessor($handler);

        verify($accessor->getProperty('cachePool'))->same($cache);
        verify($accessor->getProperty('cacheTTL'))->equals($cacheTTL);
        verify($accessor->getProperty('logger'))->same($logger);
    }

    public function getDependencyContainerDataProvider()
    {
        $container = new DependencyContainer('key');
        return [
            'will return from cache' => [ false, $container ],
            'will return new container if expired' => [ true, $container ],
            'will return new container if not set' => [ false, null ],
        ];
    }

    /**
     * @dataProvider getDependencyContainerDataProvider
     *
     * @param bool $cacheItemExpired
     * @param DependencyContainer|null $container
     */
    public function testGetDependencyContainer(bool $cacheItemExpired, DependencyContainer $container = null)
    {
        $cacheItem = new DependencyCacheItem(
            'key',
            !! $container,
            $container,
            $cacheItemExpired ? new \DateTimeImmutable('-1minute') : new \DateTimeImmutable('+1minute')
        );

        $cache = $this->getCacheMockBuilder();
        $cache->expects($this::once())
            ->method('getItem')
            ->with('key')
            ->willReturn($cacheItem);

        $handler = new CachedReflectionHandler($cache);
        $response = $handler->getDependencyContainer('key');

        verify($response)->isInstanceOf(DependencyContainer::class);
        if ($container && ! $cacheItemExpired) {
            verify($response)->same($container);
        }
        else {
            verify($response->getClassId())->equals('key');
        }
    }

    public function getMethodParametersDataProvider()
    {
        $testClassId = TestClass01::class;
        $testClassConstructorArguments = [ 'testGetMethodParameters' => [
            'foo' => new Argument('foo', 'string'),
            'bar' => new Argument('bar', 'int', null, true, 23)
        ]];

        return [
            'will update cache' => [ true, $testClassId, $testClassConstructorArguments ],
            'will not update cache' => [ false, $testClassId, $testClassConstructorArguments ]
        ];
    }

    /**
     * @dataProvider getMethodParametersDataProvider
     *
     * @param bool $shouldUpdate
     * @param string $classId
     * @param string[] $arguments
     */
    public function testGetMethodParameters(bool $shouldUpdate, string $classId, array $arguments)
    {
        $container = new DependencyContainer($classId, $shouldUpdate ? [] : $arguments);
        $cacheItem = new DependencyCacheItem(
            'key',
            !! $container,
            $container,
            new \DateTimeImmutable('+1minute')
        );

        $pool = $this->getCacheMockBuilder();
        $pool->expects($this::once())
            ->method('getItem')
            ->with(TestClass01::class)
            ->willReturn($cacheItem);

        $pool->expects($shouldUpdate ? $this::once() : $this::never())
            ->method('save')
            ->with($this::callback(function(DependencyCacheItem $cacheItem) use ($classId, $container)
            {
                verify($cacheItem)->isInstanceOf(DependencyCacheItem::class);
                verify($cacheItem->getKey())->equals($classId);
                verify($cacheItem->get())->same($container);
                return true;
            }));

        $handler = new CachedReflectionHandler($pool);
        $response = $handler->getMethodParameters($classId, 'testGetMethodParameters');

        verify($response)->equals($arguments['testGetMethodParameters']);
    }
}