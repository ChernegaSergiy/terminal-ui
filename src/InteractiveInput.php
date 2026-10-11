<?php

declare(strict_types=1);

namespace ChernegaSergiy\TerminalUi;

use ChernegaSergiy\TerminalUi\Validator\ValidatorInterface;

/**
 * Provides an interactive input field for terminal applications.
 */
class InteractiveInput implements Component
{
    private string $prompt;
    private string $current_value = '';
    private bool $done = false;
    private string $id;
    private ?string $errorMessage = null;
    private ?ValidatorInterface $validator = null;

    public function __construct(
        string $prompt,
        ?string $id = null,
        ?ValidatorInterface $validator = null
    ) {
        $this->prompt = $prompt;
        $this->id = $id ?? uniqid('input_');
        $this->validator = $validator;
    }

    public function handleKey(string $key): void
    {
        $this->errorMessage = null; // Clear previous error on new input

        if ($key === 'enter') {
            if ($this->validator) {
                $error = $this->validator->validate($this->current_value);
                if ($error !== null) {
                    $this->errorMessage = $error;
                } else {
                    $this->done = true;
                }
            } else {
                $this->done = true;
            }
        } elseif ($key === 'backspace' && strlen($this->current_value) > 0) {
            $this->current_value = substr($this->current_value, 0, -1);
        } elseif (ctype_digit($key) && strlen($this->current_value) < 3) {
            $this->current_value .= $key;
        }
    }

    public function isDone(): bool
    {
        return $this->done;
    }

    public function getResult(): int
    {
        return (int) $this->current_value;
    }

    public function isValid(): bool
    {
        return true;
    }

    public function getLabel(): string
    {
        return $this->prompt;
    }

    public function getId(): string
    {
        return $this->id;
    }

    /**
     * Renders the interactive input field on the terminal.
     */
    public function render(): void
    {
        echo "\n  {$this->prompt}\n\n";
        echo "  \033[1;33m{$this->current_value}_\033[0m\n\n";

        if ($this->errorMessage !== null) {
            echo "  \033[31m✗ {$this->errorMessage}\033[0m\n";
        }
    }
}
