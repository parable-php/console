<?php declare(strict_types=1);

namespace Parable\Console\Tests;

use Parable\Console\Tags;

class TagsTest extends AbstractTestClass
{
    protected string $defaultTag = "\e[0m";

    public function testParseTags(): void
    {
        $tags = $this->container->build(Tags::class);

        // Since tags are escaped with the defaultTag at the end, we'll need 2
        self::assertSame($this->addTag("\e[;32mgreen", 2), $tags->parse('<green>green</green>'));
        self::assertSame($this->addTag("\e[;31mred", 2), $tags->parse('<red>red</red>'));

        // And a more complex one, with both a fore- and a background color
        // Since tags are escaped with the defaultTag at the end and there's two tags, we'll need 3
        self::assertSame(
            $this->addTag("\e[;31m\e[47mred on lightgray", 3),
            $tags->parse('<red><bg_light_gray>red on lightgray</bg_light_gray></red>')
        );
    }

    public function testTagSetWorks(): void
    {
        $tags = $this->container->build(Tags::class);

        self::assertSame(
            $this->addTag("\e[;97m\e[41mthis is an error tag set", 2),
            $tags->parse('<error>this is an error tag set</error>')
        );
    }

    public function testUnknownTags(): void
    {
        $tags = $this->container->build(Tags::class);

        self::assertSame($this->addTag('<tag>unknown</tag>'), $tags->parse('<tag>unknown</tag>'));
    }

    protected function addTag(string $value, int $amount = 1)
    {
        $defaultTag = str_repeat($this->defaultTag, $amount);

        if (str_contains($value, "\n")) {
            // If there's new lines, the default tag is placed just before the newline.
            // At the end of the string, there won't be another default tag.
            $value = str_replace("\n", "{$defaultTag}\n", $value);
        } else {
            // If this is just a line with no newline, there will be a default tag at the end
            $value .= $defaultTag;
        }

        return $value;
    }
}
