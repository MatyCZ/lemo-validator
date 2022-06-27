<?php

namespace Lemo\Validator;

use Laminas\Validator\AbstractValidator;
use Traversable;

use function checkdate;
use function is_int;
use function is_string;
use function preg_match;
use function strtotime;

class BirthNumberCZMinorChild extends AbstractValidator
{
    public const INVALID         = 'intInvalid';
    public const NOT_BIRTHNUMBER = 'notBirthNumber';
    public const NOT_MINORCHILD  = 'notMinorChild';

    /**
     * @var array<string|string>
     */
    protected array $messageTemplates = [
        self::INVALID         => "Invalid type given. String or integer expected",
        self::NOT_BIRTHNUMBER => "The value does not appear to be a birth number",
        self::NOT_MINORCHILD  => "The value does not appear to be a minor child",
    ];

    /** @var array{limit: int} */
    protected $options = [
        'limit' => 18,
    ];

    /**
     * @param Traversable<string, int>|array{limit: int}|null $options
     */
    public function __construct(Traversable|array|null $options = null)
    {
        parent::__construct($options);
    }

    /**
     * Returns true if and only if $value is a valid integer
     *
     * @param  mixed $value
     * @return bool
     */
    public function isValid($value): bool
    {
        if (!is_string($value) && !is_int($value)) {
            $this->error(self::INVALID);
            return false;
        }

        $value = (string) $value;

        $this->setValue($value);

        if (!preg_match('#^\s*(\d\d)(\d\d)(\d\d)(\d\d\d)(\d?)\s*$#', $value, $matches)) {
            $this->error(self::NOT_BIRTHNUMBER);
            return false;
        }

        [, $year, $month, $day, $ext, $c] = $matches;

        // Do roku 1954 pridelovano 9 mistne RC nelze overit
        if ($c === '') {
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

        // Kontrola data
        $year += $year < 54 ? 2000 : 1900;

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

        if (strtotime($year . '-' . $month . '-' . $day) < strtotime('-' . $this->getLimit() . ' YEARS')) {
            $this->error(self::NOT_MINORCHILD);
            return false;
        }

        return true;
    }

    public function setLimit(int $limit): self
    {
        $this->options['limit'] = $limit;

        return $this;
    }

    public function getLimit(): int
    {
        if (empty($this->options['limit'])) {
            throw new Exception\InvalidArgumentException(
                sprintf(
                    '%s expects a "limit" option; none given',
                    self::class
                )
            );
        }

        return $this->options['limit'];
    }
}
