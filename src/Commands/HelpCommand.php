<?php declare(strict_types=1);

namespace Parable\Console\Commands;

use Parable\Console\Command;

class HelpCommand extends Command
{
    public function __construct()
    {
        $this->setName('help');
        $this->setDescription('Shows all commands available.');

        $this->addArgument('command_name');
    }

    public function run(): void
    {
        if ($this->application->getName()) {
            $this->output->writeln($this->application->getName());
            $this->output->newline();
        }

        $commandName = $this->parameter->getArgument('command_name');

        if ($commandName && $this->parameter->getCommandName() === $this->name) {
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
            $this->output->write(str_pad(
                "  <green>{$name}</green>",
                $longestName + 22,
                ' ',
                STR_PAD_RIGHT
            ));
            $this->output->writeln($command->getDescription());
        }
    }

    protected function showCommandHelp(string $commandName): void
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
        $this->output->writeln("  {$this->application->getCommandUsage($command)}");
    }
}
