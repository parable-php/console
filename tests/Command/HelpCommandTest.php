<?php declare(strict_types=1);

namespace Parable\Console\Tests\Command;

use Parable\Console\Application;
use Parable\Console\Input;
use Parable\Console\Output;
use Parable\Console\Parameter;
use Parable\Console\Commands;
use Parable\Console\Tests\AbstractTestClass;

class HelpCommandTest extends AbstractTestClass
{
    /** @var Application */
    protected $application;

    /** @var Parameter */
    protected $parameter;

    /** @var Commands\HelpCommand */
    protected $helpCommand;

    protected function setUp(): void
    {
        parent::setUp();

        $this->application = $this->container->build(Application::class);
        $this->parameter = $this->container->build(Parameter::class);

        $this->helpCommand = new Commands\HelpCommand();
        $this->application->addCommand($this->helpCommand);

        $this->application->setName("Help Test App");

        $this->helpCommand->prepare(
            $this->application,
            $this->container->build(Output::class),
            $this->container->build(Input::class),
            $this->parameter
        );
    }

    public function testRunListsAvailableCommandsAndDescription(): void
    {
        $this->helpCommand->run();

        $content = $this->getActualOutputAndClean();

        self::assertContains("Help Test App", $content);
        self::assertContains("Available commands:", $content);
        self::assertContains("help", $content);
        self::assertContains("Shows all commands available.", $content);
    }

    public function testHelpOnSpecificCommandReturnsDescriptionAndUsage(): void
    {
        $this->parameter->setCommandArguments($this->helpCommand->getArguments());
        $this->parameter->setParameters([
            './test.php',
            'help',
            'help',
        ]);
        $this->parameter->checkCommandArguments();

        $this->helpCommand->run();

        $content = $this->getActualOutputAndClean();

        self::assertContains("Help Test App", $content);
        self::assertContains("Description:", $content);
        self::assertContains("Usage:", $content);
    }

    public function testHelpOnUnknownCommandReturnsError(): void
    {
        $this->parameter->setCommandArguments($this->helpCommand->getArguments());
        $this->parameter->setParameters([
            './test.php',
            'help',
            'what-is-this-i-cant-even',
        ]);
        $this->parameter->checkCommandArguments();

        $this->helpCommand->run();

        $content = $this->getActualOutputAndClean();

        self::assertContains("Unknown command:", $content);
        self::assertContains("what-is-this-i-cant-even", $content);
    }
}
