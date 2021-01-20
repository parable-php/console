<?php declare(strict_types=1);

namespace Parable\Console\Tests;

use Parable\Console\Environment;
use Parable\Console\Exception;
use Parable\Console\Input;
use PHPUnit\Framework\MockObject\MockObject;

class InputTest extends AbstractTestClass
{
    protected Environment|MockObject $environment;
    protected Input|MockObject $input;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setInputResponse('default response');
    }

    protected function createInput($windows = false): void
    {
        $this->environment = $this->createPartialMock(Environment::class, ['isWindows']);

        $this->environment
            ->method('isWindows')
            ->willReturn($windows);

        $this->input = $this->createPartialMock(
            Input::class,
            ['getRaw', 'enableShowInput', 'disableShowInput']
        );

        $this->input->__construct($this->environment);
    }

    protected function setInputResponse(string $value): void
    {
        $this->createInput();
        $this->input
            ->method('getRaw')
            ->willReturn($value);
    }

    public function testGetInputFromUser(): void
    {
        $this->setInputResponse(' I typed this! ');

        self::assertSame('I typed this!', $this->input->get());
    }

    public function testGetYesNo(): void
    {
        $this->setInputResponse('y');
        self::assertTrue($this->input->getYesNo());
        self::assertTrue($this->input->getYesNo(true));
        self::assertTrue($this->input->getYesNo(false));

        $this->setInputResponse('n');
        self::assertFalse($this->input->getYesNo());
        self::assertFalse($this->input->getYesNo(true));
        self::assertFalse($this->input->getYesNo(false));

        // Empty always returns default, true on no default.
        $this->setInputResponse('');
        self::assertTrue($this->input->getYesNo());
        self::assertTrue($this->input->getYesNo(true));
        self::assertFalse($this->input->getYesNo(false));

        // If it's not either 'y', 'n', or empty, we default to false always.
        $this->setInputResponse('this is not even a valid answer');
        self::assertFalse($this->input->getYesNo(true));
        self::assertFalse($this->input->getYesNo());
        self::assertFalse($this->input->getYesNo(false));
    }

    public function testGetHidden(): void
    {
        $this->setInputResponse('y');

        $this->input->expects($this->once())->method('disableShowInput');
        $this->input->expects($this->once())->method('enableShowInput');

        self::assertSame('y', $this->input->getHidden());
    }

    public function testGetHiddenThrowsOnWindows(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Hidden input is not supported on windows.');

        $this->createInput(true);

        $this->input->getHidden();
    }
}
