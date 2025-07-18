<?php

namespace Lemo\Validator;

use Laminas\Validator\AbstractValidator;

use function intval;
use function is_numeric;
use function is_scalar;
use function mb_strlen;
use function mb_substr;
use function preg_match;
use function strcmp;

class VehicleIdentificationNumber extends AbstractValidator
{
    public const VIN_INVALID = 'vinInvalid';
    public const VIN_INVALID_CHARS = 'vinInvalidChars';
    public const VIN_INVALID_CN = 'vinInvalidCn';
    public const VIN_INVALID_CONSECUTIVE_ONES = 'vinInvalidConsecutiveOnes';
    public const VIN_INVALID_CONSECUTIVE_ZEROS = 'vinInvalidConsecutiveZeros';
    public const VIN_INVALID_LENGTH = 'vinInvalidLength';

    /**
     * @var array<string, string>
     */
    protected array $messageTemplates = [
        self::VIN_INVALID => "Invalid type given. String expected",
        self::VIN_INVALID_CHARS => "The value contains invalid characters",
        self::VIN_INVALID_CN => "Invalid control number",
        self::VIN_INVALID_CONSECUTIVE_ONES => "The value contains consecutive ones",
        self::VIN_INVALID_CONSECUTIVE_ZEROS => "The value contains consecutive zeros",
        self::VIN_INVALID_LENGTH => "Invalid value length",
    ];

    /**
     * @var array<string, int>
     */
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

    /**
     * @var array<int, int>
     */
    protected array $charWeights = [
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

    /** @var array{allowLongSequences: bool, strict: bool} */
    protected $options = [
        'allowLongSequences' => true,
        'strict' => false,
    ];

    public function setAllowLongSequences(bool $allowLongSequences): self
    {
        $this->options['allowLongSequences'] = $allowLongSequences;

        return $this;
    }

    public function getAllowLongSequences(): bool
    {
        return $this->options['allowLongSequences'];
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

    /**
     * Returns true if and only if $value is a valid integer
     *
     * @param  string|integer $value
     * @return bool
     */
    public function isValid($value): bool
    {
        if (!is_scalar($value)) {
            $this->error(self::VIN_INVALID);
            return false;
        }

        $value = (string) $value;

        $valueLength = mb_strlen($value, 'utf8');
        if (17 !== $valueLength) {
            $this->error(self::VIN_INVALID_LENGTH);

            return false;
        }

        if (!preg_match('~^[0-9A-Z]+$~', $value) || preg_match('~([IQO])~', $value)) {
            $this->error(self::VIN_INVALID_CHARS);

            return false;
        }

        if (true !== $this->getAllowLongSequences() && preg_match('/0{7}/', $value)) {
            $this->error(self::VIN_INVALID_CONSECUTIVE_ZEROS);

            return false;
        }

        if (true !== $this->getAllowLongSequences() && preg_match('/1{6}/', $value)) {
            $this->error(self::VIN_INVALID_CONSECUTIVE_ONES);

            return false;
        }

        $currentCn = mb_substr($value, 8, 1, 'utf8');

        if (
            $this->getStrict() && (preg_match('~^.{8}[0-9X]~', $value)
            || preg_match('~^[1-5]~', $value))
        ) {
            $sum = 0;
            for ($i = 1; $i < 18; $i++) {
                if (9 === $i) {
                    continue;
                }

                $char = mb_substr($value, $i - 1, 1, 'utf8');
                $charValue = $this->getCharValue($char);

                if (null === $charValue) {
                    $this->error(self::VIN_INVALID_CHARS);

                    return false;
                }

                $sum += $charValue * $this->charWeights[$i];
            }

            $cn = $sum % 11;
            if (0 === strcmp((string) $cn, '10')) {
                $cn = 'X';
            }

            if (0 !== strcmp((string) $cn, $currentCn)) {
                $this->error(self::VIN_INVALID_CN);

                return false;
            }
        }

        $this->setValue($value);

        return true;
    }

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
}
