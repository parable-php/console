<?php declare(strict_types=1);

namespace Parable\Console;

use Parable\Console\Parameter\Argument;
use Parable\Console\Parameter\Option;

class Command
{
    /**
     * @var App
     */
    protected $app;

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
     * @var string|null
     */
    protected $description;

    /**
     * @var callable|null
     */
    protected $callable;

    /**
     * @var Option[]
     */
    protected $options = [];

    /**
     * @var Argument[]
     */
    protected $arguments = [];

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

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setCallable(callable $callable): void
    {
        $this->callable = $callable;
    }

    public function getCallable(): ?callable
    {
        return $this->callable;
    }

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
     * @return Option[]
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    public function addArgument(
        string $name,
        int $required = Parameter::PARAMETER_OPTIONAL,
        $defaultValue = null
    ): void {
        $this->arguments[] = new Argument($name, $required, $defaultValue);
    }

    /**
     * @return Argument[]
     */
    public function getArguments(): array
    {
        return $this->arguments;
    }

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

    public function run()
    {
        $callable = $this->getCallable();
        if (is_callable($callable)) {
            return $callable($this->app, $this->output, $this->input, $this->parameter);
        }

        return false;
    }

    /**
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
