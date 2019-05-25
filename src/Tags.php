<?php declare(strict_types=1);

namespace Parable\Console;

use Throwable;

class Tags
{
    /**
     * @var string[]
     */
    protected $predefinedTags = [
        'default' => "\e[0m",
        'black' => "\e[;30m",
        'red' => "\e[;31m",
        'green' => "\e[;32m",
        'yellow' => "\e[;33m",
        'blue' => "\e[;34m",
        'magenta' => "\e[;35m",
        'cyan' => "\e[;36m",
        'light_gray' => "\e[;37m",
        'dark_gray' => "\e[;90m",
        'light_red' => "\e[;91m",
        'light_green' => "\e[;92m",
        'light_yellow' => "\e[;93m",
        'light_blue' => "\e[;94m",
        'light_magenta' => "\e[;95m",
        'light_cyan' => "\e[;96m",
        'white' => "\e[;97m",

        'bg_black' => "\e[40m",
        'bg_red' => "\e[41m",
        'bg_green' => "\e[42m",
        'bg_yellow' => "\e[43m",
        'bg_blue' => "\e[44m",
        'bg_magenta' => "\e[45m",
        'bg_cyan' => "\e[46m",
        'bg_light_gray' => "\e[47m",
        'bg_dark_gray' => "\e[100m",
        'bg_light_red' => "\e[101m",
        'bg_light_green' => "\e[102m",
        'bg_light_yellow' => "\e[103m",
        'bg_light_blue' => "\e[104m",
        'bg_light_magenta' => "\e[105m",
        'bg_light_cyan' => "\e[106m",
        'bg_white' => "\e[107m",
    ];

    /**
     * @var string[][]
     */
    protected $tagSets = [
        'error' => ['white', 'bg_red'],
        'success' => ['black', 'bg_green'],
        'info' => ['black', 'bg_yellow'],
    ];

    public function parse(string $line): string
    {
        $tags = $this->getTagsFromString($line);

        foreach ($tags as $tag) {
            try {
                $code = $this->getCodeFor($tag);
            } catch (Throwable $throwable) {
                continue;
            }

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

        return array_unique($tags);
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
