<?php declare(strict_types=1);

namespace Parable\Console;

class Input
{
    /**
     * @var Environment
     */
    protected $environment;

    /**
     * @var string[]
     */
    protected $specialKeys = [
        "esc" => "%1B",
        "enter" => "%0A",
        "backspace" => "%7F",
        "F1" => "%1BOP",
        "F2" => "%1BOQ",
        "F3" => "%1BOR",
        "F4" => "%1BOS",
        "F5" => "%1B%5B15%7E",
        "F6" => "%1B%5B17%7E",
        "F7" => "%1B%5B18%7E",
        "F8" => "%1B%5B19%7E",
        "F9" => "%1B%5B20%7E",
        "F10" => "%1B%5B21%7E",
        "F11" => "%1B%5B23%7E%1B",
        "F12" => "%1B%5B24%7E%08",
        "arrow_left" => "%1B%5BD",
        "arrow_right" => "%1B%5BC",
        "arrow_down" => "%1B%5BB",
        "arrow_up" => "%1B%5BA",
    ];

    public function __construct(
        Environment $environment
    ) {
        $this->environment = $environment;
    }

    public function get(): string
    {
        return trim($this->getRaw());
    }

    protected function getRaw(): string
    {
        return fread(STDIN, 10000);
    }

    public function getKeyPress(): string
    {
        $this->disableShowInput();
        $this->disableRequireReturn();

        $input = null;
        while (true) {
            $input = $this->getRaw();
            break;
        }

        $this->enableShowInput();
        $this->enableRequireReturn();

        $specialKey = $this->detectSpecialKey($input);

        return $specialKey ? $specialKey : (string)$input;
    }

    protected function detectSpecialKey(string $input): ?string
    {
        $specialKey = false;
        if (in_array(ord($input), [27, 127, 10])) {
            $specialKey = array_search(urlencode($input), $this->specialKeys);
        }

        return $specialKey ? $specialKey : null;
    }

    public function enableRequireReturn(): void
    {
        if ($this->environment->isInteractiveShell()) {
            system('stty -cbreak');
        }
    }

    public function disableRequireReturn(): void
    {
        if ($this->environment->isInteractiveShell()) {
            system('stty cbreak');
        }
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
        } elseif ($value === 'n') {
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
