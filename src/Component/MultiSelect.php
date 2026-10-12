<?php

declare(strict_types=1);

namespace ChernegaSergiy\TerminalUi\Component;

use ChernegaSergiy\TerminalUi\Terminal\Ansi;
use ChernegaSergiy\TerminalUi\Terminal\Key;

/**
 * An interactive multiple-selection component for terminal applications.
 *
 * Allows users to navigate through options, toggle selections with Space or 'a',
 * validate min/max limits, and confirm with Enter or cancel with Esc.
 */
class MultiSelect extends AbstractComponent
{
    /** @var array<int|string, string> The available options. */
    private array $options;
    /** @var array<int, int|string> Ordered list of option keys. */
    private array $keys;
    /** @var array<int|string, bool> Map of option key => is selected. */
    private array $selected = [];
    private int $cursor = 0;
    private bool $cancelled = false;
    private ?int $min;
    private ?int $max;
    private ?string $errorMessage = null;
    private string $instruction;

    /**
     * @param string                    $title       The title/prompt of the multi-select.
     * @param array<int|string, string> $options     Array of options (indexed or associative).
     * @param array<int, int|string>    $default     Keys/indices pre-selected by default.
     * @param int|null                  $min         Minimum required number of selected items.
     * @param int|null                  $max         Maximum allowed number of selected items.
     * @param string                    $instruction Instructions displayed below the list.
     */
    public function __construct(
        string $title,
        array $options,
        array $default = [],
        ?int $min = null,
        ?int $max = null,
        string $instruction = 'Use ↑↓ to navigate, Space to toggle, a to toggle all, Enter to confirm, ESC to cancel'
    ) {
        parent::__construct($title);
        $this->options = $options;
        $this->keys = array_keys($options);
        $this->min = $min;
        $this->max = $max;
        $this->instruction = $instruction;

        foreach ($this->keys as $key) {
            $this->selected[$key] = in_array($key, $default, true);
        }
    }

    public function handleKey(Key|string $key) : void
    {
        $this->errorMessage = null;
        $count = count($this->keys);

        if (0 === $count) {
            if (Key::Enter === $key || Key::Esc === $key) {
                $this->cancelled = Key::Esc === $key;
                $this->done = true;
            }
            return;
        }

        if (Key::Up === $key) {
            if ($this->cursor > 0) {
                $this->cursor--;
            }
        } elseif (Key::Down === $key) {
            if ($this->cursor < $count - 1) {
                $this->cursor++;
            }
        } elseif (Key::Home === $key) {
            $this->cursor = 0;
        } elseif (Key::End === $key) {
            $this->cursor = max(0, $count - 1);
        } elseif (' ' === $key) {
            if (isset($this->keys[$this->cursor])) {
                $currentKey = $this->keys[$this->cursor];
                $this->selected[$currentKey] = ! ($this->selected[$currentKey] ?? false);
            }
        } elseif ('a' === $key || 'A' === $key) {
            $hasAny = in_array(true, $this->selected, true);
            foreach ($this->keys as $k) {
                $this->selected[$k] = ! $hasAny;
            }
        } elseif (Key::Enter === $key) {
            $this->errorMessage = $this->validate();
            if (null === $this->errorMessage) {
                $this->cancelled = false;
                $this->done = true;
            }
        } elseif (Key::Esc === $key) {
            $this->cancelled = true;
            $this->done = true;
        }
    }

    public function isValid() : bool
    {
        return null === $this->validate();
    }

    private function validate() : ?string
    {
        $selectedCount = count($this->getSelectedKeys());

        if (null !== $this->min && $selectedCount < $this->min) {
            return "Please select at least {$this->min} item(s).";
        }

        if (null !== $this->max && $selectedCount > $this->max) {
            return "Please select at most {$this->max} item(s).";
        }

        return null;
    }

    /**
     * @return array<int, int|string>|null Selected option keys, or null if cancelled with ESC.
     */
    public function getResult() : ?array
    {
        if ($this->cancelled) {
            return null;
        }

        return $this->getSelectedKeys();
    }

    /**
     * @return array<int, int|string> Array of selected keys.
     */
    public function getSelectedKeys() : array
    {
        $selected = [];
        foreach ($this->keys as $key) {
            if ($this->selected[$key] ?? false) {
                $selected[] = $key;
            }
        }
        return $selected;
    }

    /**
     * @return array<int|string, string> Array of selected items (key => label).
     */
    public function getSelectedValues() : array
    {
        $values = [];
        foreach ($this->getSelectedKeys() as $key) {
            $values[$key] = $this->options[$key] ?? '';
        }
        return $values;
    }

    public function isCancelled() : bool
    {
        return $this->cancelled;
    }

    public function getCursor() : int
    {
        return $this->cursor;
    }

    public function render() : string
    {
        $lines = [Ansi::style(Ansi::BOLD_CYAN, $this->label), ''];

        foreach ($this->keys as $index => $key) {
            $label = $this->options[$key] ?? '';
            $isChecked = $this->selected[$key] ?? false;
            $box = $isChecked ? '[x]' : '[ ]';

            $lines[] = $index === $this->cursor
                ? Ansi::style(Ansi::BOLD_GREEN, "▶ {$box} {$label}")
                : "  {$box} {$label}";
        }

        $lines[] = '';
        $lines[] = Ansi::style(Ansi::GRAY, $this->instruction);

        if (null !== $this->errorMessage) {
            $lines[] = '';
            $lines[] = Ansi::style(Ansi::RED, "✗ {$this->errorMessage}");
        }

        return implode("\n", $lines);
    }
}
