<?php

namespace Parable\Console;

use Parable\Console\Parameter\Argument;
use Parable\Console\Parameter\Option;

class Command
{
    /** @var string|null */
    protected $name;

    /** @var string|null */
    protected $description;

    /** @var callable|null */
    protected $callable;

    /** @var Option[] */
    protected $options = [];

    /** @var Argument[] */
    protected $arguments = [];

    /** @var App|null */
    protected $app;

    /** @var Output|null */
    protected $output;

    /** @var Input|null */
    protected $input;

    /** @var Parameter|null */
    protected $parameter;

    /**
     * Prepare the command, setting all classes the command is dependant on.
     */
    public function prepare(
        App $app,
        Output $output,
        Input $input,
        Parameter $parameter
    ) {
        $this->app       = $app;
        $this->output    = $output;
        $this->input     = $input;
        $this->parameter = $parameter;
    }

    /**
     * Set the command name.
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * Return the command name.
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * Set the command description.
     */
    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    /**
     * Return the command description.
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * Set the callable to be run when the command is run.
     */
    public function setCallable(callable $callable): void
    {
        $this->callable = $callable;
    }

    /**
     * Return the callable.
     */
    public function getCallable(): ?callable
    {
        return $this->callable;
    }

    /**
     * Add an option for this command.
     */
    public function addOption(
        string $name,
        int $valueRequired = Parameter::OPTION_VALUE_OPTIONAL,
        $defaultValue = null,
        bool $flagOption = false
    ): void {
        $this->options[$name] = new Option(
            $name,
            $valueRequired,
            $defaultValue,
            $flagOption
        );
    }

    /**
     * Return all options for this command.
     *
     * @return Option[]
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * Add an argument for this command.
     */
    public function addArgument(
        string $name,
        int $required = Parameter::PARAMETER_OPTIONAL,
        $defaultValue = null
    ): void {
        $this->arguments[] = new Argument($name, $required, $defaultValue);
    }

    /**
     * Return all arguments for this command.
     *
     * @return Argument[]
     */
    public function getArguments(): array
    {
        return $this->arguments;
    }

    /**
     * Build a usage string out of the arguments and options set on the command.
     * Is automatically called when an exception is caught by App.
     */
    public function getUsage(): string
    {
        $string = [];

        $string[] = $this->getName();

        foreach ($this->getArguments() as $argument) {
            if ($argument->isRequired()) {
                $string[] = $argument->getName();
            } else {
                $string[] = "[{$argument->getName()}]";
            }
        }

        foreach ($this->getOptions() as $option) {
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

    /**
     * Run the callable if it's set. This can be overridden by
     * implementing the run method on a Command class.
     */
    public function run()
    {
        $callable = $this->getCallable();
        if (is_callable($callable)) {
            return $callable($this->app, $this->output, $this->input, $this->parameter);
        }

        return false;
    }

    /**
     * Run another command from the current command, passing parameters as an array.
     *
     * @param string[] $parameters
     */
    protected function runCommand(Command $command, array $parameters = [])
    {
        $parameter = new Parameter();
        $parameter->setParameters($parameters);

        $command->prepare($this->app, $this->output, $this->input, $parameter);

        return $command->run();
    }
}
