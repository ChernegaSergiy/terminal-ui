<?php

declare(strict_types=1);

namespace ChernegaSergiy\TerminalUi\Component;

use ChernegaSergiy\TerminalUi\Terminal\Ansi;
use ChernegaSergiy\TerminalUi\Terminal\Key;
use ChernegaSergiy\TerminalUi\Validator\ValidatorInterface;

/**
 * An interactive text input component for terminal applications.
 *
 * Supports cursor movement (Left/Right, Home/End), Backspace, Delete,
 * multi-byte UTF-8 characters, optional maxLength, placeholder, and external validation.
 */
class TextInput extends AbstractComponent
{
    protected string $value;
    protected int $cursorPosition;
    protected ?string $errorMessage = null;
    protected ?ValidatorInterface $validator;
    protected ?string $placeholder;
    protected ?int $maxLength;

    /**
     * @param string                  $prompt      Human-readable prompt/label displayed above the input.
     * @param string                  $default     Initial text value (defaults to empty string).
     * @param ValidatorInterface|null $validator   Optional validator to check input validity.
     * @param string|null             $placeholder Placeholder shown when input is empty.
     * @param int|null                $maxLength   Maximum allowed character length.
     */
    public function __construct(
        string $prompt,
        string $default = '',
        ?ValidatorInterface $validator = null,
        ?string $placeholder = null,
        ?int $maxLength = null
    ) {
        parent::__construct($prompt);
        $this->value = $default;
        $this->cursorPosition = mb_strlen($default);
        $this->validator = $validator;
        $this->placeholder = $placeholder;
        $this->maxLength = $maxLength;
    }

    public function handleKey(Key|string $key) : void
    {
        $this->errorMessage = null; // Clear previous error on key press

        if (Key::Enter === $key) {
            $this->errorMessage = $this->validate();
            $this->done = null === $this->errorMessage;
        } elseif (Key::Left === $key) {
            $this->cursorPosition = max(0, $this->cursorPosition - 1);
            $this->done = false;
        } elseif (Key::Right === $key) {
            $this->cursorPosition = min(mb_strlen($this->value), $this->cursorPosition + 1);
            $this->done = false;
        } elseif (Key::Home === $key) {
            $this->cursorPosition = 0;
            $this->done = false;
        } elseif (Key::End === $key) {
            $this->cursorPosition = mb_strlen($this->value);
            $this->done = false;
        } elseif (Key::Backspace === $key) {
            if ($this->cursorPosition > 0) {
                $before = mb_substr($this->value, 0, $this->cursorPosition - 1);
                $after = mb_substr($this->value, $this->cursorPosition);
                $this->value = $before . $after;
                $this->cursorPosition--;
            }
            $this->done = false;
        } elseif (Key::Delete === $key) {
            $len = mb_strlen($this->value);
            if ($this->cursorPosition < $len) {
                $before = mb_substr($this->value, 0, $this->cursorPosition);
                $after = mb_substr($this->value, $this->cursorPosition + 1);
                $this->value = $before . $after;
            }
            $this->done = false;
        } elseif (is_string($key)) {
            if ('' !== $key && ! preg_match('/[\x00-\x1f\x7f]/', $key)) {
                $keyLen = mb_strlen($key);
                if (null === $this->maxLength || (mb_strlen($this->value) + $keyLen <= $this->maxLength)) {
                    $before = mb_substr($this->value, 0, $this->cursorPosition);
                    $after = mb_substr($this->value, $this->cursorPosition);
                    $this->value = $before . $key . $after;
                    $this->cursorPosition += $keyLen;
                }
            }
            $this->done = false;
        }
    }

    public function getResult() : string
    {
        return $this->value;
    }

    public function getValue() : string
    {
        return $this->value;
    }

    public function getCursorPosition() : int
    {
        return $this->cursorPosition;
    }

    public function isValid() : bool
    {
        return null === $this->validate();
    }

    private function validate() : ?string
    {
        return $this->validator?->validate($this->value);
    }

    public function render() : string
    {
        $len = mb_strlen($this->value);

        if (0 === $len && null !== $this->placeholder && '' !== $this->placeholder) {
            $inputLine = Ansi::style(Ansi::GRAY, $this->placeholder);
        } elseif ($this->cursorPosition >= $len) {
            $inputLine = Ansi::style(Ansi::BOLD_YELLOW, $this->value . '_');
        } else {
            $before = mb_substr($this->value, 0, $this->cursorPosition);
            $cursorChar = mb_substr($this->value, $this->cursorPosition, 1);
            $after = mb_substr($this->value, $this->cursorPosition + 1);

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
