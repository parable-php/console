<?php declare(strict_types=1);

namespace Parable\Console\Parameters;

use Parable\Console\ConsoleException;
use Parable\Console\Parameter;

class ArgumentParameter extends AbstractParameter
{
    protected int $required;
    protected ?int $order = null;

    public function __construct(
        string $name,
        int $required = Parameter::PARAMETER_OPTIONAL,
        mixed $defaultValue = null
    ) {
        $this->setName($name);
        $this->setRequired($required);
        $this->setDefaultValue($defaultValue);
    }

    public function setRequired(int $required): void
    {
        if (!in_array(
            $required,
            [
                Parameter::PARAMETER_REQUIRED,
                Parameter::PARAMETER_OPTIONAL,
            ],
            true
        )) {
            throw ConsoleException::fromMessage('Required must be one of the PARAMETER_* constants.');
        }

        $this->required = $required;
    }

    public function isRequired(): bool
    {
        return $this->required === Parameter::PARAMETER_REQUIRED;
    }

    public function setOrder(int $order): void
    {
        $this->order = $order;
    }

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
