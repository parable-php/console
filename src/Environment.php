<?php

namespace Parable\Console;

class Environment
{
    const TERMINAL_DEFAULT_HEIGHT = 25;
    const TERMINAL_DEFAULT_WIDTH  = 80;

    /**
     * Return the terminal width. If not an interactive shell, return default.
     */
    public function getTerminalWidth(): int
    {
        if ($this->isShellAvailable()) {
            return (int)shell_exec('tput cols');
        }

        return self::TERMINAL_DEFAULT_WIDTH;
    }

    /**
     * Return the terminal height. If not an interactive shell, return default.
     */
    public function getTerminalHeight(): int
    {
        if ($this->isShellAvailable()) {
            return (int)shell_exec('tput lines');
        }

        return self::TERMINAL_DEFAULT_HEIGHT;
    }

    public function isShellAvailable(): bool
    {
        return $this->isInteractiveShell() || ($this->isWindows() && getenv('shell'));
    }

    public function isWindows(): bool
    {
        return strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
    }

    /**
     * Return whether we're currently in an interactive shell or not. Will always be false on Windows.
     */
    public function isInteractiveShell(): bool
    {
        return function_exists('posix_isatty') && posix_isatty(0);
    }
}
