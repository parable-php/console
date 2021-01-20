<?php declare(strict_types=1);

namespace Parable\Console;

use Parable\Console\Parameters\ArgumentParameter;
use Parable\Console\Parameters\OptionParameter;

class Command
{
    protected ?Application $application = null;
    protected ?Output $output = null;
    protected ?Input $input = null;
    protected ?Parameter $parameter = null;
    protected ?string $name = null;
    protected ?string $description = null;

    /** @var callable|null */
    protected $callable;

    /** @var OptionParameter[] */
    protected array $options = [];

    /** @var ArgumentParameter[] */
    protected array $arguments = [];

    public function prepare(
        Application $application,
        Output $output,
        Input $input,
        Parameter $parameter
    ): void {
        $this->application = $application;
        $this->output = $output;
        $this->input = $input;
        $this->parameter = $parameter;
    }

    public function isPrepared(): bool
    {
        return $this->application && $this->output && $this->input && $this->parameter;
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
        mixed $defaultValue = null,
        bool $flagOption = false
    ): void {
        $this->options[$name] = new OptionParameter(
            $name,
            $valueRequired,
            $defaultValue,
            $flagOption
        );
    }

    /**
     * @return OptionParameter[]
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    public function addArgument(
        string $name,
        int $required = Parameter::PARAMETER_OPTIONAL,
        mixed $defaultValue = null
    ): void {
        $this->arguments[] = new ArgumentParameter($name, $required, $defaultValue);
    }

    /**
     * @return ArgumentParameter[]
     */
    public function getArguments(): array
    {
        return $this->arguments;
    }

    public function run(): void
    {
        $callable = $this->getCallable();
        if (is_callable($callable)) {
            $callable($this->application, $this->output, $this->input, $this->parameter);
        }
    }

    /**
     * @param string[] $parameters
     */
    protected function runCommand(Command $command, array $parameters = []): void
    {
        $parameter = new Parameter();
        $parameter->setParameters($parameters);

        $command->prepare($this->application, $this->output, $this->input, $parameter);

        $command->run();
    }
}
