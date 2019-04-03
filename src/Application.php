<?php declare(strict_types=1);

namespace Parable\Console;

class Application
{
    /**
     * @var Output
     */
    protected $output;

    /**
     * @var Input
     */
    protected $input;

    /**
     * @var Parameter
     */
    protected $parameter;

    /**
     * @var string|null
     */
    protected $name;

    /**
     * @var Command[]
     */
    protected $commands = [];

    /**
     * @var Command|null
     */
    protected $activeCommand;

    /**
     * @var string|null
     */
    protected $defaultCommand;

    /**
     * @var bool
     */
    protected $onlyUseDefaultCommand = false;

    public function __construct(
        Output $output,
        Input $input,
        Parameter $parameter
    ) {
        $this->output    = $output;
        $this->input     = $input;
        $this->parameter = $parameter;

        set_exception_handler(function (Exception $e) {
            $this->output->writeErrorBlock([$e->getMessage()]);

            if ($this->activeCommand) {
                $this->output->writeln('<yellow>Usage</yellow>: ' . $this->activeCommand->getUsage());
            }
        });
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function addCommand(Command $command): void
    {
        $command->prepare($this, $this->output, $this->input, $this->parameter);
        $this->commands[$command->getName()] = $command;
    }

    /**
     * @param Command[] $commands
     */
    public function addCommands(array $commands): void
    {
        foreach ($commands as $command) {
            $this->addCommand($command);
        }
    }

    public function setDefaultCommandByName(string $commandName): void
    {
        $this->defaultCommand = $commandName;
    }

    public function setDefaultCommand(Command $command): void
    {
        $this->addCommand($command);
        $this->setDefaultCommandByName($command->getName());
    }

    public function setOnlyUseDefaultCommand(bool $onlyUseDefaultCommand): void
    {
        $this->onlyUseDefaultCommand = $onlyUseDefaultCommand;
    }

    public function shouldOnlyUseDefaultCommand(): bool
    {
        return $this->onlyUseDefaultCommand;
    }

    public function hasCommand(string $commandName): bool
    {
        return isset($this->commands[$commandName]);
    }

    public function getCommand(string $commandName): ?Command
    {
        if ($this->hasCommand($commandName)) {
            return $this->commands[$commandName];
        }

        return null;
    }

    /**
     * @return Command[]
     */
    public function getCommands(): array
    {
        return $this->commands;
    }

    public function removeCommandByName(string $commandName): void
    {
        if ($this->hasCommand($commandName)) {
            unset($this->commands[$commandName]);
        }
    }

    public function run(): void
    {
        $defaultCommand = null;
        $command = null;

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

        $command->run();
    }
}
