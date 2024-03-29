<?php declare(strict_types=1);

namespace Parable\Console;

use Parable\Di\Container;
use Throwable;

class Application
{
    protected ?string $name = null;
    /** @var Command[] */
    protected array $commands = [];
    /** @var string[] */
    protected array $commandNames = [];
    protected ?Command $activeCommand = null;
    protected ?string $defaultCommand = null;
    protected bool $onlyUseDefaultCommand = false;

    public function __construct(
        protected Output $output,
        protected Input $input,
        protected Parameter $parameter,
        protected Container $container
    ) {
        set_exception_handler(function (Throwable $e): void {
            $this->output->writeErrorBlock([$e->getMessage()]);

            if ($this->activeCommand) {
                $this->output->writeln(sprintf(
                    '<yellow>Usage</yellow>: %s',
                    $this->getCommandUsage($this->activeCommand)
                ));
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
        $this->prepareCommand($command);
        $this->commands[$command->getName()] = $command;
    }

    public function addCommandByNameAndClass(string $commandName, string $className): void
    {
        $this->commandNames[$commandName] = $className;
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
        return isset($this->commands[$commandName])
            || isset($this->commandNames[$commandName]);
    }

    public function getCommand(string $commandName): ?Command
    {
        if (!$this->hasCommand($commandName)) {
            return null;
        }

        if (isset($this->commandNames[$commandName]) && !isset($this->commands[$commandName])) {
            /** @var Command $command */
            $command = $this->container->get($this->commandNames[$commandName]);
            $this->addCommand($command);

            // Since we've instantiated now, we can lose the reference to the name alone
            unset($this->commandNames[$commandName]);
        }

        return $this->commands[$commandName];
    }

    /**
     * @return Command[]
     */
    public function getCommands(): array
    {
        $commands = [];

        foreach ($this->commandNames as $commandName => $className) {
            $commands[$commandName] = $this->getCommand($commandName);
        }

        return array_filter(array_merge($this->commands, $commands));
    }

    public function removeCommandByName(string $commandName): void
    {
        if ($this->hasCommand($commandName)) {
            unset(
                $this->commands[$commandName],
                $this->commandNames[$commandName]
            );
        }
    }

    public function getCommandUsage(Command $command): string
    {
        $string = [];

        $string[] = $command->getName();

        foreach ($command->getArguments() as $argument) {
            if ($argument->isRequired()) {
                $string[] = $argument->getName();
            } else {
                $string[] = "[{$argument->getName()}]";
            }
        }

        foreach ($command->getOptions() as $option) {
            $dashes = '-';
            if (!$option->isFlagOption()) {
                $dashes .= '-';
            }
            if ($option->isValueRequired()) {
                $optionString = "{$option->getName()}=value";
            } else {
                $optionString = "{$option->getName()}[=value]";
            }
            $string[] = "[{$dashes}{$optionString}]";
        }

        return implode(' ', $string);
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
            throw ConsoleException::fromMessage('No valid commands found.');
        }

        if (!$command->isPrepared()) {
            $this->prepareCommand($command);
        }

        $this->activeCommand = $command;

        $this->parameter->setCommandArguments($command->getArguments());
        $this->parameter->checkCommandArguments();

        $this->parameter->setCommandOptions($command->getOptions());
        $this->parameter->checkCommandOptions();

        $command->run();
    }

    protected function prepareCommand(Command $command): void
    {
        $command->prepare($this, $this->output, $this->input, $this->parameter);
    }
}
