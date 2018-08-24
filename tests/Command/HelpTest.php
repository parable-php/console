<?php

namespace Parable\Console\Tests\Command;

use Parable\Console\Tests\AbstractTestClass;

class HelpTest extends AbstractTestClass
{
    /** @var \Parable\Console\App */
    protected $app;

    /** @var \Parable\Console\Parameter */
    protected $parameter;

    /** @var \Parable\Console\Command\Help */
    protected $helpCommand;

    protected function setUp()
    {
        parent::setUp();

        $this->app         = $this->container->build(\Parable\Console\App::class);
        $this->parameter   = $this->container->build(\Parable\Console\Parameter::class);

        $this->helpCommand = new \Parable\Console\Command\Help();
        $this->app->addCommand($this->helpCommand);

        $this->app->setName("Help Test App");

        $this->helpCommand->prepare(
            $this->app,
            $this->container->build(\Parable\Console\Output::class),
            $this->container->build(\Parable\Console\Input::class),
            $this->parameter
        );
    }

    public function testRunListsAvailableCommandsAndDescription()
    {
        $this->helpCommand->run();

        $content = $this->getActualOutputAndClean();

        $this->assertContains("Help Test App", $content);
        $this->assertContains("Available commands:", $content);
        $this->assertContains("help", $content);
        $this->assertContains("Shows all commands available.", $content);
    }

    public function testHelpOnSpecificCommandReturnsDescriptionAndUsage()
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

        $this->assertContains("Help Test App", $content);
        $this->assertContains("Description:", $content);
        $this->assertContains("Usage:", $content);
    }

    public function testHelpOnUnknownCommandReturnsError()
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

        $this->assertContains("Unknown command:", $content);
        $this->assertContains("what-is-this-i-cant-even", $content);
    }
}
