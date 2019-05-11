<?php declare(strict_types=1);

namespace Parable\Console\Tests;

use Parable\Console\Exception;
use Parable\Console\Parameter;
use Parable\Console\Parameters\ArgumentParameter;
use Parable\Console\Parameters\OptionParameter;

class ParameterTest extends AbstractTestClass
{
    /** @var Parameter */
    protected $parameter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->parameter = new Parameter();
    }

    public function testParseParametersWorkedCorrectly(): void
    {
        $this->parameter->setCommandOptions([
            "option" => new OptionParameter("option"),
            "key"    => new OptionParameter("key"),
        ]);
        $this->parameter->setCommandArguments([
            new ArgumentParameter("arg1"),
        ]);

        $this->parameter->setParameters([
            './test.php',
            'command-to-run',
            '--option',
            'argument',
            '--key=value2',
        ]);

        $this->parameter->checkCommandOptions();
        $this->parameter->checkCommandArguments();

        self::assertSame('./test.php', $this->parameter->getScriptName());
        self::assertSame('command-to-run', $this->parameter->getCommandName());

        self::assertTrue($this->parameter->getOption('option'));
        self::assertSame("value2", $this->parameter->getOption('key'));

        self::assertSame(
            [
                "command-to-run",
                "--option",
                "argument",
                "--key=value2",
            ],
            $this->parameter->getParameters()
        );
    }

    public function testCommandNameIsReturnedProperlyIfGiven(): void
    {
        $this->parameter->setParameters([
            './test.php',
            'command-to-run',
        ]);

        self::assertSame('./test.php', $this->parameter->getScriptName());
        self::assertSame('command-to-run', $this->parameter->getCommandName());
    }

    public function testGetInvalidOptionReturnsNull(): void
    {
        self::assertNull($this->parameter->getOption('la-dee-dah'));
    }

    public function testCommandNameIsNullIfNotGiven(): void
    {
        $this->parameter->setParameters([
            './test.php',
        ]);

        self::assertSame('./test.php', $this->parameter->getScriptName());
        self::assertNull($this->parameter->getCommandName());
    }

    public function testCommandNameIsNullIfNotGivenButThereIsAnOptionGiven(): void
    {
        $this->parameter->setParameters([
            './test.php',
            '--option',
        ]);

        self::assertSame('./test.php', $this->parameter->getScriptName());
        self::assertNull($this->parameter->getCommandName());
    }

    public function testThrowsExceptionWhenOptionIsGivenButValueRequiredNotGiven(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Option '--option' requires a value, which is not provided.");

        $this->parameter->setParameters([
            './test.php',
            'command-to-run',
            '--option',
        ]);

        $this->parameter->setCommandOptions([
            "option" => new OptionParameter(
                "option",
                Parameter::OPTION_VALUE_REQUIRED
            ),
        ]);

        $this->parameter->checkCommandOptions();
    }

    public function testThrowsExceptionWhenFlagOptionIsGivenButValueRequiredNotGiven(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Option '-a' requires a value, which is not provided.");

        $this->parameter->setParameters([
            './test.php',
            'command-to-run',
            '-a',
        ]);

        $this->parameter->setCommandOptions([
            "option" => new OptionParameter(
                "a",
                Parameter::OPTION_VALUE_REQUIRED,
                null,
                true
            ),
        ]);

        $this->parameter->checkCommandOptions();
    }

    public function testOptionIsGivenAndValueRequiredAlsoGivenWorksProperly(): void
    {
        $this->parameter->setParameters([
            './test.php',
            'command-to-run',
            '--option=option-value'
        ]);

        $this->parameter->setCommandOptions([
            "option" => new OptionParameter(
                "option",
                Parameter::OPTION_VALUE_REQUIRED
            ),
        ]);
        $this->parameter->checkCommandOptions();

        self::assertSame('option-value', $this->parameter->getOption('option'));
    }

    public function testRequiredArgumentThrowsException(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Required argument with index #1 'numero2' not provided.");

        $this->parameter->setParameters([
            './test.php',
            'command-to-run',
            'arg1',
        ]);
        $this->parameter->setCommandArguments([
            new ArgumentParameter("numero1", Parameter::PARAMETER_REQUIRED),
            new ArgumentParameter("numero2", Parameter::PARAMETER_REQUIRED),
        ]);
        $this->parameter->checkCommandArguments();
    }

    public function testGetArgumentReturnsAppropriateValues(): void
    {
        $this->parameter->setParameters([
            './test.php',
            'command-to-run',
            'arg1',
            'arg2',
        ]);

        $this->parameter->setCommandArguments([
            new ArgumentParameter("numero1", Parameter::PARAMETER_REQUIRED),
            new ArgumentParameter("numero2", Parameter::PARAMETER_REQUIRED, 12),
            new ArgumentParameter("numero3", Parameter::PARAMETER_OPTIONAL, 24),
        ]);

        $this->parameter->checkCommandArguments();

        self::assertSame("arg1", $this->parameter->getArgument("numero1"));
        self::assertSame("arg2", $this->parameter->getArgument("numero2"));
        self::assertSame(24, $this->parameter->getArgument("numero3"));
    }

    public function testInvalidArgumentReturnsNull(): void
    {
        self::assertNull($this->parameter->getArgument("totally not"));
    }

    public function testMultipleOptionParameters(): void
    {
        $this->parameter->setParameters([
            './test.php',
            'command-to-run',
            '--option1=value1',
            '--option2',
            '--option3=value3',
        ]);

        $this->parameter->setCommandOptions([
            new OptionParameter("option1", Parameter::OPTION_VALUE_REQUIRED),
            new OptionParameter("option2"),
            new OptionParameter("option3", Parameter::OPTION_VALUE_REQUIRED),
        ]);

        $this->parameter->checkCommandOptions();

        self::assertSame(
            [
                'option1' => 'value1',
                'option2' => true,
                'option3' => 'value3',
            ],
            $this->parameter->getOptions()
        );
    }

    public function testArgumentsWorkProperly(): void
    {
        $this->parameter->setParameters([
            './test.php',
            'command-to-run',
            'argument1',
            'argument2 is a string',
            '--option1=value1',
            'argument3!',
            '--option2=value2',
            'argument4',
            'argument5',
        ]);

        $this->parameter->setCommandOptions([
            new OptionParameter("option1"),
            new OptionParameter("option2"),
        ]);
        $this->parameter->setCommandArguments([
            new ArgumentParameter("brg1", Parameter::PARAMETER_REQUIRED),
            new ArgumentParameter("arg2", Parameter::PARAMETER_OPTIONAL),
            new ArgumentParameter("arg3", Parameter::PARAMETER_OPTIONAL),
            new ArgumentParameter("arg4", Parameter::PARAMETER_OPTIONAL),
            new ArgumentParameter("arg5", Parameter::PARAMETER_OPTIONAL),
        ]);

        $this->parameter->checkCommandOptions();
        $this->parameter->checkCommandArguments();

        self::assertSame(
            [
                'option1' => 'value1',
                'option2' => 'value2',
            ],
            $this->parameter->getOptions()
        );
        self::assertSame(
            [
                'brg1' => 'argument1',
                'arg2' => 'argument2 is a string',
                'arg3' => 'argument3!',
                'arg4' => 'argument4',
                'arg5' => 'argument5',
            ],
            $this->parameter->getArguments()
        );
    }

    public function testSetCommandOptionsWithArrayThrowsException(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Options must be instances of Parameter\Option. invalid_option is not.");

        $this->parameter->setCommandOptions(["invalid_option" => []]);
    }

    public function testSetCommandArgumentsWithArrayThrowsException(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Arguments must be instances of Parameter\Argument. The item at index 0 is not.");

        $this->parameter->setCommandArguments([[]]);
    }

    public function testEnableDisableCommandNameKeepsArgumentOrderValid(): void
    {
        $this->parameter->setParameters([
            './test.php',
            'command-to-run',
            'argument1',
        ]);

        $this->parameter->setCommandArguments([
            new ArgumentParameter("arg1", Parameter::PARAMETER_OPTIONAL),
            new ArgumentParameter("arg2", Parameter::PARAMETER_OPTIONAL),
        ]);

        $this->parameter->checkCommandArguments();

        self::assertSame(
            [
                "arg1" => "argument1",
                "arg2" => null,
            ],
            $this->parameter->getArguments()
        );

        $this->parameter->disableCommandName();
        $this->parameter->checkCommandArguments();

        self::assertSame(
            [
                "arg1" => "command-to-run",
                "arg2" => "argument1",
            ],
            $this->parameter->getArguments()
        );

        $this->parameter->enableCommandName();
        $this->parameter->checkCommandArguments();

        self::assertSame(
            [
                "arg1" => "argument1",
                "arg2" => null,
            ],
            $this->parameter->getArguments()
        );
    }

    public function testParameterRequiredOnlyAcceptConstantValues(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Required must be one of the PARAMETER_* constants.");

        new ArgumentParameter("test", 418);
    }

    public function testParameterValueRequiredOnlyAcceptConstantValues(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Value type must be one of the OPTION_* constants.");

        new OptionParameter(
            "test",
            Parameter::PARAMETER_REQUIRED,
            418
        );
    }

    /**
     * @dataProvider dpGetOptionReturnsExpected
     *
     * @param string $parameter As provoked from cli
     * @param mixed  $default   Option default
     * @param mixed  $expected  Expected result
     */
    public function testGetOptionReturnsExpected($parameter, $default, $expected)
    {
        $parameters = [
            './test.php',
            'command-to-run',
        ];

        if (!empty($parameter)) {
            $parameters[] = $parameter;
        }

        $this->parameter->setParameters($parameters);
        $this->parameter->setCommandOptions([
            'option' => new OptionParameter(
                "option",
                Parameter::OPTION_VALUE_OPTIONAL,
                $default
            ),
        ]);

        $this->parameter->checkCommandOptions();

        self::assertEquals($expected, $this->parameter->getOption('option'));
    }

    /**
     * This does not test the case where the option doesn't exist.
     *
     * @return array
     */
    public function dpGetOptionReturnsExpected(): array
    {
        return [
            ['', null, null],
            ['', 0, 0],
            ['', '0', '0'],
            ['', false, false],
            ['--option', null, true], // This is "flag"-style
            ['--option', 0, 0],
            ['--option', '0', '0'],
            ['--option', false, false],
            ['--option=null', null, 'null'],
            ['--option=0', null, '0'],
            ['--option=false', null, 'false'],
        ];
    }

    public function testSingleShortOption(): void
    {
        $this->parameter->setCommandOptions([
            new OptionParameter("a", Parameter::OPTION_VALUE_OPTIONAL, null, true),
            new OptionParameter("b", Parameter::OPTION_VALUE_OPTIONAL, null, true),
        ]);

        $this->parameter->setParameters([
            './test.php',
            'command-to-run',
            '-a',
        ]);
        $this->parameter->checkCommandOptions();
        self::assertSame(
            [
                'a' => true,
                'b' => null,
            ],
            $this->parameter->getOptions()
        );
    }

    public function testSeparateShortOptions(): void
    {
        $this->parameter->setCommandOptions([
            new OptionParameter("a", Parameter::OPTION_VALUE_OPTIONAL, null, true),
            new OptionParameter("b", Parameter::OPTION_VALUE_OPTIONAL, null, true),
        ]);

        $this->parameter->setParameters([
            './test.php',
            'command-to-run',
            '-a',
            '-b',
        ]);
        $this->parameter->checkCommandOptions();
        self::assertSame(
            [
                'a' => true,
                'b' => true,
            ],
            $this->parameter->getOptions()
        );
    }

    public function testCombinedShortOptions(): void
    {
        $this->parameter->setCommandOptions([
            new OptionParameter("a", Parameter::OPTION_VALUE_OPTIONAL, null, true),
            new OptionParameter("b", Parameter::OPTION_VALUE_OPTIONAL, null, true),
            new OptionParameter("c", Parameter::OPTION_VALUE_OPTIONAL, null, true),
        ]);

        $this->parameter->setParameters([
            './test.php',
            'command-to-run',
            '-ac',
            'argument1',
        ]);
        $this->parameter->checkCommandOptions();
        self::assertSame(
            [
                'a' => true,
                'b' => null,
                'c' => true,
            ],
            $this->parameter->getOptions()
        );
    }

    public function testShortOptionAndOptionValuesSetWithEqualSign(): void
    {
        $optionA = new OptionParameter("a", Parameter::OPTION_VALUE_OPTIONAL, null, true);
        $optionB = new OptionParameter("b", Parameter::OPTION_VALUE_OPTIONAL, null, true);
        $optionC = new OptionParameter("c", Parameter::OPTION_VALUE_OPTIONAL, null, true);

        $this->parameter->setCommandOptions([$optionA, $optionB, $optionC]);

        $this->parameter->setParameters([
            './test.php',
            'command-to-run',
            '-ab=c',
        ]);
        $this->parameter->checkCommandOptions();
        self::assertSame(
            [
                'a' => true,
                'b' => 'c',
                'c' => null,
            ],
            $this->parameter->getOptions()
        );
    }

    public function testValueOptionsWithEqualSigns(): void
    {
        $this->parameter->setCommandOptions([
            new OptionParameter("aa", Parameter::OPTION_VALUE_OPTIONAL),
            new OptionParameter("bb", Parameter::OPTION_VALUE_OPTIONAL),
        ]);

        $this->parameter->setParameters([
            './test.php',
            'command-to-run',
            '--aa=test',
        ]);
        $this->parameter->checkCommandOptions();
        self::assertSame(
            [
                'aa' => 'test',
                'bb' => null,
            ],
            $this->parameter->getOptions()
        );
    }

    public function testSkippingUndefinedOptions(): void
    {
        $this->parameter->setCommandOptions([
            new OptionParameter("a", Parameter::OPTION_VALUE_OPTIONAL, null, true),
            new OptionParameter("c", Parameter::OPTION_VALUE_OPTIONAL, null, true),
        ]);

        $this->parameter->setParameters([
            './test.php',
            'command-to-run',
            '-abc',
            'test',
        ]);
        $this->parameter->checkCommandOptions();
        self::assertSame(
            [
                'a' => true,
                'c' => true,
            ],
            $this->parameter->getOptions()
        );
    }

    public function testFlagOptionCanOnlyHaveSingleLetterName(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Flag options can only have a single-letter name.');

        new OptionParameter("test", Parameter::OPTION_VALUE_OPTIONAL, null, true);
    }

    public function testLongOptionOnlyPickedUpFromDoubleDash(): void
    {
        $this->parameter->setCommandOptions([
            new OptionParameter("a"),
        ]);

        $this->parameter->setParameters([
            './test.php',
            'command-to-run',
            '-a=flag',
        ]);

        $this->parameter->checkCommandOptions();
        self::assertSame(
            [
                'a' => null,
            ],
            $this->parameter->getOptions()
        );

        $this->parameter->setParameters([
            './test.php',
            'command-to-run',
            '--a=flag',
        ]);

        $this->parameter->checkCommandOptions();
        self::assertSame(
            [
                'a' => 'flag',
            ],
            $this->parameter->getOptions()
        );
    }

    public function testFlagOptionOnlyPickedUpFromSingleDash(): void
    {
        $this->parameter->setCommandOptions([
            new OptionParameter("a", Parameter::OPTION_VALUE_OPTIONAL, null, true),
        ]);

        $this->parameter->setParameters([
            './test.php',
            'command-to-run',
            '--a=flag',
        ]);

        $this->parameter->checkCommandOptions();
        self::assertSame(
            [
                'a' => null,
            ],
            $this->parameter->getOptions()
        );

        $this->parameter->setParameters([
            './test.php',
            'command-to-run',
            '-a=flag',
        ]);

        $this->parameter->checkCommandOptions();
        self::assertSame(
            [
                'a' => 'flag',
            ],
            $this->parameter->getOptions()
        );
    }
}
