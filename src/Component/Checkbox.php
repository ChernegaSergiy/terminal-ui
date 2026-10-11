<?php

declare(strict_types=1);

namespace ChernegaSergiy\TerminalUi\Component;

use ChernegaSergiy\TerminalUi\Terminal\Ansi;
use ChernegaSergiy\TerminalUi\Terminal\Key;

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
     */
    public function __construct(
        string $label,
        bool $default_checked = false,
        string $instruction = 'Press Space to toggle, Enter to confirm'
    ) {
        parent::__construct($label);
        $this->checked = $default_checked;
        $this->instruction = $instruction;
    }

    public function handleKey(Key|string $key): void
    {
        if ($key === ' ') {
            $this->checked = !$this->checked;
        } elseif ($key === Key::Enter) {
            $this->done = true;
        }
    }

    public function getResult(): bool
    {
        return $this->checked;
    }

    public function render(): string
    {
        $checkbox_char = $this->checked ? '[x]' : '[ ]';

        return implode("\n", [
            "{$checkbox_char} {$this->label}",
            '',
            Ansi::style(Ansi::GRAY, $this->instruction),
        ]);
    }
}
