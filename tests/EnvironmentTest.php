<?php declare(strict_types=1);

namespace Parable\Console\Tests;

use Parable\Console\Environment;

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
