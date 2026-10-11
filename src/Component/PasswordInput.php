<?php

declare(strict_types=1);

namespace ChernegaSergiy\TerminalUi\Component;

use ChernegaSergiy\TerminalUi\Terminal\Ansi;
use ChernegaSergiy\TerminalUi\Validator\ValidatorInterface;

/**
 * An interactive password/secret input component with masked characters.
 *
 * Inherits full cursor navigation and validation from TextInput,
 * but masks the typed text with asterisks '*' (or a custom mask character) in render().
 */
class PasswordInput extends TextInput
{
    private string $mask;

    /**
     * @param string                  $prompt      Human-readable prompt/label displayed above the input.
     * @param ValidatorInterface|null $validator   Optional validator to check input validity.
     * @param string|null             $placeholder Placeholder shown when input is empty.
     * @param int|null                $maxLength   Maximum allowed character length.
     * @param string                  $mask        Masking character (default: '*'). Empty string hides input entirely.
     */
    public function __construct(
        string $prompt,
        ?ValidatorInterface $validator = null,
        ?string $placeholder = null,
        ?int $maxLength = null,
        string $mask = '*'
    ) {
        parent::__construct($prompt, '', $validator, $placeholder, $maxLength);
        $this->mask = $mask;
    }

    public function getMask() : string
    {
        return $this->mask;
    }

    public function render() : string
    {
        $len = mb_strlen($this->value);

        if (0 === $len && null !== $this->placeholder && '' !== $this->placeholder) {
            $inputLine = Ansi::style(Ansi::GRAY, $this->placeholder);
        } elseif ($this->cursorPosition >= $len) {
            $masked = str_repeat($this->mask, $len);
            $inputLine = Ansi::style(Ansi::BOLD_YELLOW, $masked . '_');
        } else {
            $before = str_repeat($this->mask, $this->cursorPosition);
            $cursorChar = '' === $this->mask ? '_' : $this->mask;
            $after = str_repeat($this->mask, max(0, $len - $this->cursorPosition - 1));

            $inputLine = Ansi::style(Ansi::BOLD_YELLOW, $before)
                . Ansi::style('7', $cursorChar)
                . Ansi::style(Ansi::BOLD_YELLOW, $after);
        }

        $lines = [$this->label, '', $inputLine];

        if (null !== $this->errorMessage) {
            $lines[] = '';
            $lines[] = Ansi::style(Ansi::RED, "✗ {$this->errorMessage}");
        }

        return implode("\n", $lines);
    }
}
