<?php

namespace Lemo\Validator;

use Laminas\Validator\AbstractValidator;
use Traversable;

use function chr;
use function is_int;
use function preg_match;
use function str_replace;
use function str_split;
use function strlen;

class StringContains extends AbstractValidator
{
    public const NO_ALPHA            = 'noAlpha';
    public const NO_VALID_CHARACTERS = 'noValidCharacters';
    public const NO_CAPITAL_LETTER   = 'noCapitalLetter';
    public const NO_NUMERIC          = 'noNumeric';
    public const NO_SMALL_LETTER     = 'noSmallLetter';

    /**
     * @var array<string, string>
     */
    protected array $messageTemplates = [
        self::NO_ALPHA            => 'Value must contain at least one alphabetic character',
        self::NO_VALID_CHARACTERS => 'The input contains an invalid characters',
        self::NO_CAPITAL_LETTER   => 'Value must contain at least one capital letter',
        self::NO_NUMERIC          => 'Value must contain at least one numeric character',
        self::NO_SMALL_LETTER     => 'Value must contain at least one small letter',
    ];

    /** @var array{characters: int|string|null, requireAlpha: bool, requireCapitalLetter: bool, requireNumeric: bool, requireSmallLetter: bool} */
    protected $options = [
        'characters' => null,
        'requireAlpha' => false,
        'requireCapitalLetter' => false,
        'requireNumeric' => false,
        'requireSmallLetter' => false,
    ];

    /**
     * string - valid characters
     * int - 1 .. 128 ASCII characters
     *
     * @param Traversable<string, bool|string>|array{inclusive: bool, max: string}|null $options
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
        $characters = $this->getCharacters();
        $requireAlpha = $this->getRequireAlpha();
        $requireCapitalLetter = $this->getRequireCapitalLetter();
        $requireNumeric = $this->getRequireNumeric();
        $requireSmallLetter = $this->getRequireSmallLetter();

        $value = (string) $value;

        // Alphanumeric
        if (true === $requireAlpha && 0 == preg_match('/[a-z]/i', $value)) {
            $this->error(self::NO_ALPHA);
            return false;
        }

        // ASCII characters or allowed cahracters
        if (null !== $characters) {
            if (is_int($characters)) {
                for ($x = 0; $x < $characters; ++$x) {
                    $value = str_replace(chr($x), '', $value);
                }
            } else {
                $chars = str_split($characters);
                foreach ($chars as $char) {
                    $value = str_replace($char, '', $value);
                }
            }

            if (strlen($value) > 0) {
                $this->error(self::NO_VALID_CHARACTERS);
                return false;
            }
        }

        // Capital letter
        if (true === $requireCapitalLetter && 0 == preg_match('/[A-Z]/', $value)) {
            $this->error(self::NO_CAPITAL_LETTER);
            return false;
        }

        // Numeric
        if (true === $requireNumeric && 0 == preg_match('/\d/', $value)) {
            $this->error(self::NO_NUMERIC);
            return false;
        }

        // Small letter
        if (true === $requireSmallLetter && 0 == preg_match('/[a-z]/', $value)) {
            $this->error(self::NO_SMALL_LETTER);
            return false;
        }

        return true;
    }

    public function setCharacters(int|string|null $characters): self
    {
        if (is_int($characters) && ($characters < 0 || $characters > 128)) {
            throw new Exception\InvalidArgumentException(
                sprintf(
                    '%s expects a "characters" option; none given',
                    self::class
                )
            );
        }

        $this->options['characters'] = $characters;

        return $this;
    }

    public function getCharacters(): int|string|null
    {
        return $this->options['characters'];
    }

    public function setRequireAlpha(bool $requireAlpha): self
    {
        $this->options['requireAlpha'] = $requireAlpha;

        return $this;
    }

    public function getRequireAlpha(): bool
    {
        return $this->options['requireAlpha'];
    }

    public function setRequireCapitalLetter(bool $requireCapitalLetter): self
    {
        $this->options['requireCapitalLetter'] = $requireCapitalLetter;

        return $this;
    }

    public function getRequireCapitalLetter(): bool
    {
        return $this->options['requireCapitalLetter'];
    }

    public function setRequireNumeric(bool $requireNumeric): self
    {
        $this->options['requireNumeric'] = $requireNumeric;

        return $this;
    }

    public function getRequireNumeric(): bool
    {
        return $this->options['requireNumeric'];
    }

    public function setRequireSmallLetter(bool $requireSmallLetter): self
    {
        $this->options['requireSmallLetter'] = $requireSmallLetter;

        return $this;
    }

    public function getRequireSmallLetter(): bool
    {
        return $this->options['requireSmallLetter'];
    }
}
