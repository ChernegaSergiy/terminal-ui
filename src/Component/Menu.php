<?php

declare(strict_types=1);

namespace ChernegaSergiy\TerminalUi\Component;

use ChernegaSergiy\TerminalUi\Terminal\Ansi;
use ChernegaSergiy\TerminalUi\Terminal\Key;

/**
 * Provides an interactive menu for terminal applications.
 */
class Menu extends AbstractComponent
{
    private array $items;
    private int $selected = 0;
    private bool $cancelled = false;
    private string $navigationInstruction;

    /**
     * Menu constructor.
     * @param string $title The title of the menu.
     * @param array $items An array of menu items (strings).
     */
    public function __construct(
        string $title,
        array $items,
        string $navigationInstruction = 'Use ↑↓ to navigate, Enter to select, ESC to cancel'
    ) {
        parent::__construct($title);
        $this->items = $items;
        $this->navigationInstruction = $navigationInstruction;
    }

    public function handleKey(Key|string $key): void
    {
        if ($key === Key::Up && $this->selected > 0) {
            $this->selected--;
        } elseif ($key === Key::Down && $this->selected < count($this->items) - 1) {
            $this->selected++;
        } elseif ($key === Key::Home) {
            $this->selected = 0;
        } elseif ($key === Key::End) {
            $this->selected = max(0, count($this->items) - 1);
        } elseif ($key === Key::Enter) {
            $this->cancelled = false;
            $this->done = true;
        } elseif ($key === Key::Esc) {
            $this->cancelled = true;
            $this->done = true;
        }
    }

    /**
     * @return int|null The index of the chosen item, or null if the menu was cancelled with ESC.
     */
    public function getResult(): ?int
    {
        return $this->cancelled ? null : $this->selected;
    }

    /**
     * Renders the menu.
     */
    public function render(): string
    {
        $lines = [Ansi::style(Ansi::BOLD_CYAN, $this->label), ''];

        foreach ($this->items as $index => $item) {
            $lines[] = $index === $this->selected
                ? Ansi::style(Ansi::BOLD_GREEN, "▶ {$item}")
                : "  {$item}";
        }

        $lines[] = '';
        $lines[] = Ansi::style(Ansi::GRAY, $this->navigationInstruction);

        return implode("\n", $lines);
    }
}
