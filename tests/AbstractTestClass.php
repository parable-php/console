<?php declare(strict_types=1);

namespace Parable\Console\Tests;

use Parable\Di\Container;
use PHPUnit\Framework\TestCase;

class AbstractTestClass extends TestCase
{
    protected Container $container;

    protected function setUp(): void
    {
        parent::setUp();

        $this->container = new Container();

        $GLOBALS["_SERVER"]["argv"] = [];
    }

    /**
     * Returns the actual output form the default PHPUnit output buffer,
     * and cleans 1(!) level, clearing the most recent buffer level.
     *
     * @return string
     */
    public function getActualOutputAndClean(): string
    {
        $content = $this->getActualOutput();
        ob_clean();
        return $content;
    }

    /**
     * @return array
     */
    public function dpTrueFalse(): array
    {
        return [
            [true],
            [false],
        ];
    }
}
