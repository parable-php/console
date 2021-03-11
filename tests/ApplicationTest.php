<?php declare(strict_types=1);

namespace Parable\Console\Tests;

use Parable\Console\Application;
use Parable\Console\Command;
use Parable\Console\ConsoleException;
use Parable\Console\Input;
use Parable\Console\Output;
use Parable\Console\Parameter;
use Parable\Console\Tests\Classes\TestCommand;
use Parable\Console\Tests\Classes\ValueClass;

class ApplicationTest extends AbstractTestClass
{
    protected Parameter $parameter;
    protected Application $application;
    protected Command $command1;
    protected Command $command2;
    protected Command $commandReturnOptionValue;

    protected function setUp(): void
    {
        parent::setUp();

        $this->parameter = new Parameter();
        $this->container->store($this->parameter);

        $this->application = $this->container->get(Application::class);

        $this->command1 = new Command();
        $this->command1->setName('test1');
        $this->command1->addArgument("arg1");
        $this->command1->setCallable(static function () {
            ValueClass::set('OK1');
        });
        $this->application->addCommand($this->command1);

        $this->command2 = new Command();
        $this->command2->setName('test2');
        $this->command1->addArgument("arg1");
        $this->command2->setCallable(static function () {
            ValueClass::set('OK2');
        });
        $this->application->addCommand($this->command2);

        $this->application->setDefaultCommand($this->command1);

        $this->commandReturnOptionValue = new Command();
        $this->commandReturnOptionValue->setName('returnOptionValue');
        $this->commandReturnOptionValue->setCallable(static function (
            Application $application,
            Output $output,
            Input $input,
            Parameter $parameter
        ) {
            ValueClass::set($parameter->getOption('option'));
        });

        $this->application->addCommand($this->commandReturnOptionValue);

        ValueClass::clear();
    }

    public function testAddCommands(): void
    {
        $application = $this->container->buildAll(Application::class);
        self::assertCount(0, $application->getCommands());

        $application->addCommands([
            $this->command1,
            $this->command2,
        ]);

        self::assertCount(2, $application->getCommands());
    }

    public function testSetGetName(): void
    {
        $this->application->setName('Super-application');
        self::assertSame('Super-application', $this->application->getName());
    }

    public function testGetCommand(): void
    {
        $commandGot = $this->application->getCommand('test1');

        $commandGot->run();

        self::assertSame('test1', $commandGot->getName());
        self::assertSame('OK1', ValueClass::get());

        $commandGot = $this->application->getCommand('test2');

        $commandGot->run();

        self::assertSame('test2', $commandGot->getName());
        self::assertSame('OK2', ValueClass::get());
    }

    public function testGetCommandsGetsBothInstantiatedAndCommandNames(): void
    {
        $application = $this->container->buildAll(Application::class);

        $application->addCommand($this->command1);
        $application->addCommandByNameAndClass('test-command', TestCommand::class);

        self::assertCount(2, $application->getCommands());

        foreach ($application->getCommands() as $commandName => $command) {
            self::assertInstanceOf(Command::class, $command);
        }
    }

    public function testAddCommand(): void
    {
        self::assertFalse($this->container->has(TestCommand::class));

        $testCommandBeforePrepare = $this->container->get(TestCommand::class);

        self::assertFalse($testCommandBeforePrepare->isPrepared());

        $this->application->addCommand($testCommandBeforePrepare);

        /** @var TestCommand $testCommand */
        $testCommand = $this->application->getCommand('test-command');

        self::assertTrue($testCommand->isPrepared());

        self::assertSame('test-command', $testCommand->getName());
    }

    public function testAddCommandByClassName(): void
    {
        self::assertFalse($this->container->has(TestCommand::class));

        $this->application->addCommandByNameAndClass('test-command', TestCommand::class);

        // Since we added it by name and class, it's not yet instanced or cached
        self::assertFalse($this->container->has(TestCommand::class));

        /** @var TestCommand $testCommand */
        $testCommand = $this->application->getCommand('test-command');

        self::assertTrue($testCommand->isPrepared());

        self::assertSame('test-command', $testCommand->getName());

        // Now that we've requested it, it is instanced & cached
        self::assertTrue($this->container->has(TestCommand::class));
    }

    public function testHasCommand(): void
    {
        self::assertTrue($this->application->hasCommand('test1'));
        self::assertFalse($this->application->hasCommand('nope not this one'));
    }

    public function testGetCommandsReturnsAll(): void
    {
        $commands = $this->application->getCommands();

        $commands['test1']->run();

        self::assertSame('test1', $commands['test1']->getName());
        self::assertSame('OK1', ValueClass::get());

        $commands['test2']->run();

        self::assertSame('test2', $commands['test2']->getName());
        self::assertSame('OK2', ValueClass::get());
    }

    public function testGetCommandsWithoutCommandsReturnsEmptyArray(): void
    {
        $application = $this->container->build(Application::class);
        self::assertSame([], $application->getCommands());
    }

    public function testGetNonExistingCommandReturnsNull(): void
    {
        $application = $this->container->build(Application::class);
        self::assertNull($application->getCommand('nope'));
    }

    public function testSetDefaultCommandRunsDefaultCommand(): void
    {
        $this->application->setDefaultCommand($this->command1);

        $this->application->run();

        self::assertSame('OK1', ValueClass::get());
    }

    public function testSetDefaultCommandByNameRunsDefaultCommand(): void
    {
        self::assertNull(ValueClass::get());

        $this->application->setDefaultCommandByName("test1");

        $this->application->run();

        self::assertSame('OK1', ValueClass::get());

        $this->application->setDefaultCommandByName("test2");

        $this->application->run();

        self::assertSame('OK2', ValueClass::get());
    }

    public function testPassCommandOnCommandLineRunsAppropriateCommand(): void
    {
        self::assertNull(ValueClass::get());

        $application = new Application(
            $this->container->build(Output::class),
            $this->container->build(Input::class),
            $this->parameter,
            $this->container
        );

        $application->addCommand($this->command1);
        $application->addCommand($this->command2);

        // Same as calling 'php test.php test2'
        $this->parameter->setParameters(['./test.php', 'test2']);

        $application->run();

        self::assertSame("OK2", ValueClass::get());

        // Same as calling 'php test.php test2'
        $this->parameter->setParameters(['./test.php', 'test1']);

        $application->run();

        self::assertSame("OK1", ValueClass::get());
    }

    public function testRemoveCommandByName(): void
    {
        $application = new Application(
            $this->container->build(Output::class),
            $this->container->build(Input::class),
            $this->parameter,
            $this->container
        );

        $application->addCommand($this->command1);
        $application->addCommand($this->command2);

        self::assertCount(2, $application->getCommands());

        $application->removeCommandByName($this->command1->getName());

        self::assertCount(1, $application->getCommands());

        self::assertSame($this->command2, $application->getCommand($this->command2->getName()));
    }

    /**
     * @dataProvider dpTrueFalse
     */
    public function testSetDefaultCommandWithCommandPassedRespectsDefaultOnlyCommand(
        bool $defaultCommandOnly
    ) {
        // Same as calling 'php test.php test2'
        $_SERVER["argv"] = ['./test.php', 'test2'];

        $application = $this->container->buildAll(Application::class);
        $application->addCommand($this->command1);
        $application->addCommand($this->command2);

        $application->setOnlyUseDefaultCommand($defaultCommandOnly);
        $application->setDefaultCommand($this->command1);

        $application->run();

        // If defaultCommandOnly, OK1/test1 should run, otherwise OK2/test2
        self::assertSame($defaultCommandOnly ? 'OK1' : 'OK2', ValueClass::get());

        // If default command only, the "command name" should be shifted to the arguments list instead
        $arguments = $this->command1->getArguments();
        if ($defaultCommandOnly) {
            self::assertSame("test2", $arguments[0]->getValue());
        } else {
            self::assertNull($arguments[0]->getValue());
        }
    }

    public function testOptionalOptionWithRequiredValueThrowsExceptionIfNoValue(): void
    {
        // First test the regular app instance, showing it does not care if the option isn't there
        $this->command1->addOption(
            'option',
            Parameter::OPTION_VALUE_REQUIRED
        );

        $this->application->run();

        self::assertSame('OK1', ValueClass::get());

        // And now build a new app with the option passed without a value
        $_SERVER["argv"] = ['./test.php', '--option'];
        $application = $this->container->buildAll(Application::class);
        $application->addCommand($this->command1);

        $application->setDefaultCommand($this->command1);

        $this->expectException(ConsoleException::class);
        $this->expectExceptionMessage("Option '--option' requires a value, which is not provided.");

        $application->run();
    }

    public function testOptionWithValuePassedWorksProperly(): void
    {
        $_SERVER["argv"] = ['./test.php', '--option=passed value here!'];
        $application = $this->container->buildAll(Application::class);
        $this->commandReturnOptionValue->addOption(
            'option',
            Parameter::OPTION_VALUE_OPTIONAL,
            'default value is here!'
        );
        $application->addCommand($this->commandReturnOptionValue);

        $application->setDefaultCommand($this->commandReturnOptionValue);

        $application->run();

        self::assertSame('passed value here!', ValueClass::get());
    }

    public function testOptionWithDefaultValueWorksProperly(): void
    {
        $_SERVER["argv"] = ['./test.php', '--option'];
        $application = $this->container->buildAll(Application::class);
        $this->commandReturnOptionValue->addOption(
            'option',
            Parameter::OPTION_VALUE_OPTIONAL,
            'default value is here!'
        );
        $application->addCommand($this->commandReturnOptionValue);

        $application->setDefaultCommand($this->commandReturnOptionValue);

        $application->run();

        self::assertSame('default value is here!', ValueClass::get());
    }

    public function testThrowsExceptionWhenRanWithoutCommand(): void
    {
        $this->expectExceptionMessage("No valid commands found.");
        $this->expectException(ConsoleException::class);

        $application = $this->container->buildAll(Application::class);
        $application->run();
    }

    public function testGetUsageWithNothingSetIsEmptyString(): void
    {
        $command = $this->createNewCommand();
        self::assertEmpty($this->application->getCommandUsage($command));
    }

    public function testGetUsageWithEveryCombination(): void
    {
        $command = $this->createNewCommand("test-command");
        $command->addOption("opt1", Parameter::OPTION_VALUE_OPTIONAL);
        $command->addOption("opt2", Parameter::OPTION_VALUE_REQUIRED);
        $command->addArgument("arg1", Parameter::PARAMETER_REQUIRED);
        $command->addArgument("arg2", Parameter::PARAMETER_OPTIONAL);

        self::assertSame(
            "test-command arg1 [arg2] [--opt1[=value]] [--opt2=value]",
            $this->application->getCommandUsage($command)
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
