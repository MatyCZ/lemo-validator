<?php

namespace Lemo\Validator;

use Laminas\Validator\AbstractValidator;
use Traversable;

use function is_scalar;
use function sprintf;
use function strtotime;

class DateLessThan extends AbstractValidator
{
    public const INVALID = 'dateLessThanInvalid';
    public const NOT_LESS = 'notDateLessThan';
    public const NOT_LESS_INCLUSIVE = 'notDateLessThanInclusive';

    /**
     * @var array<string|string>
     */
    protected array $messageTemplates = [
        self::INVALID => 'Invalid type given. String or integer expected',
        self::NOT_LESS => "The input is not less than date '%max%'",
        self::NOT_LESS_INCLUSIVE => "The input is not less or equal than date '%max%'",
    ];

    /**
     * @var array<string, array<string, string>>
     */
    protected array $messageVariables = [
        'max' => ['options' => 'max'],
    ];

    /** @var array{inclusive: bool, max: string|null} */
    protected $options = [
        'inclusive' => false,
        'max' => null,
    ];

    /**
     * @param Traversable<string, bool|string>|array{inclusive?: bool, max?: string}|null $options
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

        $max = $this->getMax();
        $value = (string) $value;

        if ($this->getInclusive()) {
            if (strtotime($value) > strtotime($max)) {
                $this->error(self::NOT_LESS_INCLUSIVE);
                return false;
            }
        } else {
            if (strtotime($value) >= strtotime($max)) {
                $this->error(self::NOT_LESS);
                return false;
            }
        }

        return true;
    }

    public function setMax(string $max): self
    {
        $this->options['max'] = $max;

        return $this;
    }

    public function getMax(): string
    {
        if (empty($this->options['max'])) {
            throw new Exception\InvalidArgumentException(
                sprintf(
                    '%s expects a "max" option; none given',
                    self::class
                )
            );
        }

        return $this->options['max'];
    }

    public function setInclusive(bool $inclusive): self
    {
        $this->options['inclusive'] = $inclusive;

        return $this;
    }

    public function getInclusive(): bool
    {
        return $this->options['inclusive'];
    }
}
