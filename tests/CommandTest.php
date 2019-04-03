<?php declare(strict_types=1);

namespace Parable\Console\Tests;

use Parable\Console\Application;
use Parable\Console\Command;
use Parable\Console\Input;
use Parable\Console\Output;
use Parable\Console\Parameter;
use Parable\Console\Tests\Classes\ValueClass;

class CommandTest extends AbstractTestClass
{
    /**
     * @var Command
     */
    protected $command;

    protected $value;

    protected function setUp()
    {
        parent::setUp();

        $this->command = new Command();

        ValueClass::clear();
    }

    public function testSetGetName()
    {
        $this->command->setName('name');
        self::assertSame('name', $this->command->getName());
    }

    public function testSetGetDescription()
    {
        $this->command->setDescription('description');
        self::assertSame('description', $this->command->getDescription());
    }

    public function testSetGetCallableAndRunCommand()
    {
        $callable = function () {
            ValueClass::set('Yo!');
        };
        $this->command->setCallable($callable);

        $this->command->run();

        self::assertSame($callable, $this->command->getCallable());
        self::assertSame('Yo!', ValueClass::get());
    }

    public function testAddOptionAndGetOptions()
    {
        $this->command->addOption(
            'option1',
            Parameter::OPTION_VALUE_REQUIRED,
            'smart'
        );

        $options = $this->command->getOptions();

        $option1 = $options["option1"];

        self::assertInstanceOf(Parameter\Option::class, $option1);
        self::assertSame("option1", $option1->getName());
        self::assertTrue($option1->isValueRequired());
        self::assertSame("smart", $option1->getDefaultValue());
    }

    public function testAddArgumentAndGetArguments()
    {
        $this->command->addArgument('arg1', Parameter::PARAMETER_REQUIRED);
        $this->command->addArgument('arg2', Parameter::PARAMETER_OPTIONAL, 12);

        $arguments = $this->command->getArguments();

        $argument1 = $arguments[0];
        $argument2 = $arguments[1];

        self::assertInstanceOf(Parameter\Argument::class, $argument1);
        self::assertSame("arg1", $argument1->getName());
        self::assertTrue($argument1->isRequired());
        self::assertSame(null, $argument1->getDefaultValue());

        self::assertInstanceOf(Parameter\Argument::class, $argument2);
        self::assertSame("arg2", $argument2->getName());
        self::assertFalse($argument2->isRequired());
        self::assertSame(12, $argument2->getDefaultValue());
    }

    public function testPrepareAcceptsAndPassesInstancesToCallbackProperly()
    {
        $this->command->prepare(
            $this->container->build(Application::class),
            $this->container->build(Output::class),
            $this->container->build(Input::class),
            $this->container->build(Parameter::class)
        );
        $this->command->setCallable(function ($application, $output, $input, $parameter) {
            ValueClass::set([$application, $output, $input, $parameter]);
        });

        $this->command->run();

        $instances = ValueClass::get();

        self::assertInstanceOf(Application::class, $instances[0]);
        self::assertInstanceOf(Output::class, $instances[1]);
        self::assertInstanceOf(Input::class, $instances[2]);
        self::assertInstanceOf(Parameter::class, $instances[3]);
    }

    public function testExtendingCommandClassWorks()
    {
        $command = new class extends Command {
            protected $name = 'testcommand';
            protected $description = 'This is a test command.';
            public function run(): void
            {
                ValueClass::set('OK');
            }
        };

        self::assertSame('testcommand', $command->getName());
        self::assertSame('This is a test command.', $command->getDescription());
        self::assertNull($command->getCallable());

        $command->run();

        self::assertSame('OK', ValueClass::get());
    }

    public function testCommandCanCallOtherCommand()
    {
        $command = new class extends Command {
            protected $name = 'calling-command';
            protected $description = 'This is a test command.';
            public function run(): void
            {
                $command2 = new class extends Command {
                    protected $name = 'testcommand';
                    protected $description = 'This is a test command.';
                    public function run(): void
                    {
                        ValueClass::set('OK');
                    }
                };

                $this->runCommand($command2);

                ValueClass::set('Command returned: ' . ValueClass::get());
            }
        };

        $command->prepare(
            $this->container->get(Application::class),
            $this->container->get(Output::class),
            $this->container->get(Input::class),
            $this->container->get(Parameter::class)
        );

        $command->run();

        self::assertSame('calling-command', $command->getName());
        self::assertSame('Command returned: OK', ValueClass::get());
    }

    public function testGetUsageWithNothingSetIsEmptyString()
    {
        $command = $this->createNewCommand();
        self::assertEmpty($command->getUsage());
    }

    public function testGetUsageWithEveryCombination()
    {
        $command = $this->createNewCommand("test-command");
        $command->addOption("opt1", Parameter::OPTION_VALUE_OPTIONAL);
        $command->addOption("opt2", Parameter::OPTION_VALUE_REQUIRED);
        $command->addArgument("arg1", Parameter::PARAMETER_REQUIRED);
        $command->addArgument("arg2", Parameter::PARAMETER_OPTIONAL);

        self::assertSame(
            "test-command arg1 [arg2] [--opt1[=value]] [--opt2=value]",
            $command->getUsage()
        );
    }

    protected function createNewCommand(string $name = null): Command
    {
        $command = new Command();
        $command->prepare(
            $this->container->build(Application::class),
            $this->container->build(Output::class),
            $this->container->build(Input::class),
            $this->container->build(Parameter::class)
        );

        if ($name !== null) {
            $command->setName($name);
        }

        return $command;
    }
}
