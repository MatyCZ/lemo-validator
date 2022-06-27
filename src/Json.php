<?php

namespace Lemo\Validator;

use Laminas\Validator\AbstractValidator;

use function json_decode;
use function json_last_error;
use function json_last_error_msg;

use const JSON_ERROR_NONE;

class Json extends AbstractValidator
{
    public const INVALID = 'jsonInvalid';

    /**
     * @var array<string|string>
     */
    protected array $messageTemplates = [
        self::INVALID => "Json is invalid: %reason%",
    ];

    /**
     * @var array<string|string>
     */
    protected array $messageVariables = [
        'reason' => 'reason',
    ];

    protected ?string $reason = null;

    /**
     * @param mixed $value
     * @return bool
     */
    public function isValid($value): bool
    {
        if (empty($value)) {
            return true;
        }

        if (!is_scalar($value)) {
            return false;
        }

        $value = (string) $value;

        json_decode($value);
        if (JSON_ERROR_NONE !== json_last_error()) {
            $this->error(self::INVALID);

            $errorMessage = json_last_error_msg();

            if (!empty($errorMessage)) {
                $this->reason = $errorMessage;
            } else {
                $this->reason = 'An Unexpected Error Occurred';
            }

            return false;
        }

        return true;
    }
}