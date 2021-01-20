<?php declare(strict_types=1);

namespace Parable\Console\Tests\Classes;

class ValueClass
{
    protected static mixed $value;

    public static function set(mixed $value): void
    {
        self::$value = $value;
    }

    public static function get(): mixed
    {
        return self::$value;
    }

    public static function clear(): void
    {
        self::$value = null;
    }
}
