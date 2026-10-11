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
    /** @var Component[] The fields in the order they are shown. */
    private array $components = [];
    /** @var string[] The name of each field, in the same order as $components. */
    private array $names = [];
    private int $focusedIndex = 0;
    private bool $submitFailed = false;

    /**
     * Form constructor.
     * @param string $title The title of the form.
     * @param array<string, Component> $fields The fields keyed by name; the name is the key of the field in getResult().
     * @throws \InvalidArgumentException If a field is not keyed by a non-empty string name.
     */
    public function __construct(string $title, array $fields = [])
    {
        parent::__construct($title);

        foreach ($fields as $name => $component) {
            if (!is_string($name)) {
                throw new \InvalidArgumentException('Form fields must be keyed by name, e.g. [\'age\' => $input]; use add() for numeric names.');
            }
            $this->add($name, $component);
        }
    }

    /**
     * Adds a field to the end of the form.
     * @param string $name The name of the field: its key in the form's result.
     * @param Component $component The component that collects the value.
     * @throws \InvalidArgumentException If the name is empty or already used by another field.
     */
    public function add(string $name, Component $component): static
    {
        if ($name === '') {
            throw new \InvalidArgumentException('A form field needs a non-empty name.');
        }
        if (in_array($name, $this->names, true)) {
            throw new \InvalidArgumentException("The form already has a field named '{$name}'.");
        }

        $this->names[] = $name;
        $this->components[] = $component;

        return $this;
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
        foreach ($this->components as $index => $component) {
            $results[$this->names[$index]] = $component->getResult();
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
