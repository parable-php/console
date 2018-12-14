<?php

namespace Parable\Console\Parameter;

use Parable\Console\Exception;
use Parable\Console\Parameter;

class Argument extends Base
{
    /** @var int|null */
    protected $required;

    /** @var int|null */
    protected $order;

    /**
     * @param string     $name
     * @param int        $required
     * @param mixed|null $defaultValue
     */
    public function __construct(
        string $name,
        int $required = Parameter::PARAMETER_OPTIONAL,
        $defaultValue = null
    ) {
        $this->setName($name);
        $this->setRequired($required);
        $this->setDefaultValue($defaultValue);
    }

    /**
     * Set whether this argument is required.
     */
    public function setRequired(int $required): void
    {
        if (!in_array(
            $required,
            [
                Parameter::PARAMETER_REQUIRED,
                Parameter::PARAMETER_OPTIONAL,
            ]
        )) {
            throw Exception::fromMessage('Required must be one of the PARAMETER_* constants.');
        }

        $this->required = $required;
    }

    /**
     * Return whether the parameter is required.
     */
    public function isRequired(): bool
    {
        return $this->required === Parameter::PARAMETER_REQUIRED;
    }

    /**
     * Set the order for this argument.
     */
    public function setOrder(int $order): void
    {
        $this->order = $order;
    }

    /**
     * Return the order for this argument.
     */
    public function getOrder(): ?int
    {
        return $this->order;
    }

    /**
     * @inheritdoc
     */
    public function addParameters(array $parameters): void
    {
        $this->setProvidedValue(null);
        $this->setHasBeenProvided(false);

        if (!array_key_exists($this->getOrder(), $parameters)) {
            return;
        }

        $this->setHasBeenProvided(true);
        $this->setProvidedValue($parameters[$this->getOrder()]);
    }
}
