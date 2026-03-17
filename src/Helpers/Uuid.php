<?php

namespace NiklasSchmitt\Saml2\Helpers;

/**
 * Lightweight RFC 9562 UUIDv7 generator.
 *
 * The UUIDv7 monotonic increment strategy is adapted from MIT-licensed
 * implementations in ramsey/uuid and symfony/uid.
 *
 * @internal
 */
final class Uuid
{
    private static string $lastTimestampMs = '';
    private static string $lastRandom = '';

    public static function uuid7(?\DateTimeInterface $time = null): string
    {
        $timestampMs = self::timestampMilliseconds($time);

        if (self::$lastRandom === '' || $timestampMs !== self::$lastTimestampMs) {
            self::$lastRandom = random_bytes(10);
            self::$lastTimestampMs = $timestampMs;
        } else {
            self::$lastRandom = self::incrementRandomPart(self::$lastRandom);

            if (self::$lastRandom === str_repeat("\x00", 10)) {
                do {
                    usleep(1000);
                    $timestampMs = self::timestampMilliseconds();
                } while ((int) $timestampMs <= (int) self::$lastTimestampMs);

                self::$lastRandom = random_bytes(10);
                self::$lastTimestampMs = $timestampMs;
            }
        }

        $bytes = self::packTimestampMs($timestampMs) . self::$lastRandom;

        // Set version to 7.
        $bytes[6] = chr((ord($bytes[6]) & 0x0F) | 0x70);
        // Set variant to RFC 4122/9562.
        $bytes[8] = chr((ord($bytes[8]) & 0x3F) | 0x80);

        return self::formatBytes($bytes);
    }

    private static function formatBytes(string $bytes): string
    {
        $hex = bin2hex($bytes);

        return sprintf(
            '%s-%s-%s-%s-%s',
            substr($hex, 0, 8),
            substr($hex, 8, 4),
            substr($hex, 12, 4),
            substr($hex, 16, 4),
            substr($hex, 20, 12),
        );
    }

    private static function timestampMilliseconds(?\DateTimeInterface $time = null): string
    {
        if ($time !== null) {
            return $time->format('Uv');
        }

        [$microseconds, $seconds] = explode(' ', microtime());

        return $seconds . substr($microseconds, 2, 3);
    }

    private static function packTimestampMs(string $timestampMs): string
    {
        $value = (int) $timestampMs;
        $bytes = '';

        for ($i = 5; $i >= 0; --$i) {
            $bytes = chr($value & 0xFF) . $bytes;
            $value >>= 8;
        }

        return $bytes;
    }

    private static function incrementRandomPart(string $random): string
    {
        $bytes = $random;

        for ($i = 9; $i >= 0; --$i) {
            $value = ord($bytes[$i]) + 1;
            $bytes[$i] = chr($value & 0xFF);

            if ($value <= 0xFF) {
                break;
            }
        }

        return $bytes;
    }
}
