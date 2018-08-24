<?php

namespace Parable\Console\Parameter;

abstract class Base
{
    /** @var string|null */
    protected $name;

    /** @var mixed|null */
    protected $defaultValue;

    /** @var bool */
    protected $hasBeenProvided = false;

    /** @var string|null */
    protected $providedValue;

    /**
     * Set the name.
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * Return the name.
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * Set the default value.
     */
    public function setDefaultValue($defaultValue): void
    {
        $this->defaultValue = $defaultValue;
    }

    /**
     * Return the default value.
     */
    public function getDefaultValue()
    {
        return $this->defaultValue;
    }

    /**
     * Set whether the parameter has been provided.
     */
    public function setHasBeenProvided(bool $hasBeenProvided): void
    {
        $this->hasBeenProvided = $hasBeenProvided;
    }

    /**
     * Return whether the parameter has been provided.
     */
    public function hasBeenProvided(): bool
    {
        return $this->hasBeenProvided;
    }

    /**
     * Set the value that was provided.
     */
    public function setProvidedValue(?string $providedValue): void
    {
        $this->providedValue = $providedValue;
    }

    /**
     * Return the provided value.
     */
    public function getProvidedValue(): ?string
    {
        return $this->providedValue;
    }

    /**
     * Get the value. The provided value if available, otherwise the default.
     */
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
