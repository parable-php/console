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

        return (int)shell_exec('TERM=ansi tput cols');
    }

    public function getTerminalHeight(): int
    {
        if (!$this->isShellAvailable()) {
            return self::TERMINAL_DEFAULT_HEIGHT;
        }

        return (int)shell_exec('TERM=ansi tput lines');
    }

    public function isShellAvailable(): bool
    {
        return $this->isInteractiveShell() || ($this->isWindows() && getenv('shell'));
    }

    public function isWindows(): bool
    {
        return strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
    }

    public function isInteractiveShell(): bool
    {
        return function_exists('posix_isatty') && posix_isatty(0);
    }
}
