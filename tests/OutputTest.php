<?php declare(strict_types=1);

namespace Parable\Console\Tests;

use Parable\Console\Environment;
use Parable\Console\Output;
use Parable\Console\Tags;

class OutputTest extends AbstractTestClass
{
    /** @var Output|\PHPUnit_Framework_MockObject_MockObject */
    protected $output;

    /** @var Environment */
    protected $environment;

    /** @var string */
    protected $defaultTag = "\e[0m";

    protected function setUp(): void
    {
        parent::setUp();

        // We mock out parseTags, because it adds too many escape codes. We'll test parseTags concretely later.
        $tags = $this->createPartialMock(Tags::class, ['parse']);
        $tags->method('parse')
            ->withAnyParameters()
            ->willReturnCallback(function ($string) {
                return $string . $this->defaultTag;
            });

        $this->container->store($tags, Tags::class);

        $this->output = $this->createPartialMock(Output::class, ['isInteractiveShell']);
        $this->output->__construct(...$this->container->getDependenciesFor(Output::class));

        // Make sure Output always thinks it's not in an interactive shell
        $this->output
            ->method('isInteractiveShell')
            ->withAnyParameters()
            ->willReturn(false);

        $this->environment = $this->container->get(Environment::class);
    }

    public function testWrite(): void
    {
        $this->output->write('OK');
        $content = $this->getActualOutputAndClean();

        self::assertSameWithTag("OK", $content);
    }

    public function testWriteln(): void
    {
        $this->output->writeln('OK');
        $content = $this->getActualOutputAndClean();

        self::assertSameWithTag("OK\n", $content);
    }

    public function testWritelnWithArray(): void
    {
        $this->output->writelns([
            'line1',
            'line2'
        ]);
        $content = $this->getActualOutputAndClean();

        self::assertSameWithTag("line1\nline2\n", $content);
    }

    public function testNewline(): void
    {
        // Just one.
        $this->output->newline();
        self::assertSame("\n", $this->getActualOutputAndClean());

        // Now multiple
        $this->output->newline(3);
        self::assertSame("\n\n\n", $this->getActualOutputAndClean());
    }

    public function testCursorForward(): void
    {
        $this->output->cursorForward(1);
        self::assertSameWithTag("\e[1C", $this->getActualOutputAndClean());
    }

    public function testCursorBackward(): void
    {
        $this->output->cursorBackward(1);
        self::assertSameWithTag("\e[1D", $this->getActualOutputAndClean());
    }

    public function testCursorUp(): void
    {
        $this->output->cursorUp(1);
        self::assertSameWithTag("\e[1A", $this->getActualOutputAndClean());
    }

    public function testCursorDown(): void
    {
        $this->output->cursorDown(1);
        self::assertSameWithTag("\e[1B", $this->getActualOutputAndClean());
    }

    public function testCursorPlace(): void
    {
        $this->output->cursorPlace(4, 8);
        self::assertSameWithTag("\e[4;8H", $this->getActualOutputAndClean());
    }

    public function testCursorPlaceDisablesClearLine(): void
    {
        $this->output->write("stuff!");
        self::assertTrue($this->output->isClearLineEnabled());

        $this->output->cursorPlace(1, 1);
        self::assertFalse($this->output->isClearLineEnabled());

        // This should do nothing
        $this->output->clearLine();

        // If clear line had worked, there would be many spaces. The string we're expecting does not.
        self::assertSame("stuff!\e[0m\e[1;1H\e[0m", $this->getActualOutputAndClean());
    }

    public function testCls(): void
    {
        $this->output->cls();
        self::assertSameWithTag("\ec", $this->getActualOutputAndClean());
    }

    public function testClearLine(): void
    {
        $this->output->write("12345");
        $this->output->clearLine();
        $this->output->write("no");

        // Use urlencode because the carriage return escape codes are annoying to escape otherwise
        $output = urlencode($this->getActualOutputAndClean());

        // Check that we've got 2 carriage returns and then remove %0D (carriage return)
        self::assertSame(2, substr_count($output, "%0D"));
        $output = str_replace("%0D", "", $output);

        // Straight up remove %1B (backslash) and %5B (square bracket) combinations (the reset style \[0m)
        $output = str_replace("%1B%5B0m", "", $output);

        // Check that we've got the correct amount of spaces (+)
        $spaces = str_repeat("+", $this->environment->getTerminalWidth());

        self::assertSame(
            $output,
            "12345{$spaces}no"
        );
    }

    public function testWriteErrorBlock(): void
    {
        $this->output->writeErrorBlock(['error']);

        $output = [
            $this->addTag(""),
            $this->addTag(" <error>┌───────┐</error>"),
            $this->addTag(" <error>│ error │</error>"),
            $this->addTag(" <error>└───────┘</error>"),
            $this->addTag(""),
            "",
        ];

        self::assertSame(
            implode("\n", $output),
            $this->getActualOutputAndClean()
        );
    }

    public function testWriteInfoBlock(): void
    {
        $this->output->writeInfoBlock(['info']);

        $output = [
            $this->addTag(""),
            $this->addTag(" <info>┌──────┐</info>"),
            $this->addTag(" <info>│ info │</info>"),
            $this->addTag(" <info>└──────┘</info>"),
            $this->addTag(""),
            "",
        ];

        self::assertSame(
            implode("\n", $output),
            $this->getActualOutputAndClean()
        );
    }

    public function testWriteSuccessBlock(): void
    {
        $this->output->writeSuccessBlock(['success']);

        $output = [
            $this->addTag(""),
            $this->addTag(" <success>┌─────────┐</success>"),
            $this->addTag(" <success>│ success │</success>"),
            $this->addTag(" <success>└─────────┘</success>"),
            $this->addTag(""),
            "",
        ];

        self::assertSame(
            implode("\n", $output),
            $this->getActualOutputAndClean()
        );
    }

    public function testWriteBlockWithAnyTag(): void
    {
        $this->output->writeBlock(['any block'], ['anytag']);

        $output = [
            $this->addTag(""),
            $this->addTag(" <anytag>┌───────────┐</anytag>"),
            $this->addTag(" <anytag>│ any block │</anytag>"),
            $this->addTag(" <anytag>└───────────┘</anytag>"),
            $this->addTag(""),
            "",
        ];

        self::assertSame(
            implode("\n", $output),
            $this->getActualOutputAndClean()
        );
    }

    public function testWriteBlockWithTagsUsingMultipleTags(): void
    {
        $this->output->writeBlock(['any block'], ["1", "2", "3"]);

        $output = [
            $this->addTag(""),
            $this->addTag(" <1><2><3>┌───────────┐</1></2></3>"),
            $this->addTag(" <1><2><3>│ any block │</1></2></3>"),
            $this->addTag(" <1><2><3>└───────────┘</1></2></3>"),
            $this->addTag(""),
            "",
        ];

        self::assertSame(
            implode("\n", $output),
            $this->getActualOutputAndClean()
        );
    }

    public function testWriteBlockWithTagsUsingNoTagsOutputsNoTags(): void
    {
        $this->output->writeBlock(['any block'], []);

        $output = [
            $this->addTag(""),
            $this->addTag(" ┌───────────┐"),
            $this->addTag(" │ any block │"),
            $this->addTag(" └───────────┘"),
            $this->addTag(""),
            "",
        ];

        self::assertSame(
            implode("\n", $output),
            $this->getActualOutputAndClean()
        );
    }

    public function testWriteBlockWithTagsHandlesLineBreaksCorrectly(): void
    {
        $this->output->writeBlock(["\nGive Me\n\nLots of newlines!\nThis should be the longest line.\n"], ['tag']);

        $output = [
            $this->addTag(""),
            $this->addTag(" <tag>┌──────────────────────────────────┐</tag>"),
            $this->addTag(" <tag>│                                  │</tag>"),
            $this->addTag(" <tag>│ Give Me                          │</tag>"),
            $this->addTag(" <tag>│                                  │</tag>"),
            $this->addTag(" <tag>│ Lots of newlines!                │</tag>"),
            $this->addTag(" <tag>│ This should be the longest line. │</tag>"),
            $this->addTag(" <tag>│                                  │</tag>"),
            $this->addTag(" <tag>└──────────────────────────────────┘</tag>"),
            $this->addTag(""),
            "",
        ];

        self::assertSame(
            implode("\n", $output),
            $this->getActualOutputAndClean()
        );
    }

    protected function assertSameWithTag(string $expected, string $actual): void
    {
        $expected = $this->addTag($expected);
        self::assertSame($expected, $actual);
    }

    protected function addTag(string $value, int $amount = 1)
    {
        $defaultTag = str_repeat($this->defaultTag, $amount);

        if (strpos($value, "\n") !== false) {
            // If there's new lines, the default tag is placed just before the newline.
            // At the end of the string, there won't be another default tag.
            $value = str_replace("\n", "{$defaultTag}\n", $value);
        } else {
            // If this is just a line with no newline, there will be a default tag at the end
            $value = $value . $defaultTag;
        }

        return $value;
    }
}
