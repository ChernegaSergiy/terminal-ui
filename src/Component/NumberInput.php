<?php

declare(strict_types=1);

namespace ChernegaSergiy\TerminalUi\Component;

use ChernegaSergiy\TerminalUi\Terminal\Ansi;
use ChernegaSergiy\TerminalUi\Terminal\Key;
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
        ?ValidatorInterface $validator = null
    ) {
        parent::__construct($prompt);
        $this->validator = $validator;
    }

    public function handleKey(Key|string $key) : void
    {
        $this->errorMessage = null; // Clear previous error on new input

        if (Key::Enter === $key) {
            $this->errorMessage = $this->validate();
            $this->done = null === $this->errorMessage;
        } elseif (Key::Backspace === $key && strlen($this->current_value) > 0) {
            $this->current_value = substr($this->current_value, 0, -1);
            $this->done = false;
        } elseif (is_string($key) && ctype_digit($key) && strlen($this->current_value) < 3) {
            $this->current_value .= $key;
            $this->done = false;
        }
    }

    public function getResult() : int
    {
        return (int) $this->current_value;
    }

    /**
     * Whether the current value passes the validator (always true without one).
     */
    public function isValid() : bool
    {
        return null === $this->validate();
    }

    private function validate() : ?string
    {
        return $this->validator?->validate($this->current_value);
    }

    /**
     * Renders the input field.
     */
    public function render() : string
    {
        $lines = [$this->label, '', Ansi::style(Ansi::BOLD_YELLOW, "{$this->current_value}_")];

        if (null !== $this->errorMessage) {
            $lines[] = '';
            $lines[] = Ansi::style(Ansi::RED, "✗ {$this->errorMessage}");
        }

        return implode("\n", $lines);
    }
}
