<?php

declare(strict_types=1);

namespace ChernegaSergiy\TerminalUI\Validator;

interface ValidatorInterface
{
    /**
     * Validates the given value.
     * @param string $value The value to validate.
     * @return string|null Returns an error message if validation fails, or null if valid.
     */
    public function validate(string $value): ?string;
}
