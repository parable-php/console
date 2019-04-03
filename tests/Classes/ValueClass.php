<?php declare(strict_types=1);

namespace Parable\Console\Tests\Classes;

class ValueClass
{
    protected static $value;

    public static function set($value): void
    {
        self::$value = $value;
    }

    public static function get()
    {
        return self::$value;
    }

    public static function clear(): void
    {
        self::$value = null;
    }
}
