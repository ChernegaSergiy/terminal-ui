<?php

declare(strict_types=1);

namespace ChernegaSergiy\TerminalUi\Validator;

class CallbackValidator implements ValidatorInterface
{
    private $callback;

    public function __construct(callable $callback)
    {
        $this->callback = $callback;
    }

    public function validate(string $value): ?string
    {
        return ($this->callback)($value);
    }
}
