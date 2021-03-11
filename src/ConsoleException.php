<?php declare(strict_types=1);

namespace Parable\Console;

class ConsoleException extends \Exception
{
    public static function fromMessage(string $message, ...$replacements): self
    {
        if (count($replacements) > 0) {
            $message = sprintf($message, ...$replacements);
        }

        return new static($message);
    }
}
