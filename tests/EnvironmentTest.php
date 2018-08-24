<?php

namespace Parable\Console\Tests;

use Parable\Console\App;
use Parable\Console\Command;
use Parable\Console\Environment;
use Parable\Console\Input;
use Parable\Console\Output;
use Parable\Console\Parameter;

class EnvironmentTest extends AbstractTestClass
{
    /** @var Environment */
    protected $environment;

    protected function setUp()
    {
        parent::setUp();

        $this->environment = $this->createPartialMock(Environment::class, ['isInteractiveShell']);
    }

    public function testGetTerminalWidthReturnsDefaultIfNoInteractiveShell()
    {
        $this->environment->method('isInteractiveShell')->willReturn(false);

        self::assertSame(80, $this->environment->getTerminalWidth());
    }

    public function testGetTerminalHeightReturnsDefaultIfNoInteractiveShell()
    {
        $this->environment->method('isInteractiveShell')->willReturn(false);

        self::assertSame(25, $this->environment->getTerminalHeight());
    }
}
