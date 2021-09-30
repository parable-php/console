<?php declare(strict_types=1);

namespace Parable\Console\Parameters;

abstract class AbstractParameter
{
    protected ?string $name = null;
    protected mixed $defaultValue;
    protected bool $hasBeenProvided = false;
    protected ?string $providedValue = null;

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setDefaultValue(mixed $defaultValue): void
    {
        $this->defaultValue = $defaultValue;
    }

    public function getDefaultValue(): mixed
    {
        return $this->defaultValue;
    }

    public function setHasBeenProvided(bool $hasBeenProvided): void
    {
        $this->hasBeenProvided = $hasBeenProvided;
    }

    public function hasBeenProvided(): bool
    {
        return $this->hasBeenProvided;
    }

    public function setProvidedValue(?string $providedValue): void
    {
        $this->providedValue = $providedValue;
    }

    public function getProvidedValue(): ?string
    {
        return $this->providedValue;
    }

    public function getValue(): mixed
    {
        return $this->getProvidedValue() ?? $this->getDefaultValue();
    }

    /**
     * Add data from the parameter arguments to decide whether this parameter type
     * has been provided and set the provided value, if any.
     *
     * @param string[] $parameters
     */
    abstract public function addParameters(array $parameters): void;
}
