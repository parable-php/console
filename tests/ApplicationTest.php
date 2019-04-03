<?php declare(strict_types=1);

namespace Parable\Console\Tests;

use Parable\Console\Application;
use Parable\Console\Command;
use Parable\Console\Exception;
use Parable\Console\Input;
use Parable\Console\Output;
use Parable\Console\Parameter;
use Parable\Console\Tests\Classes\ValueClass;

class ApplicationTest extends AbstractTestClass
{
    /**
     * @var Parameter
     */
    protected $parameter;

    /**
     * @var Application
     */
    protected $application;

    /**
     * @var Command
     */
    protected $command1;

    /**
     * @var Command
     */
    protected $command2;

    /**
     * @var Command
     */
    protected $commandReturnOptionValue;

    protected function setUp()
    {
        parent::setUp();

        $this->parameter = new Parameter();
        $this->container->store($this->parameter);

        $this->application = $this->container->get(Application::class);

        $this->command1 = new Command();
        $this->command1->setName('test1');
        $this->command1->addArgument("arg1");
        $this->command1->setCallable(function () {
            ValueClass::set('OK1');
        });
        $this->application->addCommand($this->command1);

        $this->command2 = new Command();
        $this->command2->setName('test2');
        $this->command1->addArgument("arg1");
        $this->command2->setCallable(function () {
            ValueClass::set('OK2');
        });
        $this->application->addCommand($this->command2);

        $this->application->setDefaultCommand($this->command1);

        $this->commandReturnOptionValue = new Command();
        $this->commandReturnOptionValue->setName('returnOptionValue');
        $this->commandReturnOptionValue->setCallable(function (
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

    public function testAddCommands()
    {
        $application = $this->container->buildAll(Application::class);
        self::assertCount(0, $application->getCommands());

        $application->addCommands([
            $this->command1,
            $this->command2,
        ]);

        self::assertCount(2, $application->getCommands());
    }

    public function testAppSetGetName()
    {
        $this->application->setName('Super-application');
        self::assertSame('Super-application', $this->application->getName());
    }

    public function testAppAddGetCommand()
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

    public function testHasCommand()
    {
        self::assertTrue($this->application->hasCommand('test1'));
        self::assertFalse($this->application->hasCommand('nope not this one'));
    }

    public function testAppGetCommandsReturnsAll()
    {
        $commands = $this->application->getCommands();

        $commands['test1']->run();

        self::assertSame('test1', $commands['test1']->getName());
        self::assertSame('OK1', ValueClass::get());

        $commands['test2']->run();

        self::assertSame('test2', $commands['test2']->getName());
        self::assertSame('OK2', ValueClass::get());
    }

    public function testAppGetCommandsWithoutCommandsReturnsEmptyArray()
    {
        $application = $this->container->build(Application::class);
        self::assertSame([], $application->getCommands());
    }

    public function testAppGetNonExistingCommandReturnsNull()
    {
        $application = $this->container->build(Application::class);
        self::assertNull($application->getCommand('nope'));
    }

    public function testSetDefaultCommandRunsDefaultCommand()
    {
        $this->application->setDefaultCommand($this->command1);

        $this->application->run();

        self::assertSame('OK1', ValueClass::get());
    }

    public function testSetDefaultCommandByNameRunsDefaultCommand()
    {
        self::assertNull(ValueClass::get());

        $this->application->setDefaultCommandByName("test1");

        $this->application->run();

        self::assertSame('OK1', ValueClass::get());

        $this->application->setDefaultCommandByName("test2");

        $this->application->run();

        self::assertSame('OK2', ValueClass::get());
    }

    public function testPassCommandOnCommandLineRunsAppropriateCommand()
    {
        self::assertNull(ValueClass::get());

        $application = new Application(
            $this->container->build(Output::class),
            $this->container->build(Input::class),
            $this->parameter
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

    public function testRemoveCommandByName()
    {
        $application = new Application(
            $this->container->build(Output::class),
            $this->container->build(Input::class),
            $this->parameter
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
     *
     * @param $defaultCommandOnly
     */
    public function testSetDefaultCommandWithCommandPassedRespectsDefaultOnlyCommand(bool $defaultCommandOnly)
    {
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

    public function testOptionalOptionWithRequiredValueThrowsExceptionIfNoValue()
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

        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Option '--option' requires a value, which is not provided.");

        $application->run();
    }

    public function testOptionWithValuePassedWorksProperly()
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

    public function testOptionWithDefaultValueWorksProperly()
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

    public function testThrowsExceptionWhenRanWithoutCommand()
    {
        $this->expectExceptionMessage("No valid commands found.");
        $this->expectException(Exception::class);

        $application = $this->container->buildAll(Application::class);
        $application->run();
    }
}
