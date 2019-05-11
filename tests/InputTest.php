<?php declare(strict_types=1);

namespace Parable\Console\Tests;

use Parable\Console\Environment;
use Parable\Console\Exception;
use Parable\Console\Input;
use PHPUnit\Framework\MockObject\MockObject;

class InputTest extends AbstractTestClass
{
    /**
     * @var Environment|MockObject
     */
    protected $environment;

    /**
     * @var Input|MockObject
     */
    protected $input;

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
            ['getRaw', 'enableShowInput', 'disableShowInput', 'enableRequireReturn', 'disableRequireReturn']
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

    public function testGetKeyPress(): void
    {
        $this->setInputResponse('i');

        $this->input->expects($this->atLeastOnce())->method('disableShowInput');
        $this->input->expects($this->atLeastOnce())->method('disableRequireReturn');

        $this->input->expects($this->atLeastOnce())->method('enableShowInput');
        $this->input->expects($this->atLeastOnce())->method('enableRequireReturn');

        self::assertSame('i', $this->input->getKeyPress());
    }

    public function testGetKeyPressDetectsSpecialKeys(): void
    {
        $this->setInputResponse(urldecode("%1B"));
        self::assertSame('esc', $this->input->getKeyPress());

        $this->setInputResponse(urldecode("%0A"));
        self::assertSame('enter', $this->input->getKeyPress());

        $this->setInputResponse(urldecode("%7F"));
        self::assertSame('backspace', $this->input->getKeyPress());

        $this->setInputResponse(urldecode("%1BOP"));
        self::assertSame('F1', $this->input->getKeyPress());

        $this->setInputResponse(urldecode("%1BOQ"));
        self::assertSame('F2', $this->input->getKeyPress());

        $this->setInputResponse(urldecode("%1BOR"));
        self::assertSame('F3', $this->input->getKeyPress());

        $this->setInputResponse(urldecode("%1BOS"));
        self::assertSame('F4', $this->input->getKeyPress());

        $this->setInputResponse(urldecode("%1B%5B15%7E"));
        self::assertSame('F5', $this->input->getKeyPress());

        $this->setInputResponse(urldecode("%1B%5B17%7E"));
        self::assertSame('F6', $this->input->getKeyPress());

        $this->setInputResponse(urldecode("%1B%5B18%7E"));
        self::assertSame('F7', $this->input->getKeyPress());

        $this->setInputResponse(urldecode("%1B%5B19%7E"));
        self::assertSame('F8', $this->input->getKeyPress());

        $this->setInputResponse(urldecode("%1B%5B20%7E"));
        self::assertSame('F9', $this->input->getKeyPress());

        $this->setInputResponse(urldecode("%1B%5B21%7E"));
        self::assertSame('F10', $this->input->getKeyPress());

        $this->setInputResponse(urldecode("%1B%5B23%7E%1B"));
        self::assertSame('F11', $this->input->getKeyPress());

        $this->setInputResponse(urldecode("%1B%5B24%7E%08"));
        self::assertSame('F12', $this->input->getKeyPress());

        $this->setInputResponse(urldecode("%1B%5BD"));
        self::assertSame('arrow_left', $this->input->getKeyPress());

        $this->setInputResponse(urldecode("%1B%5BC"));
        self::assertSame('arrow_right', $this->input->getKeyPress());

        $this->setInputResponse(urldecode("%1B%5BB"));
        self::assertSame('arrow_down', $this->input->getKeyPress());

        $this->setInputResponse(urldecode("%1B%5BA"));
        self::assertSame('arrow_up', $this->input->getKeyPress());
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
