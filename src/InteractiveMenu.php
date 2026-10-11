<?php

declare(strict_types=1);

namespace ChernegaSergiy\TerminalUI;

/**
 * Provides an interactive menu for terminal applications.
 */
class InteractiveMenu implements Component
{
    private array $items;
    private int $selected = 0;
    private string $title;
    private bool $done = false;
    private string $id;

    /**
     * InteractiveMenu constructor.
     * @param string $title The title of the menu.
     * @param array $items An array of menu items (strings).
     * @param string|null $id Optional unique ID for the component.
     */
    private string $navigationInstruction;

    public function __construct(
        string $title,
        array $items,
        ?string $id = null,
        string $navigationInstruction = 'Використовуйте ↑↓ для навігації, Enter для вибору, ESC для виходу'
    ) {
        $this->title = $title;
        $this->items = $items;
        $this->id = $id ?? uniqid('menu_');
        $this->navigationInstruction = $navigationInstruction;
    }

    public function handleKey(string $key): void
    {
        if ($key === 'up' && $this->selected > 0) {
            $this->selected--;
        } elseif ($key === 'down' && $this->selected < count($this->items) - 1) {
            $this->selected++;
        } elseif ($key === 'enter') {
            $this->done = true;
        } elseif ($key === 'esc') {
            $this->selected = count($this->items) - 1; // Exit option
            $this->done = true;
        }
    }

    public function isDone(): bool
    {
        return $this->done;
    }

    public function getResult(): int
    {
        return $this->selected;
    }

    public function isValid(): bool
    {
        return true;
    }

    public function getLabel(): string
    {
        return $this->title;
    }

    public function getId(): string
    {
        return $this->id;
    }

    /**
     * Renders the menu on the terminal.
     */
    public function render(): void
    {

        echo "\n  \033[1;36m{$this->title}\033[0m\n\n";

        foreach ($this->items as $index => $item) {
            if ($index === $this->selected) {
                echo "  \033[1;32m▶ {$item}\033[0m\n";
            } else {
                echo "    {$item}\n";
            }
        }

        echo "\n  \033[90m{$this->navigationInstruction}\033[0m\n";
    }
}
