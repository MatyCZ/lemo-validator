<?php

namespace Lemo\Validator;

use Laminas\Validator\AbstractValidator;
use Traversable;

use function is_int;
use function is_string;
use function preg_match;
use function preg_quote;
use function sprintf;
use function str_pad;

class IdentificationNumberCZ extends AbstractValidator
{
    public const INVALID = 'identificationNumberInvalid';
    public const NOT_IDENTIFICATIONNUMBER = 'notIdentificationNumber';

    /**
     * @var array<string|string>
     */
    protected array $messageTemplates = [
        self::INVALID => "Invalid type given. String or integer expected",
        self::NOT_IDENTIFICATIONNUMBER => "The value does not appear to be an identification number",
    ];

    /** @var array{exclude: array<string>|null} */
    protected $options = [
        'exclude' => null, // Excluded regular expression patterns without delimiter ['0000$', '9999$']
    ];

    /**
     * @param Traversable<string, array<string>>|array{exclude: array<string>}|null $options
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

        if (!is_int($value) && !is_string($value)) {
            $this->error(self::INVALID);
            return false;
        }

        $value = (string) $value;
        $value = str_pad($value, 8, '0', STR_PAD_LEFT);

        if (!preg_match('~^\d{8}$~', preg_quote($value, '~'))) {
            $this->error(self::NOT_IDENTIFICATIONNUMBER);
            return false;
        }

        // Regularni vyrazy pro hodnoty, ktere se povazuji za validni
        $exclude = $this->getExclude();
        if (null !== $exclude) {
            foreach ($exclude as $pattern) {
                if (1 === preg_match('~' . $pattern . '~', preg_quote($value, '~'))) {
                    return true;
                }
            }
        }

        // kontrolní součet
        $a = 0;
        for ($i = 0; $i < 7; $i++) {
            $a += (int) $value[$i] * (8 - $i);
        }

        $a = $a % 11;

        if ($a === 0) {
            $c = 1;
        } elseif ($a === 10) {
            $c = 1;
        } elseif ($a === 1) {
            $c = 0;
        } else {
            $c = 11 - $a;
        }

        if ((int) $value[7] !== $c) {
            $this->error(self::NOT_IDENTIFICATIONNUMBER);
            return false;
        }

        return true;
    }

    /**
     * Set excluded patterns
     *
     * @param array<string> $exclude
     * @return self
     */
    public function setExclude(array $exclude): self
    {
        foreach ($exclude as $pattern) {
            $pattern = $this->testPregPattern($pattern);

            $this->options['exclude'][] = $pattern;
        }

        return $this;
    }

    /**
     * @return array<string>|null
     */
    public function getExclude(): ?array
    {
        return $this->options['exclude'];
    }

    /**
     * Test regular expression pattern
     */
    protected function testPregPattern(string $pattern): string
    {
        if (false === @preg_match('~' . $pattern . '~', "")) {
            throw new Exception\InvalidArgumentException(
                sprintf(
                    'Invalid regular expression pattern `%s` in options',
                    $pattern
                )
            );
        }

        return $pattern;
    }
}
