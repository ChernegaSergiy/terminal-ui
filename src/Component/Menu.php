<?php

declare(strict_types=1);

namespace ChernegaSergiy\TerminalUi\Component;

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
     * @param string|null $id Optional unique ID for the component.
     */
    public function __construct(
        string $title,
        array $items,
        ?string $id = null,
        string $navigationInstruction = 'Use ↑↓ to navigate, Enter to select, ESC to cancel'
    ) {
        parent::__construct($title, $id, 'menu_');
        $this->items = $items;
        $this->navigationInstruction = $navigationInstruction;
    }

    public function handleKey(string $key): void
    {
        if ($key === 'up' && $this->selected > 0) {
            $this->selected--;
        } elseif ($key === 'down' && $this->selected < count($this->items) - 1) {
            $this->selected++;
        } elseif ($key === 'enter') {
            $this->cancelled = false;
            $this->done = true;
        } elseif ($key === 'esc') {
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
        $out = "\n  \033[1;36m{$this->label}\033[0m\n\n";

        foreach ($this->items as $index => $item) {
            if ($index === $this->selected) {
                $out .= "  \033[1;32m▶ {$item}\033[0m\n";
            } else {
                $out .= "    {$item}\n";
            }
        }

        return $out . "\n  \033[90m{$this->navigationInstruction}\033[0m\n";
    }
}
