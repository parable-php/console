<?php declare(strict_types=1);

namespace Parable\Console;

class Output
{
    /**
     * @var Environment
     */
    protected $environment;

    /**
     * @var Tags
     */
    protected $tags;

    /**
     * @var bool
     */
    protected $clearLineEnabled = false;

    public function __construct(
        Environment $environment,
        Tags $tags
    ) {
        $this->environment = $environment;
        $this->tags = $tags;
    }

    public function write(string $string): void
    {
        $string = $this->tags->parse($string);

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

        $tagsOpen = '';
        $tagsClose = '';

        if (count($tags) > 0) {
            foreach ($tags as $tag) {
                $tagsOpen .= "<{$tag}>";
                $tagsClose .= "</{$tag}>";
            }
        }

        $outputLines = [
            "",
            sprintf(
                " %s┌%s┐%s",
                $tagsOpen,
                str_repeat("─", $strlen + 2),
                $tagsClose
            )
        ];

        foreach ($actualLines as $line) {
            $padding = str_repeat(" ", $strlen - mb_strlen($line));
            $outputLines[] = sprintf(
                " %s│ %s%s │%s",
                $tagsOpen,
                $line,
                $padding,
                $tagsClose
            );
        }

        $outputLines[] = sprintf(
            " %s└%s┘%s",
            $tagsOpen,
            str_repeat("─", $strlen + 2),
            $tagsClose
        );
        $outputLines[] = "";

        $this->writelns($outputLines);
    }
}
