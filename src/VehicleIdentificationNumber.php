<?php

namespace LemoBase\Validator;

use Laminas\Validator\AbstractValidator;
use Traversable;

use function array_key_exists;
use function boolval;
use function intval;
use function is_numeric;
use function is_string;
use function mb_strlen;
use function mb_substr;
use function preg_match;
use function strcmp;

class VehicleIdentificationNumber extends AbstractValidator
{
    public const VIN_INVALID        = 'vinInvalid';
    public const VIN_INVALID_CHARS  = 'vinInvalidChars';
    public const VIN_INVALID_LENGTH = 'vinInvalidLength';
    public const VIN_INVALID_CN     = 'vinInvalidCn';
    public const VIN_TOO_LONG       = 'vinTooLong';

    protected array $messageTemplates = [
        self::VIN_INVALID        => "Invalid type given. String expected",
        self::VIN_INVALID_CHARS  => "The value contains invalid characters",
        self::VIN_INVALID_LENGTH => "Invalid value length",
        self::VIN_INVALID_CN     => "Invalid control number",
        self::VIN_TOO_LONG       => "The value is greater than 17 characters"
    ];

    protected array $charValues = [
        'A' => 1,
        'J' => 1,
        'B' => 2,
        'K' => 2,
        'S' => 2,
        'C' => 3,
        'L' => 3,
        'T' => 3,
        'D' => 4,
        'M' => 4,
        'U' => 4,
        'E' => 5,
        'N' => 5,
        'V' => 5,
        'F' => 6,
        'W' => 6,
        'G' => 7,
        'P' => 7,
        'X' => 7,
        'H' => 8,
        'Y' => 8,
        'R' => 9,
        'Z' => 9,
    ];

    protected array $charVeights = [
        1  => 8,
        2  => 7,
        3  => 6,
        4  => 5,
        5  => 4,
        6  => 3,
        7  => 2,
        8  => 10,
        10 => 9,
        11 => 8,
        12 => 7,
        13 => 6,
        14 => 5,
        15 => 4,
        16 => 3,
        17 => 2,
    ];

    /**
     * true = validate controll number
     *
     * @var bool
     */
    protected bool $validateCn = false;

    /**
     * true = strict validation to 17 chars
     *
     * @var bool
     */
    protected bool $strict = false;

    /**
     * @param array|Traversable|null $options
     */
    public function __construct($options = [])
    {
        if (array_key_exists('validate_cn', $options)) {
            $this->setValidateCn(boolval($options['validate_cn']));
        }

        if (array_key_exists('strict', $options)) {
            $this->setStrict(boolval($options['strict']));
        }

        parent::__construct($options);
    }

    /**
     * Returns true if and only if $value is a valid integer
     *
     * @param  string|integer $value
     * @return bool
     */
    public function isValid($value): bool
    {
        $result = true;
        if (!is_string($value)) {
            $this->error(self::VIN_INVALID);
            $result = false;
        }

        $valueLength = mb_strlen($value, 'utf8');
        if ($valueLength > 17) {
            $this->error(self::VIN_TOO_LONG);
            $result = false;
        }

        if ($valueLength > 8 && $valueLength < 17 || true === $this->strict && 17 !== $valueLength) {
            $this->error(self::VIN_INVALID_LENGTH);
            $result = false;
        }

        if (!preg_match('~^[0-9A-Z]+$~', $value) || preg_match('~(I|Q|O)~', $value)) {
            $this->error(self::VIN_INVALID_CHARS);
            $result = false;
        }

        if (true === $result && true === $this->validateCn && 17 === $valueLength) {
            $currentCn = mb_substr($value, 8, 1, 'utf8');

            if (preg_match('~^[0-9X]{1}$~', $currentCn)) {
                $sum = 0;
                for ($i = 1; $i < 18; $i++) {
                    if (9 === $i) {
                        continue;
                    }

                    $char = mb_substr($value, $i - 1, 1, 'utf8');
                    $charValue = $this->getCharValue($char);

                    if (null === $charValue) {
                        $this->error(self::VIN_INVALID_CHARS);
                        $result = false;
                        break;
                    }

                    $sum += $charValue * $this->charVeights[$i];
                }

                $cn = $sum % 11;
                if (0 === strcmp($cn, '10')) {
                    $cn = 'X';
                }

                if (0 !== strcmp($cn, $currentCn)) {
                    $this->error(self::VIN_INVALID_CN);
                    $result = false;
                }
            }
        }

        if (true === $result) {
            $this->setValue($value);
        }

        return $result;
    }

    /**
     * @param  string $char
     * @return int|null
     */
    protected function getCharValue(string $char): ?int
    {
        if (is_numeric($char)) {
            return intval($char);
        }

        if (isset($this->charValues[$char])) {
            return $this->charValues[$char];
        }

        return null;
    }

    /**
     * @param  bool|int $strict
     * @return self
     */
    public function setValidateCn($strict): self
    {
        $this->validateCn = boolval($strict);

        return $this;
    }

    /**
     * @param  bool|int $strict
     * @return self
     */
    public function setStrict($strict): self
    {
        $this->strict = boolval($strict);

        return $this;
    }
}