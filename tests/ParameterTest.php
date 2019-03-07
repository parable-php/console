<?php declare(strict_types=1);

namespace Parable\Console\Tests;

use \Parable\Console\Parameter\Option;
use \Parable\Console\Parameter\Argument;

class ParameterTest extends AbstractTestClass
{
    /** @var \Parable\Console\Parameter */
    protected $parameter;

    protected function setUp()
    {
        parent::setUp();

        $this->parameter = new \Parable\Console\Parameter();
    }

    public function testParseParametersWorkedCorrectly()
    {
        $this->parameter->setCommandOptions([
            "option" => new Option("option"),
            "key"    => new Option("key"),
        ]);
        $this->parameter->setCommandArguments([
            new Argument("arg1"),
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

    public function testCommandNameIsReturnedProperlyIfGiven()
    {
        $this->parameter->setParameters([
            './test.php',
            'command-to-run',
        ]);

        self::assertSame('./test.php', $this->parameter->getScriptName());
        self::assertSame('command-to-run', $this->parameter->getCommandName());
    }

    public function testGetInvalidOptionReturnsNull()
    {
        self::assertNull($this->parameter->getOption('la-dee-dah'));
    }

    public function testCommandNameIsNullIfNotGiven()
    {
        $this->parameter->setParameters([
            './test.php',
        ]);

        self::assertSame('./test.php', $this->parameter->getScriptName());
        self::assertNull($this->parameter->getCommandName());
    }

    public function testCommandNameIsNullIfNotGivenButThereIsAnOptionGiven()
    {
        $this->parameter->setParameters([
            './test.php',
            '--option',
        ]);

        self::assertSame('./test.php', $this->parameter->getScriptName());
        self::assertNull($this->parameter->getCommandName());
    }

    public function testThrowsExceptionWhenOptionIsGivenButValueRequiredNotGiven()
    {
        $this->expectException(\Parable\Console\Exception::class);
        $this->expectExceptionMessage("Option '--option' requires a value, which is not provided.");

        $this->parameter->setParameters([
            './test.php',
            'command-to-run',
            '--option',
        ]);

        $this->parameter->setCommandOptions([
            "option" => new Option(
                "option",
                \Parable\Console\Parameter::OPTION_VALUE_REQUIRED
            ),
        ]);

        $this->parameter->checkCommandOptions();
    }

    public function testThrowsExceptionWhenFlagOptionIsGivenButValueRequiredNotGiven()
    {
        $this->expectException(\Parable\Console\Exception::class);
        $this->expectExceptionMessage("Option '-a' requires a value, which is not provided.");

        $this->parameter->setParameters([
            './test.php',
            'command-to-run',
            '-a',
        ]);

        $this->parameter->setCommandOptions([
            "option" => new Option(
                "a",
                \Parable\Console\Parameter::OPTION_VALUE_REQUIRED,
                null,
                true
            ),
        ]);

        $this->parameter->checkCommandOptions();
    }

    public function testOptionIsGivenAndValueRequiredAlsoGivenWorksProperly()
    {
        $this->parameter->setParameters([
            './test.php',
            'command-to-run',
            '--option=option-value'
        ]);

        $this->parameter->setCommandOptions([
            "option" => new Option(
                "option",
                \Parable\Console\Parameter::OPTION_VALUE_REQUIRED
            ),
        ]);
        $this->parameter->checkCommandOptions();

        self::assertSame('option-value', $this->parameter->getOption('option'));
    }

    public function testRequiredArgumentThrowsException()
    {
        $this->expectException(\Parable\Console\Exception::class);
        $this->expectExceptionMessage("Required argument with index #1 'numero2' not provided.");

        $this->parameter->setParameters([
            './test.php',
            'command-to-run',
            'arg1',
        ]);
        $this->parameter->setCommandArguments([
            new Argument("numero1", \Parable\Console\Parameter::PARAMETER_REQUIRED),
            new Argument("numero2", \Parable\Console\Parameter::PARAMETER_REQUIRED),
        ]);
        $this->parameter->checkCommandArguments();
    }

    public function testGetArgumentReturnsAppropriateValues()
    {
        $this->parameter->setParameters([
            './test.php',
            'command-to-run',
            'arg1',
            'arg2',
        ]);

        $this->parameter->setCommandArguments([
            new Argument("numero1", \Parable\Console\Parameter::PARAMETER_REQUIRED),
            new Argument("numero2", \Parable\Console\Parameter::PARAMETER_REQUIRED, 12),
            new Argument("numero3", \Parable\Console\Parameter::PARAMETER_OPTIONAL, 24),
        ]);

        $this->parameter->checkCommandArguments();

        self::assertSame("arg1", $this->parameter->getArgument("numero1"));
        self::assertSame("arg2", $this->parameter->getArgument("numero2"));
        self::assertSame(24, $this->parameter->getArgument("numero3"));
    }

    public function testInvalidArgumentReturnsNull()
    {
        self::assertNull($this->parameter->getArgument("totally not"));
    }

    public function testMultipleOptionParameters()
    {
        $this->parameter->setParameters([
            './test.php',
            'command-to-run',
            '--option1=value1',
            '--option2',
            '--option3=value3',
        ]);

        $this->parameter->setCommandOptions([
            new Option("option1", \Parable\Console\Parameter::OPTION_VALUE_REQUIRED),
            new Option("option2"),
            new Option("option3", \Parable\Console\Parameter::OPTION_VALUE_REQUIRED),
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

    public function testArgumentsWorkProperly()
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
            new Option("option1"),
            new Option("option2"),
        ]);
        $this->parameter->setCommandArguments([
            new Argument("brg1", \Parable\Console\Parameter::PARAMETER_REQUIRED),
            new Argument("arg2", \Parable\Console\Parameter::PARAMETER_OPTIONAL),
            new Argument("arg3", \Parable\Console\Parameter::PARAMETER_OPTIONAL),
            new Argument("arg4", \Parable\Console\Parameter::PARAMETER_OPTIONAL),
            new Argument("arg5", \Parable\Console\Parameter::PARAMETER_OPTIONAL),
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

    public function testSetCommandOptionsWithArrayThrowsException()
    {
        $this->expectException(\Parable\Console\Exception::class);
        $this->expectExceptionMessage("Options must be instances of Parameter\Option. invalid_option is not.");

        $this->parameter->setCommandOptions(["invalid_option" => []]);
    }

    public function testSetCommandArgumentsWithArrayThrowsException()
    {
        $this->expectException(\Parable\Console\Exception::class);
        $this->expectExceptionMessage("Arguments must be instances of Parameter\Argument. The item at index 0 is not.");

        $this->parameter->setCommandArguments([[]]);
    }

    public function testEnableDisableCommandNameKeepsArgumentOrderValid()
    {
        $this->parameter->setParameters([
            './test.php',
            'command-to-run',
            'argument1',
        ]);

        $this->parameter->setCommandArguments([
            new Argument("arg1", \Parable\Console\Parameter::PARAMETER_OPTIONAL),
            new Argument("arg2", \Parable\Console\Parameter::PARAMETER_OPTIONAL),
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

    public function testParameterRequiredOnlyAcceptConstantValues()
    {
        $this->expectException(\Parable\Console\Exception::class);
        $this->expectExceptionMessage("Required must be one of the PARAMETER_* constants.");

        new Argument("test", 418);
    }

    public function testParameterValueRequiredOnlyAcceptConstantValues()
    {
        $this->expectException(\Parable\Console\Exception::class);
        $this->expectExceptionMessage("Value type must be one of the OPTION_* constants.");

        new Option(
            "test",
            \Parable\Console\Parameter::PARAMETER_REQUIRED,
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
            'option' => new Option(
                "option",
                \Parable\Console\Parameter::OPTION_VALUE_OPTIONAL,
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
    public function dpGetOptionReturnsExpected()
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

    public function testSingleShortOption()
    {
        $this->parameter->setCommandOptions([
            new Option("a", \Parable\Console\Parameter::OPTION_VALUE_OPTIONAL, null, true),
            new Option("b", \Parable\Console\Parameter::OPTION_VALUE_OPTIONAL, null, true),
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

    public function testSeparateShortOptions()
    {
        $this->parameter->setCommandOptions([
            new Option("a", \Parable\Console\Parameter::OPTION_VALUE_OPTIONAL, null, true),
            new Option("b", \Parable\Console\Parameter::OPTION_VALUE_OPTIONAL, null, true),
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

    public function testCombinedShortOptions()
    {
        $this->parameter->setCommandOptions([
            new Option("a", \Parable\Console\Parameter::OPTION_VALUE_OPTIONAL, null, true),
            new Option("b", \Parable\Console\Parameter::OPTION_VALUE_OPTIONAL, null, true),
            new Option("c", \Parable\Console\Parameter::OPTION_VALUE_OPTIONAL, null, true),
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

    public function testShortOptionAndOptionValuesSetWithEqualSign()
    {
        $optionA = new Option("a", \Parable\Console\Parameter::OPTION_VALUE_OPTIONAL, null, true);
        $optionB = new Option("b", \Parable\Console\Parameter::OPTION_VALUE_OPTIONAL, null, true);
        $optionC = new Option("c", \Parable\Console\Parameter::OPTION_VALUE_OPTIONAL, null, true);

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

    public function testValueOptionsWithEqualSigns()
    {
        $this->parameter->setCommandOptions([
            new Option("aa", \Parable\Console\Parameter::OPTION_VALUE_OPTIONAL),
            new Option("bb", \Parable\Console\Parameter::OPTION_VALUE_OPTIONAL),
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

    public function testSkippingUndefinedOptions()
    {
        $this->parameter->setCommandOptions([
            new Option("a", \Parable\Console\Parameter::OPTION_VALUE_OPTIONAL, null, true),
            new Option("c", \Parable\Console\Parameter::OPTION_VALUE_OPTIONAL, null, true),
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

    public function testFlagOptionCanOnlyHaveSingleLetterName()
    {
        $this->expectException(\Parable\Console\Exception::class);
        $this->expectExceptionMessage('Flag options can only have a single-letter name.');

        new Option("test", \Parable\Console\Parameter::OPTION_VALUE_OPTIONAL, null, true);
    }

    public function testLongOptionOnlyPickedUpFromDoubleDash()
    {
        $this->parameter->setCommandOptions([
            new Option("a"),
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

    public function testFlagOptionOnlyPickedUpFromSingleDash()
    {
        $this->parameter->setCommandOptions([
            new Option("a", \Parable\Console\Parameter::OPTION_VALUE_OPTIONAL, null, true),
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
