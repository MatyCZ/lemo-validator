<?php

namespace Lemo\Validator;

use Laminas\Validator\AbstractValidator;
use Traversable;

use function date;
use function preg_replace;
use function sprintf;
use function str_replace;
use function strtotime;

class DateFormat extends AbstractValidator
{
    public const INVALID   = 'dateFormatInvalid';
    public const INVALID_DATE = 'dateFormatInvalidDate';
    public const INVALID_FORMAT = 'dateFormatInvalidFormat';

    /**
     * @var array<string|string>
     */
    protected array $messageTemplates = [
        self::INVALID => 'Invalid type given. String or integer expected',
        self::INVALID_DATE => "Invalid date '%value%' given.",
        self::INVALID_FORMAT => "Date '%value%' doesn`t match format '%format%'",
    ];

    /**
     * @var array<string|string>
     */
    protected array $messageVariables = [
        'format' => 'format',
    ];

    /** @var array{format: string|null} */
    protected $options = [
        'format' => null,
    ];

    /**
     * @param Traversable<string, string>|array{format?: string}|null $options
     */
    public function __construct(Traversable|array|null $options = null)
    {
        parent::__construct($options);
    }

    /**
     * @param mixed $value
     * @return bool
     */
    public function isValid($value): bool
    {
        $this->setValue($value);

        if (!is_scalar($value)) {
            $this->error(self::INVALID);
            return false;
        }

        $value = (string) $value;
        $value = preg_replace('/(^[0])/', '', $value);
        $value = str_replace('.0', '.', $value);

        $timestamp = strtotime($value);
        if (false === $timestamp) {
            $this->error(self::INVALID_DATE);
            return false;
        }

        $formated = date($this->getFormat(), $timestamp);
        $formated = preg_replace('/(^[0])/', '', $formated);
        $formated = str_replace('.0', '.', $formated);

        if ($value != $formated) {
            $this->error(self::INVALID_FORMAT);
            return false;
        }

        return true;
    }

    public function setFormat(string $format): self
    {
        $this->options['format'] = $format;

        return $this;
    }

    public function getFormat(): string
    {
        if (empty($this->options['format'])) {
            throw new Exception\InvalidArgumentException(
                sprintf(
                    '%s expects a "format" option; none given',
                    self::class
                )
            );
        }

        return $this->options['format'];
    }
}
