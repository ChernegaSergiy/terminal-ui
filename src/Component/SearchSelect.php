<?php

declare(strict_types=1);

namespace ChernegaSergiy\TerminalUi\Component;

use ChernegaSergiy\TerminalUi\Terminal\Ansi;
use ChernegaSergiy\TerminalUi\Terminal\Key;

/**
 * An interactive searchable selection component for terminal applications.
 *
 * Allows users to filter a list of options in real time by typing a search query,
 * navigate the filtered results with Up/Down arrows, select with Enter, or cancel with Esc.
 */
class SearchSelect extends AbstractComponent
{
    /** @var array<int|string, string> Available options. */
    private array $options;
    private string $query = '';
    private int $selectedIndex = 0;
    private bool $cancelled = false;
    private int $limit;
    private ?string $placeholder;
    private string $instruction;

    /**
     * @param string                    $title       The title/prompt of the searchable select.
     * @param array<int|string, string> $options     Array of options (indexed or associative).
     * @param string|null               $placeholder Placeholder for the search query when empty.
     * @param int                       $limit       Maximum number of visible items in the filtered list.
     * @param string                    $instruction Instructions displayed below the list.
     */
    public function __construct(
        string $title,
        array $options,
        ?string $placeholder = 'Type to search...',
        int $limit = 7,
        string $instruction = 'Type to filter, ↑↓ to navigate, Enter to select, ESC to cancel'
    ) {
        parent::__construct($title);
        $this->options = $options;
        $this->placeholder = $placeholder;
        $this->limit = max(1, $limit);
        $this->instruction = $instruction;
    }

    public function handleKey(Key|string $key) : void
    {
        $filtered = $this->getFilteredKeys();
        $count = count($filtered);

        if (Key::Up === $key) {
            if ($this->selectedIndex > 0) {
                $this->selectedIndex--;
            }
        } elseif (Key::Down === $key) {
            if ($this->selectedIndex < $count - 1) {
                $this->selectedIndex++;
            }
        } elseif (Key::Home === $key) {
            $this->selectedIndex = 0;
        } elseif (Key::End === $key) {
            $this->selectedIndex = max(0, $count - 1);
        } elseif (Key::Enter === $key) {
            if ($count > 0) {
                $this->cancelled = false;
                $this->done = true;
            }
        } elseif (Key::Esc === $key) {
            $this->cancelled = true;
            $this->done = true;
        } elseif (Key::Backspace === $key) {
            if (mb_strlen($this->query) > 0) {
                $this->query = mb_substr($this->query, 0, -1);
                $this->selectedIndex = 0;
            }
        } elseif (is_string($key)) {
            if ('' !== $key && ! preg_match('/[\x00-\x1f\x7f]/', $key)) {
                $this->query .= $key;
                $this->selectedIndex = 0;
            }
        }
    }

    /**
     * @return array<int, int|string> Keys of the options that match the current query.
     */
    public function getFilteredKeys() : array
    {
        if ('' === $this->query) {
            return array_keys($this->options);
        }

        $matched = [];
        foreach ($this->options as $key => $label) {
            if (false !== mb_stripos($label, $this->query) || false !== mb_stripos((string) $key, $this->query)) {
                $matched[] = $key;
            }
        }

        return $matched;
    }

    /**
     * @return int|string|null Selected option key, or null if cancelled or no options matched.
     */
    public function getResult() : int|string|null
    {
        if ($this->cancelled) {
            return null;
        }

        $filtered = $this->getFilteredKeys();
        if (empty($filtered) || ! isset($filtered[$this->selectedIndex])) {
            return null;
        }

        return $filtered[$this->selectedIndex];
    }

    public function getSelectedValue() : ?string
    {
        $key = $this->getResult();
        if (null === $key) {
            return null;
        }

        return $this->options[$key] ?? null;
    }

    public function getQuery() : string
    {
        return $this->query;
    }

    public function getSelectedIndex() : int
    {
        return $this->selectedIndex;
    }

    public function isCancelled() : bool
    {
        return $this->cancelled;
    }

    public function render() : string
    {
        $lines = [Ansi::style(Ansi::BOLD_CYAN, $this->label), ''];

        if ('' === $this->query && null !== $this->placeholder && '' !== $this->placeholder) {
            $inputLine = Ansi::style(Ansi::BOLD_CYAN, 'Search: ') . Ansi::style(Ansi::GRAY, $this->placeholder);
        } else {
            $inputLine = Ansi::style(Ansi::BOLD_CYAN, 'Search: ') . Ansi::style(Ansi::BOLD_YELLOW, $this->query . '_');
        }
        $lines[] = $inputLine;
        $lines[] = '';

        $filteredKeys = $this->getFilteredKeys();
        $totalMatches = count($filteredKeys);

        if (0 === $totalMatches) {
            $lines[] = Ansi::style(Ansi::GRAY, '  (No matches found)');
        } else {
            $windowStart = 0;
            if ($totalMatches > $this->limit) {
                $windowStart = max(0, min($this->selectedIndex - (int) floor($this->limit / 2), $totalMatches - $this->limit));
            }
            $visibleKeys = array_slice($filteredKeys, $windowStart, $this->limit);

            foreach ($visibleKeys as $i => $key) {
                $actualIndex = $windowStart + $i;
                $label = $this->options[$key] ?? '';

                if ($actualIndex === $this->selectedIndex) {
                    $lines[] = Ansi::style(Ansi::BOLD_GREEN, "▶ {$label}");
                } else {
                    $lines[] = "  {$label}";
                }
            }

            if ($totalMatches > $this->limit) {
                $lines[] = Ansi::style(Ansi::GRAY, "  (Showing {$this->limit} of {$totalMatches} matches)");
            }
        }

        $lines[] = '';
        $lines[] = Ansi::style(Ansi::GRAY, $this->instruction);

        return implode("\n", $lines);
    }
}
