<?php

declare(strict_types=1);

namespace ChernegaSergiy\TerminalUi\Component;

use ChernegaSergiy\TerminalUi\Terminal\Ansi;
use ChernegaSergiy\TerminalUi\Terminal\Key;

/**
 * Provides an interactive slider input for terminal applications.
 */
class Slider extends AbstractComponent
{
    private int $min;
    private int $max;
    private int $value;
    private int $step;
    private string $typed = '';
    private string $changeValueInstruction;
    private string $quickInputInstruction;

    /**
     * Slider constructor.
     * @param string $label The label for the slider.
     * @param int $min The minimum value of the slider.
     * @param int $max The maximum value of the slider.
     * @param int|null $default The default value of the slider (clamped to the range).
     * @param int $step The step increment/decrement for the slider.
     * @param string|null $id Optional unique ID for the component.
     * @throws \InvalidArgumentException If $min is greater than $max or $step is less than 1.
     */
    public function __construct(
        string $label,
        int $min,
        int $max,
        ?int $default = null,
        int $step = 1,
        ?string $id = null,
        string $changeValueInstruction = '← → to change the value, Enter to confirm',
        string $quickInputInstruction = 'Or type a digit for quick input'
    ) {
        if ($min > $max) {
            throw new \InvalidArgumentException("Slider minimum ({$min}) must not exceed its maximum ({$max}).");
        }
        if ($step < 1) {
            throw new \InvalidArgumentException("Slider step must be at least 1, {$step} given.");
        }

        parent::__construct($label, $id, 'slider_');
        $this->min = $min;
        $this->max = $max;
        $this->value = $this->clamp($default ?? $min);
        $this->step = $step;
        $this->changeValueInstruction = $changeValueInstruction;
        $this->quickInputInstruction = $quickInputInstruction;
    }

    public function handleKey(Key|string $key): void
    {
        if ($key === Key::Left || $key === Key::Right) {
            $this->typed = '';
            $this->value = $key === Key::Left
                ? max($this->min, $this->value - $this->step)
                : min($this->max, $this->value + $this->step);
        } elseif ($key === Key::Enter) {
            $this->done = true;
        } elseif (is_string($key) && ctype_digit($key)) {
            // Quick number input: digits are collected in place and clamped
            // to the slider's range, Enter confirms as usual.
            $this->typed = substr($this->typed . $key, 0, strlen((string) $this->max));
            $this->value = $this->clamp((int) $this->typed);
        } elseif ($key === Key::Backspace && $this->typed !== '') {
            $this->typed = substr($this->typed, 0, -1);
            if ($this->typed !== '') {
                $this->value = $this->clamp((int) $this->typed);
            }
        }
    }

    private function clamp(int $value): int
    {
        return min($this->max, max($this->min, $value));
    }

    public function getResult(): mixed
    {
        return $this->value;
    }

    /**
     * Renders the slider.
     */
    public function render(): string
    {
        // Progress bar
        $bar_width = 50;
        $range = $this->max - $this->min;
        $percentage = $range > 0 ? ($this->value - $this->min) / $range : 1.0;
        $filled = (int) ($bar_width * $percentage);

        $bar = Ansi::style(Ansi::CYAN, str_repeat("█", $filled))
            . Ansi::style(Ansi::GRAY, str_repeat("░", $bar_width - $filled));

        return implode("\n", [
            $this->label,
            '',
            $bar,
            '',
            Ansi::style(Ansi::BOLD_YELLOW, (string) $this->value) . " / {$this->max}",
            '',
            Ansi::style(Ansi::GRAY, $this->changeValueInstruction),
            Ansi::style(Ansi::GRAY, $this->quickInputInstruction),
        ]);
    }
}
