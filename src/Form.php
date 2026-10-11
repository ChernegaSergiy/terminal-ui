<?php

declare(strict_types=1);

namespace ChernegaSergiy\TerminalUi;

/**
 * A component that manages a collection of other components, allowing navigation and submission.
 */
class Form implements Component
{
    private string $title;
    /** @var Component[] */
    private array $components = [];
    private int $focusedIndex = 0;
    private bool $done = false;
    private string $id;

    /**
     * Form constructor.
     * @param string $title The title of the form.
     * @param Component[] $components An array of child components.
     * @param string|null $id Optional unique ID for the component.
     */
    public function __construct(string $title, array $components = [], ?string $id = null)
    {
        $this->title = $title;
        $this->components = $components;
        $this->id = $id ?? uniqid('form_');
    }

    public function addComponent(Component $component): void
    {
        $this->components[] = $component;
    }

    public function handleKey(string $key): void
    {
        if (empty($this->components)) {
            $this->done = true; // Nothing to do if no components
            return;
        }

        $current_component = $this->components[$this->focusedIndex];

        if ($key === 'down' || $key === 'tab') {
            // Move focus to the next component
            $this->focusedIndex = ($this->focusedIndex + 1) % count($this->components);
        } elseif ($key === 'up') {
            // Move focus to the previous component
            $this->focusedIndex = ($this->focusedIndex - 1 + count($this->components)) % count($this->components);
        } elseif ($key === 'enter') {
            $current_component->handleKey($key); // Pass Enter to the current component

            if ($current_component->isValid()) {
                // If current component is valid, try to move to the next or submit
                if ($this->focusedIndex === count($this->components) - 1) {
                    // If it's the last component, check if the whole form is valid
                    if ($this->isValid()) {
                        $this->done = true;
                    }
                } else {
                    // Move to the next component
                    $this->focusedIndex = ($this->focusedIndex + 1) % count($this->components);
                }
            }
            // If current component is not valid, focus remains on it.
        } else {
            // Pass other keys to the currently focused component
            $current_component->handleKey($key);
        }
    }

    public function isDone(): bool
    {
        return $this->done;
    }

    public function isValid(): bool
    {
        foreach ($this->components as $component) {
            if (!$component->isValid()) {
                return false;
            }
        }
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

    public function getResult(): mixed
    {
        $results = [];
        foreach ($this->components as $component) {
            $results[$component->getId()] = $component->getResult();
        }
        return $results;
    }

    public function render(): void
    {
        echo "\n  \033[1;36m{$this->title}\033[0m\n\n";

        foreach ($this->components as $index => $component) {
            // Highlight the focused component
            if ($index === $this->focusedIndex) {
                echo "  \033[1;33m▶ "; // Highlight focused component
            } else {
                echo "    ";
            }
            $component->render(); // Each component renders itself
            echo "\033[0m\n"; // Reset color and add newline after each component's render
        }

        echo "\n  \033[90mВикористовуйте ↑↓/Tab для навігації, Enter для підтвердження/переходу\033[0m\n";
        if (!$this->isValid() && $this->done) {
            echo "  \033[31m✗ Будь ласка, виправте помилки у формі.\033[0m\n";
        }
    }
}
