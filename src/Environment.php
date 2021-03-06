<?php declare(strict_types=1);

namespace Parable\Console;

class Environment
{
    public const TERMINAL_DEFAULT_HEIGHT = 24;
    public const TERMINAL_DEFAULT_WIDTH = 80;

    public function getTerminalWidth(): int
    {
        if (!$this->isShellAvailable()) {
            return self::TERMINAL_DEFAULT_WIDTH;
        }

        /** @psalm-suppress ForbiddenCode */
        return (int)shell_exec('TERM=ansi tput cols');
    }

    public function getTerminalHeight(): int
    {
        if (!$this->isShellAvailable()) {
            return self::TERMINAL_DEFAULT_HEIGHT;
        }

        /** @psalm-suppress ForbiddenCode */
        return (int)shell_exec('TERM=ansi tput lines');
    }

    public function isShellAvailable(): bool
    {
        return $this->isInteractiveShell() || ($this->isWindows() && getenv('shell'));
    }

    public function isWindows(): bool
    {
        return stripos(PHP_OS_FAMILY, 'Windows') === 0;
    }

    public function isInteractiveShell(): bool
    {
        return function_exists('posix_isatty') && posix_isatty(0);
    }
}
