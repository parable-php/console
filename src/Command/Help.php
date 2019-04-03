<?php declare(strict_types=1);

namespace Parable\Console\Command;

use Parable\Console\Command;

class Help extends Command
{
    /**
     * @var string
     */
    protected $name = 'help';

    /**
     * @var string
     */
    protected $description = 'Shows all commands available.';

    public function __construct()
    {
        $this->addArgument('command_name');
    }

    public function run(): void
    {
        if ($this->application->getName()) {
            $this->output->writeln($this->application->getName());
            $this->output->newline();
        }

        $commandName = $this->parameter->getArgument('command_name');

        if ($this->parameter->getCommandName() === $this->name && $commandName) {
            $this->showCommandHelp($this->parameter->getArgument('command_name'));
        } else {
            $this->showGeneralHelp();
        }
    }

    protected function showGeneralHelp(): void
    {
        $this->output->writeln("<yellow>Available commands:</yellow>");

        $longestName = 0;
        foreach ($this->application->getCommands() as $command) {
            $strlen = strlen($command->getName());
            if ($strlen > $longestName) {
                $longestName = $strlen;
            }
        }

        foreach ($this->application->getCommands() as $command) {
            $name = $command->getName();
            $this->output->write(str_pad("  <green>{$name}</green>", $longestName + 22, ' ', STR_PAD_RIGHT));
            $this->output->write("{$command->getDescription()}");
            $this->output->newline();
        }
    }

    /**
     * Show the usage and description for a specific command.
     *
     * @param string $commandName
     */
    protected function showCommandHelp($commandName): void
    {
        $command = $this->application->getCommand($commandName);

        if (!$command) {
            $this->output->writeln("<red>Unknown command:</red> {$commandName}");
            return;
        }

        if ($command->getDescription()) {
            $this->output->writeln("<yellow>Description:</yellow>");
            $this->output->writeln("  {$command->getDescription()}");
            $this->output->newline();
        }

        $this->output->writeln("<yellow>Usage:</yellow>");
        $this->output->writeln("  {$command->getUsage()}");
    }
}
