<?php
namespace OS\DependencyInjector\Tests;


use DateInterval;
use DateTimeImmutable;
use DateTimeInterface;
use Exception;
use OS\DependencyInjector\DependencyCacheItem;
use OS\DependencyInjector\DependencyContainer;
use OS\DependencyInjector\Tests\Helper\ClassAccessor;
use PHPUnit\Framework\TestCase;

class DependencyCacheItemTest extends TestCase
{
    public function testConstructor()
    {
        $key = 'key';
        $hasValue = true;
        $value = new DependencyContainer($key, ['foo' => 'bar']);
        $expires = new DateTimeImmutable('+20seconds');

        $item = new DependencyCacheItem($key, $hasValue, $value, $expires);
        $accessor = new ClassAccessor($item);

        verify($accessor->getProperty('key'))->same($key);
        verify($accessor->getProperty('hasValue'))->same($hasValue);
        verify($accessor->getProperty('value'))->same($value);
        verify($accessor->getProperty('expires'))->same($expires);
    }

    public function testGetKey()
    {
        $item = new DependencyCacheItem('key', false);
        verify($item->getKey())->equals('key');
    }

    public function testGetWithoutHit()
    {
        $item = new DependencyCacheItem('key', false);
        verify($item->get())->null();
    }

    public function testGetWithHit()
    {
        $value = new DependencyContainer('key', ['foo' => 'bar']);
        $item = new DependencyCacheItem('key', true, $value);
        verify($item->get())->same($value);
    }

    public function isHitDataProvider(): array
    {
        return [
            'is hit' => [ true, '+30seconds', true ],
            'is empty' => [ false, '+30seconds', false ],
            'is expired' => [ false, '-30seconds', true ]
        ];
    }

    /**
     * @dataProvider isHitDataProvider
     *
     * @param bool   $isHit
     * @param string $expiry
     * @param bool   $hasValue
     *
     * @throws Exception
     */
    public function testIsHit(bool $isHit, string $expiry, bool $hasValue)
    {
        $item = new DependencyCacheItem(
            'key',
            $hasValue,
            $hasValue
                ? new DependencyContainer('key', ['foo' => 'bar'])
                : null,
            new DateTimeImmutable($expiry)
        );

        verify($item->isHit())->equals($isHit);
    }

    public function setDataProvider(): array
    {
        return [
            'with hit' => [ '+30seconds', true, true ],
            'with non-hit' => [ '+30seconds', false, false ],
            'with expired' => [ '-30seconds', true, false ],
            'with non-hit & expired' => [ '-30seconds', false, false ]
        ];
    }

    /**
     * @dataProvider setDataProvider
     *
     * @param string $expires
     * @param bool   $hasValue
     * @param bool   $wasHit
     *
     * @throws Exception
     */
    public function testSet(string $expires, bool $hasValue, bool $wasHit)
    {
        $expires = new DateTimeImmutable($expires);
        $value = new DependencyContainer('key', ['foo' => 'bar']);
        $item = new DependencyCacheItem('key', $hasValue, $hasValue ? $value : null, $expires);

        verify($item->set($value))->same($item);
        verify($item->isHit())->true();
        verify($item->isExpired())->false();
        verify((new ClassAccessor($item))->getProperty('expires'))->same($wasHit ? $expires : null);
    }

    public function isExpiredDataProvider(): array
    {
        return [
            'not expired' => [ '+30 seconds', false ],
            'expired' => [ '-30 seconds', true ],
            'expired right now' => [ 'now', true ]
        ];
    }

    /**
     * @dataProvider isExpiredDataProvider
     *
     * @param string $expires
     * @param bool   $isExpired
     *
     * @throws Exception
     */
    public function testIsExpired(string $expires, bool $isExpired)
    {
        $expires = new DateTimeImmutable($expires);
        $item = new DependencyCacheItem('key', false, null, $expires);

        verify($item->isExpired())->equals($isExpired);
    }

    public function testExpiresAtWithNull()
    {
        $item = new DependencyCacheItem('key', false);

        verify($item->expiresAt(null))->same($item);
        verify((new ClassAccessor($item))->getProperty('expires'))->equals(null);
    }

    public function testExpiresAtWithValidDate()
    {
        $expiry = new DateTimeImmutable('now');
        $item = new DependencyCacheItem('key', false);

        verify($item->expiresAt($expiry))->same($item);
        verify((new ClassAccessor($item))->getProperty('expires'))->equals($expiry);
    }

    public function testExpiresAtWithInvalidDate()
    {
        $item = new DependencyCacheItem('key', false);
        $this->expectException('InvalidArgumentException');

        /** @noinspection PhpParamsInspection */
        $item->expiresAt('i am invalid');
    }

    public function expiresAfterDataProvider(): array
    {
        return [
            'time in seconds' => [ 60, 60 ],
            'date interval' => [ new DateInterval('PT60S'), 60 ],
            'null' => [ null, null ],
            'invalid interval' => [ 'foo', null ]
        ];
    }

    /**
     * @dataProvider expiresAfterDataProvider
     *
     * @param int|DateTimeInterface|null $time
     * @param int|null                   $curDateOffset
     *
     * @throws Exception
     */
    public function testExpiresAfter($time, ?int $curDateOffset)
    {
        // Calculate the expected expiry date in here, to avoid the time gap
        // between the data provider and the test execution
        $expectedDate = is_null($curDateOffset) ? null : new DateTimeImmutable('+' . $curDateOffset . 'seconds');

        $item = new DependencyCacheItem('key', false);
        verify($item->expiresAfter($time))->same($item);

        $actualExpiry = (new ClassAccessor($item))->getProperty('expires');
        if ($expectedDate === null) {
            verify($actualExpiry)->null();
        }
        else {
            verify($actualExpiry)->instanceOf(DateTimeInterface::class);
            verify($actualExpiry->getTimestamp())->equals($expectedDate->getTimestamp(), 2);
        }
    }
}
