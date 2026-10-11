<?php

declare(strict_types=1);

namespace ChernegaSergiy\TerminalUI;

/**
 * Provides an interactive checkbox component for terminal applications.
 */
class Checkbox implements Component
{
    private string $label;
    private bool $checked;
    private bool $done = false;
    private string $id;

    /**
     * Checkbox constructor.
     * @param string $label The label for the checkbox.
     * @param bool $default_checked The initial state of the checkbox.
     * @param string|null $id Optional unique ID for the component.
     */
    private string $instruction;

    public function __construct(
        string $label,
        bool $default_checked = false,
        ?string $id = null,
        string $instruction = 'Натисніть Пробіл для перемикання, Enter для підтвердження'
    ) {
        $this->label = $label;
        $this->checked = $default_checked;
        $this->id = $id ?? uniqid('checkbox_');
        $this->instruction = $instruction;
    }

    public function handleKey(string $key): void
    {
        if ($key === ' ') {
            $this->checked = !$this->checked;
        } elseif ($key === 'enter') {
            $this->done = true;
        }
    }

    public function isDone(): bool
    {
        return $this->done;
    }

    public function getResult(): bool
    {
        return $this->checked;
    }

    public function isValid(): bool
    {
        return true;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function render(): void
    {

        $checkbox_char = $this->checked ? '[x]' : '[ ]';

        echo "\n  {$checkbox_char} {$this->label}\n\n";
        echo "  \033[90m{$this->instruction}\033[0m\n";
    }
}
