<?php

declare(strict_types=1);

namespace ChernegaSergiy\TerminalUi\Component;

/**
 * Provides an interactive checkbox component for terminal applications.
 */
class Checkbox extends AbstractComponent
{
    private bool $checked;
    private string $instruction;

    /**
     * Checkbox constructor.
     * @param string $label The label for the checkbox.
     * @param bool $default_checked The initial state of the checkbox.
     * @param string|null $id Optional unique ID for the component.
     */
    public function __construct(
        string $label,
        bool $default_checked = false,
        ?string $id = null,
        string $instruction = 'Press Space to toggle, Enter to confirm'
    ) {
        parent::__construct($label, $id, 'checkbox_');
        $this->checked = $default_checked;
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

    public function getResult(): bool
    {
        return $this->checked;
    }

    public function render(): void
    {

        $checkbox_char = $this->checked ? '[x]' : '[ ]';

        echo "\n  {$checkbox_char} {$this->label}\n\n";
        echo "  \033[90m{$this->instruction}\033[0m\n";
    }
}
