<?php declare(strict_types=1);

namespace Parable\Console;

class Input
{
    public function __construct(
        protected Environment $environment
    ) {
    }

    public function get(): string
    {
        return trim($this->getRaw());
    }

    protected function getRaw(): string
    {
        return fread(STDIN, 10000);
    }

    public function enableShowInput(): void
    {
        if ($this->environment->isInteractiveShell()) {
            system('stty echo');
        }
    }

    public function disableShowInput(): void
    {
        if ($this->environment->isInteractiveShell()) {
            system('stty -echo');
        }
    }

    public function getHidden(): string
    {
        if ($this->environment->isWindows()) {
            throw Exception::fromMessage("Hidden input is not supported on windows.");
        }

        $this->disableShowInput();
        $input = $this->get();
        $this->enableShowInput();

        return $input;
    }

    public function getYesNo(bool $default = true): bool
    {
        $value = strtolower($this->get());

        // Y/N values are ALWAYS directly returned as true/false
        if ($value === 'y') {
            return true;
        }

        if ($value === 'n') {
            return false;
        }

        // If no value, we return the default value
        if (empty($value)) {
            return $default;
        }

        // Anything else should be considered false
        return false;
    }

    public function __destruct()
    {
        $this->enableShowInput();
    }
}
