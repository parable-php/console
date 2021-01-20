<?php declare(strict_types=1);

namespace Parable\Console\Tests;

use Parable\Console\Environment;

class EnvironmentTest extends AbstractTestClass
{
    protected Environment $environment;

    protected function setUp(): void
    {
        parent::setUp();

        $this->environment = $this->createPartialMock(Environment::class, ['isInteractiveShell']);
    }

    public function testGetTerminalWidthAndHeightReturnNonZeroInteger(): void
    {
        $this->environment->method('isInteractiveShell')->willReturn(true);

        self::assertGreaterThan(0, $this->environment->getTerminalWidth());
        self::assertGreaterThan(0, $this->environment->getTerminalHeight());
    }

    public function testGetTerminalWidthReturnsDefaultIfNoInteractiveShell(): void
    {
        $this->environment->method('isInteractiveShell')->willReturn(false);

        self::assertSame(80, $this->environment->getTerminalWidth());
    }

    public function testGetTerminalHeightReturnsDefaultIfNoInteractiveShell(): void
    {
        $this->environment->method('isInteractiveShell')->willReturn(false);

        self::assertSame(24, $this->environment->getTerminalHeight());
    }
}
