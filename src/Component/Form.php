<?php

declare(strict_types=1);

namespace ChernegaSergiy\TerminalUi\Component;

use ChernegaSergiy\TerminalUi\Terminal\Ansi;
use ChernegaSergiy\TerminalUi\Terminal\Key;

/**
 * A component that manages a collection of other components, allowing navigation and submission.
 */
class Form extends AbstractComponent
{
    /** @var Component[] */
    private array $components = [];
    private int $focusedIndex = 0;
    private bool $submitFailed = false;

    /**
     * Form constructor.
     * @param string $title The title of the form.
     * @param Component[] $components An array of child components.
     * @param string|null $id Optional unique ID for the component.
     */
    public function __construct(string $title, array $components = [], ?string $id = null)
    {
        parent::__construct($title, $id, 'form_');
        $this->components = $components;
    }

    public function addComponent(Component $component): void
    {
        $this->components[] = $component;
    }

    public function handleKey(Key|string $key): void
    {
        $this->submitFailed = false;

        if (empty($this->components)) {
            $this->done = true; // Nothing to do if no components
            return;
        }

        $current_component = $this->components[$this->focusedIndex];

        if ($key === Key::Down || $key === Key::Tab) {
            // Move focus to the next component
            $this->focusedIndex = ($this->focusedIndex + 1) % count($this->components);
        } elseif ($key === Key::Up) {
            // Move focus to the previous component
            $this->focusedIndex = ($this->focusedIndex - 1 + count($this->components)) % count($this->components);
        } elseif ($key === Key::Enter) {
            $current_component->handleKey($key); // Pass Enter to the current component

            if ($current_component->isValid()) {
                // If current component is valid, try to move to the next or submit
                if ($this->focusedIndex === count($this->components) - 1) {
                    // If it's the last component, check if the whole form is valid
                    if ($this->isValid()) {
                        $this->done = true;
                    } else {
                        // Do not submit: send the user to the first field that needs fixing.
                        $this->submitFailed = true;
                        $this->focusFirstInvalid();
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

    private function focusFirstInvalid(): void
    {
        foreach ($this->components as $index => $component) {
            if (!$component->isValid()) {
                $this->focusedIndex = $index;
                return;
            }
        }
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

    public function getResult(): mixed
    {
        $results = [];
        foreach ($this->components as $component) {
            $results[$component->getId()] = $component->getResult();
        }
        return $results;
    }

    public function render(): string
    {
        $lines = [Ansi::style(Ansi::BOLD_CYAN, $this->label), ''];

        foreach ($this->components as $index => $component) {
            // The marker sits on the first line of the focused field; the rest is aligned under it.
            $marker = $index === $this->focusedIndex ? Ansi::style(Ansi::BOLD_YELLOW, '▶') . ' ' : '  ';

            foreach (explode("\n", $component->render()) as $i => $line) {
                $lines[] = $line === '' ? '' : ($i === 0 ? $marker : '  ') . $line;
            }
            $lines[] = '';
        }

        $lines[] = Ansi::style(Ansi::GRAY, 'Use ↑↓/Tab to navigate, Enter to confirm and move on');
        if ($this->submitFailed) {
            $lines[] = Ansi::style(Ansi::RED, '✗ Please fix the errors in the form.');
        }

        return implode("\n", $lines);
    }
}
