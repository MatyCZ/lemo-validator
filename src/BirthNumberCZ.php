<?php

namespace Lemo\Validator;

use Laminas\Validator\AbstractValidator;
use Traversable;

use function checkdate;
use function is_int;
use function is_string;
use function preg_match;
use function preg_quote;
use function sprintf;

class BirthNumberCZ extends AbstractValidator
{
    public const INVALID = 'birthNumberInvalid';
    public const NOT_BIRTHNUMBER = 'notBirthNumber';

    /**
     * @var array<string, string>
     */
    protected array $messageTemplates = [
        self::INVALID => "Invalid type given. String or integer expected",
        self::NOT_BIRTHNUMBER => "The value does not appear to be a birth number",
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

        // Regularni vyrazy pro hodnoty, ktere se povazuji za validni
        $exclude = $this->getExclude();
        if (null !== $exclude) {
            foreach ($exclude as $pattern) {
                if (1 === preg_match('~' . $pattern . '~', preg_quote($value, '~'))) {
                    return true;
                }
            }
        }

        if (!preg_match('~^\s*(\d\d)(\d\d)(\d\d)(\d\d\d)(\d?)\s*$~', preg_quote($value, '~'), $matches)) {
            $this->error(self::NOT_BIRTHNUMBER);
            return false;
        }

        [, $year, $month, $day, $ext, $c] = $matches;

        $yearMax = (int) date('Y');
        $yearMin = (int) date('Y', strtotime('-100 YEARS'));

        // Osetreni roku
        if (9 === strlen($value) || 10 === strlen($value) && $year >= 54) {
            $year += 1900;
        } else {
            $year += 2000;
        }

        if ($year > $yearMax) {
            $year -= 100;
        }

        if ($year < $yearMin) {
            $year += 100;
        }

        // Do roku 1954 pridelovano 9 mistne RC nelze overit
        if ($c === '' && $year < 1954) {
            return true;
        }

        // Kontrolni cislice
        $mod = ($year . $month . $day . $ext) % 11;
        if ($mod === 10) {
            $mod = 0;
        }
        if ($mod !== (int) $c) {
            $this->error(self::NOT_BIRTHNUMBER);
            return false;
        }

        // K mesici muze byt pricteno 20, 50 nebo 70
        if ($month > 70 && $year > 2003) {
            $month -= 70;
        } elseif ($month > 50) {
            $month -= 50;
        } elseif ($month > 20 && $year > 2003) {
            $month -= 20;
        }

        if (!checkdate($month, $day, $year)) {
            $this->error(self::NOT_BIRTHNUMBER);
            return false;
        }

        if ($day <= 31 && $month <= 12 && $year >= $yearMin && $year <= $yearMax) {
            return true;
        }

        return false;
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
