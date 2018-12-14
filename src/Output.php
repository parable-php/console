<?php

namespace Parable\Console;

use Throwable;

class Output
{
    /**
     * @var Environment
     */
    protected $environment;

    /** @var array */
    protected $predefinedTags = [
        'default'          => "\e[0m",
        'black'            => "\e[;30m",
        'red'              => "\e[;31m",
        'green'            => "\e[;32m",
        'yellow'           => "\e[;33m",
        'blue'             => "\e[;34m",
        'magenta'          => "\e[;35m",
        'cyan'             => "\e[;36m",
        'light_gray'       => "\e[;37m",
        'dark_gray'        => "\e[;90m",
        'light_red'        => "\e[;91m",
        'light_green'      => "\e[;92m",
        'light_yellow'     => "\e[;93m",
        'light_blue'       => "\e[;94m",
        'light_magenta'    => "\e[;95m",
        'light_cyan'       => "\e[;96m",
        'white'            => "\e[;97m",

        'bg_black'         => "\e[40m",
        'bg_red'           => "\e[41m",
        'bg_green'         => "\e[42m",
        'bg_yellow'        => "\e[43m",
        'bg_blue'          => "\e[44m",
        'bg_magenta'       => "\e[45m",
        'bg_cyan'          => "\e[46m",
        'bg_light_gray'    => "\e[47m",
        'bg_dark_gray'     => "\e[100m",
        'bg_light_red'     => "\e[101m",
        'bg_light_green'   => "\e[102m",
        'bg_light_yellow'  => "\e[103m",
        'bg_light_blue'    => "\e[104m",
        'bg_light_magenta' => "\e[105m",
        'bg_light_cyan'    => "\e[106m",
        'bg_white'         => "\e[107m",
    ];

    protected $tagSets = [
        'error'   => ['white', 'bg_red'],
        'success' => ['black', 'bg_green'],
        'info'    => ['black', 'bg_yellow'],
    ];

    protected $clearLineEnabled = false;

    public function __construct(
        Environment $environment
    ) {
        $this->environment = $environment;
    }

    public function write(string $string): void
    {
        $string = $this->parseTags($string);

        $this->enableClearLine();

        echo $string;
    }

    public function writeln(string $line): void
    {
        $this->write($line);
        $this->newline();
    }

    public function writelns(array $lines): void
    {
        foreach ($lines as $line) {
            $this->writeln($line);
        }
    }

    public function newline(int $count = 1): void
    {
        $this->disableClearLine();

        echo str_repeat(PHP_EOL, $count);
    }

    public function cursorForward(int $characters = 1): void
    {
        $this->write("\e[{$characters}C");
    }

    public function cursorBackward(int $characters = 1)
    {
        $this->write("\e[{$characters}D");
    }

    public function cursorUp(int $characters = 1): void
    {
        $this->write("\e[{$characters}A");
        $this->disableClearLine();
    }

    public function cursorDown(int $characters = 1): void
    {
        $this->write("\e[{$characters}B");
        $this->disableClearLine();
    }

    public function cursorPlace(int $line = 0, int $column = 0): void
    {
        $this->write("\e[{$line};{$column}H");
        $this->disableClearLine();
    }

    public function cursorReset(): void
    {
        $this->write("\r");
    }

    public function cls(): void
    {
        $this->disableClearLine();
        $this->write("\ec");
    }

    public function enableClearLine(): void
    {
        $this->clearLineEnabled = true;
    }

    public function disableClearLine(): void
    {
        $this->clearLineEnabled = false;
    }

    public function isClearLineEnabled(): bool
    {
        return $this->clearLineEnabled;
    }

    public function clearLine(): void
    {
        if (!$this->isClearLineEnabled()) {
            return;
        }

        $this->cursorReset();
        $this->write(str_repeat(' ', $this->environment->getTerminalWidth()));
        $this->cursorReset();

        $this->disableClearLine();
    }

    /**
     * @param string[] $lines
     */
    public function writeErrorBlock(array $lines): void
    {
        $this->writeBlock($lines, ['error']);
    }

    /**
     * @param string[] $lines
     */
    public function writeInfoBlock(array $lines): void
    {
        $this->writeBlock($lines, ['info']);
    }

    /**
     * @param string[] $lines
     */
    public function writeSuccessBlock(array $lines): void
    {
        $this->writeBlock($lines, ['success']);
    }

    /**
     * @param string[] $lines
     * @param string[] $tags
     */
    public function writeBlock(array $lines, array $tags = []): void
    {
        $strlen = 0;

        $actualLines = [];
        foreach ($lines as $line) {
            $actualLines = array_merge($actualLines, explode("\n", $line));
        }

        foreach ($actualLines as $line) {
            $strlen = max($strlen, mb_strlen($line));
        }

        $tagsOpen  = '';
        $tagsClose = '';

        if (count($tags) > 0) {
            foreach ($tags as $tag) {
                $tagsOpen  .= "<{$tag}>";
                $tagsClose .= "</{$tag}>";
            }
        }

        $outputLines = [
            "",
            " {$tagsOpen}┌" . str_repeat("─", $strlen + 2) . "┐{$tagsClose}",
        ];

        foreach ($actualLines as $line) {
            $padding = str_repeat(" ", $strlen - mb_strlen($line));
            $outputLines[] = " {$tagsOpen}│ {$line}{$padding} │{$tagsClose}";
        }

        $outputLines[] = " {$tagsOpen}└" . str_repeat("─", $strlen + 2) . "┘{$tagsClose}";
        $outputLines[] = "";

        $this->writelns($outputLines);
    }

    public function parseTags(string $line): string
    {
        $tags = $this->getTagsFromString($line);

        foreach ($tags as $tag) {
            $code = $this->getCodeFor($tag);

            $line = str_replace("<{$tag}>", $code, $line);
            $line = str_replace("</{$tag}>", $this->predefinedTags['default'], $line);
        }

        return $line . $this->predefinedTags['default'];
    }

    protected function getTagsFromString(string $string): array
    {
        preg_match_all('/<(?!\/)(.|\n)*?>/', $string, $matches);

        $tags = [];
        foreach ($matches[0] as $tag) {
            $tags[] = trim($tag, '<>');
        }
        return $tags;
    }

    protected function getCodeFor(string $tag): string
    {
        try {
            return $this->getCodeForPredefined($tag);
        } catch (Throwable $throwable) {
        }

        try {
            $tags = $this->getTagsForSet($tag);

            $codes = '';
            foreach ($tags as $tag) {
                $codes .= $this->getCodeForPredefined($tag);
            }
            return $codes;
        } catch (Throwable $throwable) {
        }

        throw Exception::fromMessage('No predefined or tag set found for <%s>.', $tag);
    }

    protected function getCodeForPredefined(string $tag): string
    {
        if (!isset($this->predefinedTags[$tag])) {
            throw Exception::fromMessage('Predefined tag <%s> not found.', $tag);
        }

        return $this->predefinedTags[$tag];
    }

    protected function getTagsForSet(string $tag): array
    {
        if (!isset($this->tagSets[$tag])) {
            throw Exception::fromMessage('Tag set <%s> not found.', $tag);
        }

        return $this->tagSets[$tag];
    }
}
