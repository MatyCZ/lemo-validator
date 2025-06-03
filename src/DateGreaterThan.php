<?php

namespace Lemo\Validator;

use Laminas\Validator\AbstractValidator;
use Traversable;

use function is_scalar;
use function sprintf;
use function strtotime;

class DateGreaterThan extends AbstractValidator
{
    public const INVALID   = 'dateGreaterThanInvalid';
    public const NOT_GREATER = 'notDateGreaterThan';
    public const NOT_GREATER_INCLUSIVE = 'notDateGreaterThanInclusive';

    /**
     * @var array<string|string>
     */
    protected array $messageTemplates = [
        self::INVALID => 'Invalid type given. String or integer expected',
        self::NOT_GREATER => "The input is not greater than date '%min%'",
        self::NOT_GREATER_INCLUSIVE => "The input is not greater or equal than date '%min%'",
    ];

    /**
     * @var array<string, array<string, string>>
     */
    protected array $messageVariables = [
        'min' => ['options' => 'min'],
    ];

    /** @var array{inclusive: bool, min: string|null} */
    protected $options = [
        'inclusive' => false,
        'min' => null,
    ];

    /**
     * @param Traversable<string, bool|string>|array{inclusive?: bool, min?: string}|null $options
     */
    public function __construct(Traversable|array|null $options = null)
    {
        parent::__construct($options);
    }

    /**
     * @param  mixed $value
     * @return bool
     */
    public function isValid($value): bool
    {
        $this->setValue($value);

        if (!is_scalar($value)) {
            $this->error(self::INVALID);
            return false;
        }

        $min = $this->getMin();
        $value = (string) $value;

        if ($this->getInclusive()) {
            if (strtotime($value) < strtotime($min)) {
                $this->error(self::NOT_GREATER_INCLUSIVE);
                return false;
            }
        } else {
            if (strtotime($value) <= strtotime($min)) {
                $this->error(self::NOT_GREATER);
                return false;
            }
        }

        return true;
    }

    /**
     * Sets the min option
     */
    public function setMin(string $min): self
    {
        $this->options['min'] = $min;

        return $this;
    }

    /**
     * Returns the min option
     *
     * @return string
     */
    public function getMin(): string
    {
        if (empty($this->options['min'])) {
            throw new Exception\InvalidArgumentException(
                sprintf(
                    '%s expects a "min" option; none given',
                    self::class
                )
            );
        }

        return $this->options['min'];
    }

    /**
     * Sets the inclusive option
     */
    public function setInclusive(bool $inclusive): self
    {
        $this->options['inclusive'] = $inclusive;

        return $this;
    }

    /**
     * Returns the inclusive option
     */
    public function getInclusive(): bool
    {
        return $this->options['inclusive'];
    }
}
