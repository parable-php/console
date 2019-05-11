<?php declare(strict_types=1);

namespace Parable\Console;

use Parable\Console\Parameters\ArgumentParameter;
use Parable\Console\Parameters\OptionParameter;

class Parameter
{
    const PARAMETER_REQUIRED = 1;
    const PARAMETER_OPTIONAL = 2;

    const OPTION_VALUE_REQUIRED = 11;
    const OPTION_VALUE_OPTIONAL = 12;

    /**
     * @var string[]
     */
    protected $parameters = [];

    /**
     * @var string
     */
    protected $scriptName;

    /**
     * @var string|null
     */
    protected $commandName;

    /**
     * @var array
     */
    protected $options = [];

    /**
     * @var array
     */
    protected $flagOptions = [];

    /**
     * @var array
     */
    protected $arguments = [];

    /**
     * @var OptionParameter[]
     */
    protected $commandOptions = [];

    /**
     * @var ArgumentParameter[]
     */
    protected $commandArguments = [];

    /**
     * @var bool
     */
    protected $commandNameEnabled = true;

    public function __construct()
    {
        $this->setParameters($_SERVER["argv"]);
    }

    /**
     * @param string[] $parameters
     */
    public function setParameters(array $parameters): void
    {
        $this->parameters = $parameters;
        $this->parseParameters();
    }

    /**
     * @return string[]
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }

    /**
     * Split the parameters into script name, command name, options and arguments.
     *
     * Flag options can be passed in a single set preceded by a dash:
     *   -a -b -c
     * or concatenated together, which looks like this:
     *   -abc
     *
     * When an option is encountered with a value set, everything after = is seen as that value:
     *   -a -b -c=def
     * or:
     *   -abc=def
     */
    public function parseParameters(): void
    {
        $this->reset();

        // Extract the scriptName
        $this->scriptName = array_shift($this->parameters);

        foreach ($this->parameters as $parameter) {
            $optionString = ltrim($parameter, '-');

            if (substr($parameter, 0, 2) === "--") {
                $this->parseOption($optionString);
            } elseif (substr($parameter, 0, 1) === "-") {
                $this->parseFlagOption($optionString);
            } else {
                $this->parseArgument($parameter);
            }
        }
    }

    protected function parseOption(string $optionString): void
    {
        $optionParts = explode('=', $optionString);

        if (count($optionParts) > 1) {
            list($key, $value) = $optionParts;
        } else {
            $key = $optionString;
            $value = true;
        }

        $this->options[$key] = $value;
    }

    /**
     * Parse a flag option string (-a or -abc, this last version
     * is parsed as a concatenated string of one char per option).
     */
    protected function parseFlagOption(string $optionString): void
    {
        for ($i = 0; $i < strlen($optionString); $i++) {
            $optionChar = substr($optionString, $i, 1);
            $optionParts = explode('=', substr($optionString, $i + 1));

            if (count($optionParts) > 1 && empty($optionParts[0])) {
                $value = $optionParts[1];
            } elseif ($optionChar !== "=") {
                $value = true;
            } else {
                break;
            }

            $this->flagOptions[$optionChar] = $value;
        }
    }

    /**
     * Parse argument. If no command name set and commands are enabled,
     * interpret as command name. Otherwise, add to argument list.
     */
    protected function parseArgument(string $parameter): void
    {
        if ($this->commandNameEnabled && !$this->commandName) {
            $this->commandName = $parameter;
        } else {
            $this->arguments[] = $parameter;
        }
    }

    public function getScriptName(): string
    {
        return $this->scriptName;
    }

    public function getCommandName(): ?string
    {
        return $this->commandName;
    }

    /**
     * @param OptionParameter[] $options
     */
    public function setCommandOptions(array $options): void
    {
        foreach ($options as $name => $option) {
            if ((!$option instanceof OptionParameter)) {
                throw Exception::fromMessage(
                    "Options must be instances of Parameter\\Option. %s is not.",
                    $name
                );
            }
            $this->commandOptions[$option->getName()] = $option;
        }
    }

    /**
     * Checks the options set against the parameters set. Takes into account whether an option is required
     * to be passed or not, or a value is required if it's passed, or sets the defaultValue if given and necessary.
     */
    public function checkCommandOptions(): void
    {
        foreach ($this->commandOptions as $option) {
            if ($option->isFlagOption()) {
                $parameters = $this->flagOptions;
            } else {
                $parameters = $this->options;
            }
            $option->addParameters($parameters);

            if ($option->isValueRequired() && $option->hasBeenProvided() && !$option->getValue()) {
                $dashes = $option->isFlagOption() ? '-' : '--';

                throw Exception::fromMessage(
                    "Option '%s%s' requires a value, which is not provided.",
                    $dashes,
                    $option->getName()
                );
            }
        }
    }

    /**
     * Returns null if the value doesn't exist. Otherwise, it's whatever was passed to it or set
     * as a default value.
     */
    public function getOption(string $name)
    {
        if (!array_key_exists($name, $this->commandOptions)) {
            return null;
        }

        $option = $this->commandOptions[$name];

        if ($option->hasBeenProvided() && $option->getProvidedValue() === null && $option->getDefaultValue() === null) {
            return true;
        }

        return $option->getValue();
    }

    public function getOptions(): array
    {
        $returnArray = [];
        foreach ($this->commandOptions as $option) {
            $returnArray[$option->getName()] = $this->getOption($option->getName());
        }
        return $returnArray;
    }

    /**
     * Set the arguments from a command.
     *
     * @param ArgumentParameter[] $arguments
     */
    public function setCommandArguments(array $arguments): void
    {
        $orderedArguments = [];
        foreach ($arguments as $index => $argument) {
            if (!($argument instanceof ArgumentParameter)) {
                throw Exception::fromMessage(
                    "Arguments must be instances of Parameter\\Argument. The item at index %d is not.",
                    $index
                );
            }

            $argument->setOrder($index);
            $orderedArguments[$index] = $argument;
        }
        $this->commandArguments = $orderedArguments;
    }

    /**
     * Checks the arguments set against the parameters set. Takes into account whether an argument is required
     * to be passed or not.
     */
    public function checkCommandArguments(): void
    {
        foreach ($this->commandArguments as $index => $argument) {
            $argument->addParameters($this->arguments);

            if ($argument->isRequired() && !$argument->hasBeenProvided()) {
                throw Exception::fromMessage(
                    "Required argument with index #%d '%s' not provided.",
                    $index,
                    $argument->getName()
                );
            }
        }
    }

    /**
     * Returns null if the value doesn't exist. Returns default value if set from command, and the actual value
     * if passed on the command line.
     */
    public function getArgument(string $name)
    {
        foreach ($this->commandArguments as $argument) {
            if ($argument->getName() === $name) {
                return $argument->getValue();
            }
        }

        return null;
    }

    /**
     * Return all arguments passed.
     */
    public function getArguments(): array
    {
        $returnArray = [];
        foreach ($this->commandArguments as $argument) {
            $returnArray[$argument->getName()] = $argument->getValue();
        }

        return $returnArray;
    }

    /**
     * Reset the class to a fresh state.
     */
    protected function reset(): void
    {
        $this->scriptName = null;
        $this->commandName = null;
        $this->options = [];
        $this->arguments = [];
    }

    /**
     * Remove the command name from the arguments, if a command name is actually set.
     */
    public function enableCommandName(): void
    {
        if (!$this->commandNameEnabled
            && $this->commandName
            && isset($this->arguments[0])
            && $this->arguments[0] === $this->commandName
        ) {
            unset($this->arguments[0]);
            $this->arguments = array_values($this->arguments);
        }

        $this->commandNameEnabled = true;
    }

    /**
     * Add the command name to the arguments, if a command name is set.
     */
    public function disableCommandName(): void
    {
        if ($this->commandNameEnabled && $this->commandName) {
            array_unshift($this->arguments, $this->commandName);
        }
        $this->commandNameEnabled = false;
    }
}
