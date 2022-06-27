<?php

namespace LemoBase\Validator;

use Laminas\Validator\AbstractValidator;

use function array_key_exists;
use function is_array;
use function is_int;
use function is_string;

class UniqueValue extends AbstractValidator
{
    public const INVALID = 'valueInvalid';
    public const NOT_UNIQUE = 'valueNotUnique';

    protected array $messageTemplates = [
        self::INVALID => "Invalid type given. String or integer expected",
        self::NOT_UNIQUE => "Value must be unique",
    ];

    protected bool $caseSensitive = false;
    protected array $haystack = [];

    public function __construct($options = array())
    {
        if (array_key_exists('haystack', $options) && is_array($options['haystack'])) {
            $this->setHaystack($options['haystack']);
        }

        if (array_key_exists('case_sensitive', $options) && true === $options['case_sensitive']) {
            $this->setCaseSensitive(true);
        }

        parent::__construct($options);
    }

    /**
     * If value is unique oposit the haystack, returns true
     *
     * @param  string $value
     * @return bool
     */
    public function isValid($value): bool
    {
        if (!is_string($value) && !is_int($value)) {
            $this->error(self::INVALID);
            return false;
        }

        if (empty($this->haystack)) {
            return true;
        }

        if ($this->inArray($value, $this->haystack)) {
            $this->error(self::NOT_UNIQUE);
            return false;
        }

        return true;
    }

    /**
     * Lower string multibyte by default
     *
     * @param  string $string
     * @param  string $encoding
     * @return string
     */
    protected function toLower(string $string, string $encoding = 'UTF-8'): string
    {
        return mb_strtolower($string, $encoding);
    }

    /**
     * Lower string on all levels of array
     *
     * @param  array $array
     * @return array
     */
    protected function arrayToLower(array $array): array
    {
        foreach ($array as &$value) {
            switch (true) {
                case is_string($value):
                    $value = $this->toLower($value);
                    break;
                case is_array($value):
                    $value = $this->arrayToLower($value);
                    break;
            }
        }

        return $array;
    }

    /**
     * In array comparsion, case insensitive by default
     *
     * @param  string|array $needle
     * @param  string|array $haystack
     * @param  bool         $caseSensitive
     * @param  bool         $strict
     * @return bool
     */
    protected function inArray($needle, $haystack, bool $caseSensitive = false, bool $strict = false): bool
    {
        switch ($caseSensitive) {
            case true:
                return in_array($needle, $haystack, $strict);
            default:
            case false:
                if (is_array($needle)) {
                    return in_array($this->arrayToLower($needle), $this->arrayToLower($haystack), $strict);
                } else {
                    return in_array($this->toLower($needle), $this->arrayToLower($haystack), $strict);
                }
        }
    }

    /**
     * Set case sensitive comparsion
     *
     * @param  bool $caseSensitive
     * @return self
     */
    public function setCaseSensitive(bool $caseSensitive): self
    {
        $this->caseSensitive = $caseSensitive;

        return $this;
    }

    /**
     * Set haystack for comparsion
     *
     * @param  array $values
     * @return self
     */
    public function setHaystack(array $values): self
    {
        $this->haystack = $values;

        return $this;
    }
}
