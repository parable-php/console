<?php declare(strict_types=1);

namespace Parable\Console\Parameter;

use Parable\Console\Exception;
use Parable\Console\Parameter;

class Option extends AbstractParameter
{
    /**
     * @var int
     */
    protected $valueType;

    /**
     * @var bool
     */
    protected $flagOption = false;

    public function __construct(
        string $name,
        int $valueType = Parameter::OPTION_VALUE_OPTIONAL,
        $defaultValue = null,
        bool $flagOption = false
    ) {
        $this->setName($name);
        $this->setValueType($valueType);
        $this->setDefaultValue($defaultValue);
        $this->setFlagOption($flagOption);
    }

    public function setValueType(int $valueType): void
    {
        if (!in_array(
            $valueType,
            [
                Parameter::OPTION_VALUE_REQUIRED,
                Parameter::OPTION_VALUE_OPTIONAL,
            ]
        )) {
            throw Exception::fromMessage('Value type must be one of the OPTION_* constants.');
        }

        $this->valueType = $valueType;
    }

    public function isValueRequired(): bool
    {
        return $this->valueType === Parameter::OPTION_VALUE_REQUIRED;
    }

    public function setFlagOption(bool $enabled): void
    {
        if ($enabled && mb_strlen($this->getName()) > 1) {
            throw Exception::fromMessage("Flag options can only have a single-letter name.");
        }
        $this->flagOption = $enabled;
    }

    public function isFlagOption(): bool
    {
        return $this->flagOption;
    }

    /**
     * @inheritdoc
     */
    public function addParameters(array $parameters): void
    {
        $this->setProvidedValue(null);
        $this->setHasBeenProvided(false);

        if (!array_key_exists($this->getName(), $parameters)) {
            return;
        }

        $this->setHasBeenProvided(true);

        if ($parameters[$this->getName()] !== true) {
            $this->setProvidedValue($parameters[$this->getName()]);
        }
    }
}
