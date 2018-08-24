<?php

namespace Parable\Console;

class App
{
    /** @var Output */
    protected $output;

    /** @var Input */
    protected $input;

    /** @var Parameter */
    protected $parameter;

    /** @var string|null */
    protected $name;

    /** @var Command[] */
    protected $commands = [];

    /** @var Command|null */
    protected $activeCommand;

    /** @var string|null */
    protected $defaultCommand;

    /** @var bool */
    protected $onlyUseDefaultCommand = false;

    public function __construct(
        Output $output,
        Input $input,
        Parameter $parameter
    ) {
        $this->output    = $output;
        $this->input     = $input;
        $this->parameter = $parameter;

        set_exception_handler(function ($e) {
            // @codeCoverageIgnoreStart

            /** @var \Exception $e */
            $this->output->writeErrorBlock([$e->getMessage()]);

            if ($this->activeCommand) {
                $this->output->writeln('<yellow>Usage</yellow>: ' . $this->activeCommand->getUsage());
            }
            // @codeCoverageIgnoreEnd
        });
    }

    /**
     * Set the application name.
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * Return the application name.
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * Add a command to the application.
     */
    public function addCommand(Command $command): void
    {
        $command->prepare($this, $this->output, $this->input, $this->parameter);
        $this->commands[$command->getName()] = $command;
    }

    /**
     * Add an array of commands to the application.
     *
     * @param Command[] $commands
     */
    public function addCommands(array $commands): void
    {
        foreach ($commands as $command) {
            $this->addCommand($command);
        }
    }

    /**
     * Set the default command to use if no command is given (by name).
     */
    public function setDefaultCommandByName(string $commandName): void
    {
        $this->defaultCommand = $commandName;
    }

    /**
     * Set the default command to use if no command is given. Also
     * adds the command.
     */
    public function setDefaultCommand(Command $command): void
    {
        $this->addCommand($command);
        $this->setDefaultCommandByName($command->getName());
    }

    /**
     * Set whether, if a default command is set up, we should consider it the only command.
     */
    public function setOnlyUseDefaultCommand(bool $onlyUseDefaultCommand): void
    {
        $this->onlyUseDefaultCommand = $onlyUseDefaultCommand;
    }

    /**
     * Return whether, if a default command is set up, we should consider it the only command.
     */
    public function shouldOnlyUseDefaultCommand(): bool
    {
        return $this->onlyUseDefaultCommand;
    }

    /**
     * Returns whether the $commandName is registered.
     */
    public function hasCommand(string $commandName): bool
    {
        return isset($this->commands[$commandName]);
    }

    /**
     * Return the command by name if it's set on the application.
     */
    public function getCommand(string $commandName): ?Command
    {
        if ($this->hasCommand($commandName)) {
            return $this->commands[$commandName];
        }

        return null;
    }

    /**
     * Return all commands set on the application.
     *
     * @return Command[]
     */
    public function getCommands(): array
    {
        return $this->commands;
    }

    /**
     * Remove a command by name.
     */
    public function removeCommandByName(string $commandName): void
    {
        if ($this->hasCommand($commandName)) {
            unset($this->commands[$commandName]);
        }
    }

    /**
     * Run the application.
     */
    public function run()
    {
        $defaultCommand = null;
        $command        = null;

        if ($this->defaultCommand) {
            $defaultCommand = $this->getCommand($this->defaultCommand);
        }
        if (!$this->shouldOnlyUseDefaultCommand()) {
            $commandName = $this->parameter->getCommandName();
            if ($commandName) {
                $command = $this->getCommand($commandName);
            }
            $this->parameter->enableCommandName();
        } else {
            $this->parameter->disableCommandName();
        }

        // Use $command or $defaultCommand, since they're mutually exclusive
        $command = $command ?: $defaultCommand;

        if (!$command) {
            throw Exception::fromMessage('No valid commands found.');
        }

        $this->activeCommand = $command;

        $this->parameter->setCommandArguments($command->getArguments());
        $this->parameter->checkCommandArguments();

        $this->parameter->setCommandOptions($command->getOptions());
        $this->parameter->checkCommandOptions();

        return $command->run();
    }
}
