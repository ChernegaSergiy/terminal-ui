<?php

declare(strict_types=1);

namespace ChernegaSergiy\TerminalUi\Component;

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

    public function handleKey(string $key): void
    {
        if ($key === 'left' || $key === 'right') {
            $this->typed = '';
            $this->value = $key === 'left'
                ? max($this->min, $this->value - $this->step)
                : min($this->max, $this->value + $this->step);
        } elseif ($key === 'enter') {
            $this->done = true;
        } elseif (ctype_digit($key)) {
            // Quick number input: digits are collected in place and clamped
            // to the slider's range, Enter confirms as usual.
            $this->typed = substr($this->typed . $key, 0, strlen((string) $this->max));
            $this->value = $this->clamp((int) $this->typed);
        } elseif ($key === 'backspace' && $this->typed !== '') {
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

        return implode("\n", [
            $this->label,
            '',
            "\033[36m" . str_repeat("█", $filled) . "\033[90m" . str_repeat("░", $bar_width - $filled) . "\033[0m",
            '',
            "\033[1;33m{$this->value}\033[0m / {$this->max}",
            '',
            "\033[90m{$this->changeValueInstruction}\033[0m",
            "\033[90m{$this->quickInputInstruction}\033[0m",
        ]);
    }
}
