<?php

namespace Lemo\Validator;

use Laminas\Validator\AbstractValidator;
use Traversable;

use function in_array;
use function is_scalar;
use function is_string;
use function mb_strtolower;
use function sprintf;

class UniqueValue extends AbstractValidator
{
    public const INVALID = 'valueInvalid';
    public const NOT_UNIQUE = 'valueNotUnique';

    /**
     * @var array<string, string>
     */
    protected array $messageTemplates = [
        self::INVALID => "Invalid type given. Boolean, float, integer, or string expected",
        self::NOT_UNIQUE => "Value must be unique",
    ];

    /** @var array{caseSensitive: bool, haystack: array<bool|float|int|string>|null, strict: bool} */
    protected $options = [
        'caseSensitive' => false,
        'haystack' => null,
        'strict' => false,
    ];

    /**
     * @param Traversable<string, array<string>|bool>|array{caseSensitive: bool, haystack: array<bool|float|int|string>|null, strict: bool}|null $options
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
        if (!is_scalar($value)) {
            $this->error(self::INVALID);
            return false;
        }

        if ($this->isValidAgainstHaystack($value)) {
            $this->error(self::NOT_UNIQUE);
            return false;
        }

        return true;
    }

    protected function isValidAgainstHaystack(mixed $value): bool
    {
        $caseSensitive = $this->getCaseSensitive();
        $haystack = $this->getHaystack();
        $strict = $this->getStrict();

        if (true === $caseSensitive) {
            return in_array($value, $haystack, $strict);
        }

        return in_array(
            $this->toLower($value),
            $this->toLowerHaystack($haystack),
            $strict
        );
    }

    /**
     * Lower string multibyte by default
     */
    protected function toLower(string $string, string $encoding = 'UTF-8'): string
    {
        return mb_strtolower($string, $encoding);
    }

    /**
     * Lower strings on all levels of array
     *
     * @param  array<bool|float|int|string> $array
     * @return array<bool|float|int|string>
     */
    protected function toLowerHaystack(array $array): array
    {
        foreach ($array as &$value) {
            if (is_string($value)) {
                $value = $this->toLower($value);
            }
        }

        return $array;
    }

    public function setCaseSensitive(bool $caseSensitive): self
    {
        $this->options['caseSensitive'] = $caseSensitive;

        return $this;
    }

    public function getCaseSensitive(): bool
    {
        return $this->options['caseSensitive'];
    }

    /**
     * @param  array<string> $values
     * @return self
     */
    public function setHaystack(array $values): self
    {
        $this->options['haystack'] = $values;

        return $this;
    }

    /**
     * @return array<bool|float|int|string>
     */
    public function getHaystack(): array
    {
        if (empty($this->options['haystack'])) {
            throw new Exception\InvalidArgumentException(
                sprintf(
                    '%s expects a "haystack" option; none given',
                    self::class
                )
            );
        }

        return $this->options['haystack'];
    }

    public function setStrict(bool $strict): self
    {
        $this->options['strict'] = $strict;

        return $this;
    }

    public function getStrict(): bool
    {
        return $this->options['strict'];
    }
}
