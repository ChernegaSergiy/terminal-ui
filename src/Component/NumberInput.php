<?php

declare(strict_types=1);

namespace ChernegaSergiy\TerminalUi\Component;

use ChernegaSergiy\TerminalUi\Validator\ValidatorInterface;

/**
 * Provides an interactive numeric input field (digits only, up to 3 characters) for terminal applications.
 */
class NumberInput extends AbstractComponent
{
    private string $current_value = '';
    private ?string $errorMessage = null;
    private ?ValidatorInterface $validator = null;

    public function __construct(
        string $prompt,
        ?string $id = null,
        ?ValidatorInterface $validator = null
    ) {
        parent::__construct($prompt, $id, 'input_');
        $this->validator = $validator;
    }

    public function handleKey(string $key): void
    {
        $this->errorMessage = null; // Clear previous error on new input

        if ($key === 'enter') {
            $this->errorMessage = $this->validate();
            $this->done = $this->errorMessage === null;
        } elseif ($key === 'backspace' && strlen($this->current_value) > 0) {
            $this->current_value = substr($this->current_value, 0, -1);
            $this->done = false;
        } elseif (ctype_digit($key) && strlen($this->current_value) < 3) {
            $this->current_value .= $key;
            $this->done = false;
        }
    }

    public function getResult(): int
    {
        return (int) $this->current_value;
    }

    /**
     * Whether the current value passes the validator (always true without one).
     */
    public function isValid(): bool
    {
        return $this->validate() === null;
    }

    private function validate(): ?string
    {
        return $this->validator?->validate($this->current_value);
    }

    /**
     * Renders the input field.
     */
    public function render(): string
    {
        $out = "\n  {$this->label}\n\n"
            . "  \033[1;33m{$this->current_value}_\033[0m\n\n";

        if ($this->errorMessage !== null) {
            $out .= "  \033[31m✗ {$this->errorMessage}\033[0m\n";
        }

        return $out;
    }
}
