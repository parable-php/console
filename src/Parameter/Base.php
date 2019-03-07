<?php declare(strict_types=1);

namespace Parable\Console\Parameter;

abstract class Base
{
    /**
     * @var string|null
     */
    protected $name;

    /**
     * @var mixed|null
     */
    protected $defaultValue;

    /**
     * @var bool
     */
    protected $hasBeenProvided = false;

    /**
     * @var string|null
     */
    protected $providedValue;

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setDefaultValue($defaultValue): void
    {
        $this->defaultValue = $defaultValue;
    }

    public function getDefaultValue()
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

    public function getValue()
    {
        if ($this->getProvidedValue() !== null) {
            return $this->getProvidedValue();
        }

        return $this->getDefaultValue();
    }

    /**
     * Add data from the parameter arguments to decide whether this parameter type
     * has been provided and set the provided value, if any.
     *
     * @param string[] $parameters
     */
    abstract public function addParameters(array $parameters): void;
}
