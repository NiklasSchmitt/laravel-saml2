<?php

declare(strict_types=1);

namespace Slides\Saml2\Tests;

use PHPUnit\Framework\TestCase;
use Slides\Saml2\Helpers\Uuid;

/**
 * @internal
 */
class UuidTest extends TestCase
{
    public function testUuid7GeneratesRfc9562Version7Identifier(): void
    {
        $uuid = Uuid::uuid7();

        self::assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-7[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/',
            $uuid,
        );
    }

    public function testUuid7IsMonotonicWithinSameMillisecond(): void
    {
        $time = \DateTimeImmutable::createFromFormat('U.u', '1735689600.123000');

        self::assertInstanceOf(\DateTimeImmutable::class, $time);

        $first = Uuid::uuid7($time);
        $second = Uuid::uuid7($time);

        self::assertNotSame($first, $second);
        self::assertGreaterThan(0, strcmp($second, $first));
    }

    public function testUuid7UsesProvidedTimestamp(): void
    {
        $time = \DateTimeImmutable::createFromFormat('U.u', '1735689600.456000');

        self::assertInstanceOf(\DateTimeImmutable::class, $time);

        $uuid = Uuid::uuid7($time);
        $hex = str_replace('-', '', $uuid);
        $timestampHex = substr($hex, 0, 12);

        self::assertSame(sprintf('%012x', (int) $time->format('Uv')), $timestampHex);
    }

    public function testUuid7IsTimeSortableAcrossDifferentTimestamps(): void
    {
        $timeA = \DateTimeImmutable::createFromFormat('U.u', '1735689600.100000');
        $timeB = \DateTimeImmutable::createFromFormat('U.u', '1735689600.200000');
        $timeC = \DateTimeImmutable::createFromFormat('U.u', '1735689600.300000');

        self::assertInstanceOf(\DateTimeImmutable::class, $timeA);
        self::assertInstanceOf(\DateTimeImmutable::class, $timeB);
        self::assertInstanceOf(\DateTimeImmutable::class, $timeC);

        $uuidA = Uuid::uuid7($timeA);
        $uuidB = Uuid::uuid7($timeB);
        $uuidC = Uuid::uuid7($timeC);

        self::assertGreaterThan(0, strcmp($uuidB, $uuidA));
        self::assertGreaterThan(0, strcmp($uuidC, $uuidB));
    }
}
