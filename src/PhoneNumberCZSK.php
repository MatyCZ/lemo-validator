<?php

namespace Lemo\Validator;

use Laminas\Validator\AbstractValidator;
use Traversable;

use function array_key_exists;
use function array_keys;
use function is_array;
use function is_string;
use function preg_match;

class PhoneNumberCZSK extends AbstractValidator
{
    public const INVALID                = 'phoneNumberInvalid';
    public const NO_MATCH               = 'phoneNumberNoMatch';
    public const NO_MATCH_INTERNATIONAL = 'phoneNumberNoMatchInternational';
    public const UNSUPPORTED            = 'phoneNumberUnsupported';

    /**
     * @var array<string|string>
     */
    protected array $messageTemplates = [
        self::INVALID                => 'Invalid type given. String expected',
        self::NO_MATCH               => 'The input does not match a phone number format',
        self::NO_MATCH_INTERNATIONAL => 'The input does not match an international phone number format',
        self::UNSUPPORTED            => 'The country provided is currently unsupported',
    ];

    /**
     * @var array<string|string>
     */
    protected array $patterns = [
        'cs-CZ' => "/^(\+?420)? ?[1-9][0-9]{2} ?[0-9]{3} ?[0-9]{3}$/",
        'sk-SK' => "/^(\+?421)? ?[1-9][0-9]{2} ?[0-9]{3} ?[0-9]{3}$/",
    ];

    /** @var array{locale: array<string>|string|null, strict: bool} */
    protected $options = [
        'locale' => null,
        'strict' => false,
    ];

    /**
     * @param Traversable<string, array<string>|bool|string>|array{locale: array<string>|string, strict: bool, }|null $options
     */
    public function __construct(Traversable|array|null $options = null)
    {
        parent::__construct($options);
    }

    /**
     * @param  string $value
     * @return bool
     */
    public function isValid($value): bool
    {
        if (!is_string($value)) {
            $this->error(self::INVALID);
            return false;
        }

        $value = (string) $value;

        $this->setValue($value);

        // Is not strict
        if (true === $this->getStrict() && !str_starts_with($value, '+')) {
            $this->error(self::NO_MATCH);
            return false;
        }

        $locale = $this->getLocale();

        if (is_array($locale)) {
            foreach ($locale as $loc) {
                if (!array_key_exists($loc, $this->patterns)) {
                    $this->error(self::UNSUPPORTED);
                    return false;
                }

                if (preg_match($this->patterns[$loc], $value)) {
                    return true;
                }
            }
        } elseif (null !== $locale) {
            if (!array_key_exists($locale, $this->patterns)) {
                $this->error(self::UNSUPPORTED);
                return false;
            }

            if (preg_match($this->patterns[$locale], $value)) {
                return true;
            }
        } else {
            foreach (array_keys($this->patterns) as $locale) {
                if (preg_match($this->patterns[$locale], $value)) {
                    return true;
                }
            }
        }

        $this->error(self::NO_MATCH);
        return false;
    }

    /**
     * @param array<string>|string|null $locale
     * @return self
     */
    public function setLocale(array|string|null $locale): self
    {
        $this->options['locale'] = $locale;

        return $this;
    }

    /**
     * @return array<string>|string|null
     */
    public function getLocale(): array|string|null
    {
        return $this->options['locale'];
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
