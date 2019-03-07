<?php declare(strict_types=1);

namespace Parable\Console\Tests;

use Parable\Console\App;
use Parable\Console\Command;
use Parable\Console\Input;
use Parable\Console\Output;
use Parable\Console\Parameter;

class CommandTest extends AbstractTestClass
{
    /** @var \Parable\Console\Command */
    protected $command;

    protected function setUp()
    {
        parent::setUp();

        $this->command = new \Parable\Console\Command();
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
            return 'Yo!';
        };
        $this->command->setCallable($callable);

        self::assertSame($callable, $this->command->getCallable());
        self::assertSame('Yo!', $this->command->run());
    }

    public function testAddOptionAndGetOptions()
    {
        $this->command->addOption(
            'option1',
            \Parable\Console\Parameter::OPTION_VALUE_REQUIRED,
            'smart'
        );

        $options = $this->command->getOptions();

        $option1 = $options["option1"];

        self::assertInstanceOf(\Parable\Console\Parameter\Option::class, $option1);
        self::assertSame("option1", $option1->getName());
        self::assertTrue($option1->isValueRequired());
        self::assertSame("smart", $option1->getDefaultValue());
    }

    public function testAddArgumentAndGetArguments()
    {
        $this->command->addArgument('arg1', \Parable\Console\Parameter::PARAMETER_REQUIRED);
        $this->command->addArgument('arg2', \Parable\Console\Parameter::PARAMETER_OPTIONAL, 12);

        $arguments = $this->command->getArguments();

        $argument1 = $arguments[0];
        $argument2 = $arguments[1];

        self::assertInstanceOf(\Parable\Console\Parameter\Argument::class, $argument1);
        self::assertSame("arg1", $argument1->getName());
        self::assertTrue($argument1->isRequired());
        self::assertSame(null, $argument1->getDefaultValue());

        self::assertInstanceOf(\Parable\Console\Parameter\Argument::class, $argument2);
        self::assertSame("arg2", $argument2->getName());
        self::assertFalse($argument2->isRequired());
        self::assertSame(12, $argument2->getDefaultValue());
    }

    public function testPrepareAcceptsAndPassesInstancesToCallbackProperly()
    {
        $this->command->prepare(
            $this->container->build(\Parable\Console\App::class),
            $this->container->build(\Parable\Console\Output::class),
            $this->container->build(\Parable\Console\Input::class),
            $this->container->build(\Parable\Console\Parameter::class)
        );
        $this->command->setCallable(function ($app, $output, $input, $parameter) {
            return [$app, $output, $input, $parameter];
        });

        $instances = $this->command->run();

        self::assertInstanceOf(\Parable\Console\App::class, $instances[0]);
        self::assertInstanceOf(\Parable\Console\Output::class, $instances[1]);
        self::assertInstanceOf(\Parable\Console\Input::class, $instances[2]);
        self::assertInstanceOf(\Parable\Console\Parameter::class, $instances[3]);
    }

    public function testExtendingCommandClassWorks()
    {
        $command = new class extends Command {
            protected $name = 'testcommand';
            protected $description = 'This is a test command.';
            public function run()
            {
                return 'OK';
            }
        };

        self::assertSame('testcommand', $command->getName());
        self::assertSame('This is a test command.', $command->getDescription());
        self::assertNull($command->getCallable());
        self::assertSame('OK', $command->run());
    }

    public function testCommandCanCallOtherCommand()
    {
        $command = new class extends Command {
            protected $name = 'calling-command';
            protected $description = 'This is a test command.';
            public function run()
            {
                $command2 = new class extends Command {
                    protected $name = 'testcommand';
                    protected $description = 'This is a test command.';
                    public function run()
                    {
                        return 'OK';
                    }
                };

                return 'Command returned: ' . $this->runCommand($command2);
            }
        };

        $command->prepare(
            $this->container->get(App::class),
            $this->container->get(Output::class),
            $this->container->get(Input::class),
            $this->container->get(Parameter::class)
        );

        self::assertSame('calling-command', $command->getName());
        self::assertSame('Command returned: OK', $command->run());
    }

    public function testCommandRunWithoutCallableReturnsFalse()
    {
        $command = $this->createNewCommand();
        self::assertFalse($command->run());
    }

    public function testGetUsageWithNothingSetIsEmptyString()
    {
        $command = $this->createNewCommand();
        self::assertEmpty($command->getUsage());
    }

    public function testGetUsageWithEveryCombination()
    {
        $command = $this->createNewCommand("test-command");
        $command->addOption("opt1", \Parable\Console\Parameter::OPTION_VALUE_OPTIONAL);
        $command->addOption("opt2", \Parable\Console\Parameter::OPTION_VALUE_REQUIRED);
        $command->addArgument("arg1", \Parable\Console\Parameter::PARAMETER_REQUIRED);
        $command->addArgument("arg2", \Parable\Console\Parameter::PARAMETER_OPTIONAL);

        self::assertSame(
            "test-command arg1 [arg2] [--opt1[=value]] [--opt2=value]",
            $command->getUsage()
        );
    }

    protected function createNewCommand($name = null)
    {
        $command = new \Parable\Console\Command();
        $command->prepare(
            $this->container->build(\Parable\Console\App::class),
            $this->container->build(\Parable\Console\Output::class),
            $this->container->build(\Parable\Console\Input::class),
            $this->container->build(\Parable\Console\Parameter::class)
        );
        if ($name) {
            $command->setName($name);
        }
        return $command;
    }
}
