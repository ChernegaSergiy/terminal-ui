<?php

declare(strict_types=1);

namespace ChernegaSergiy\TerminalUI;

/**
 * Provides an interactive slider input for terminal applications.
 */
class SliderInput implements Component
{
    private string $label;
    private int $min;
    private int $max;
    private int $value;
    private int $step;
    private bool $done = false;
    private string $id;

    /**
     * SliderInput constructor.
     * @param string $label The label for the slider.
     * @param int $min The minimum value of the slider.
     * @param int $max The maximum value of the slider.
     * @param int|null $default The default value of the slider.
     * @param int $step The step increment/decrement for the slider.
     * @param string|null $id Optional unique ID for the component.
     */
    private string $changeValueInstruction;
    private string $quickInputInstruction;

    public function __construct(
        string $label,
        int $min,
        int $max,
        int $default = null,
        int $step = 1,
        ?string $id = null,
        string $changeValueInstruction = '← → для зміни значення, Enter для підтвердження',
        string $quickInputInstruction = 'Або введіть цифру для швидкого вводу'
    ) {
        $this->label = $label;
        $this->min = $min;
        $this->max = $max;
        $this->value = $default ?? $min;
        $this->step = $step;
        $this->id = $id ?? uniqid('slider_');
        $this->changeValueInstruction = $changeValueInstruction;
        $this->quickInputInstruction = $quickInputInstruction;
    }

    public function handleKey(string $key): void
    {
        if ($key === 'left' && $this->value > $this->min) {
            $this->value = max($this->min, $this->value - $this->step);
        } elseif ($key === 'right' && $this->value < $this->max) {
            $this->value = min($this->max, $this->value + $this->step);
        } elseif ($key === 'enter') {
            $this->done = true;
        } elseif (ctype_digit($key)) {
            // Quick number input
            $input = new InteractiveInput($this->label, $this->min, $this->max);
            $this->value = ComponentRunner::run($input);
            $this->done = true;
        }
    }

    public function isDone(): bool
    {
        return $this->done;
    }

    public function isValid(): bool
    {
        return true;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getResult(): mixed
    {
        return $this->value;
    }

    /**
     * Renders the slider on the terminal.
     */
    public function render(): void
    {

        echo "\n  {$this->label}\n\n";

        // Progress bar
        $bar_width = 50;
        $percentage = ($this->value - $this->min) / ($this->max - $this->min);
        $filled = (int) ($bar_width * $percentage);

        echo "  \033[36m";
        echo str_repeat("█", $filled);
        echo "\033[90m";
        echo str_repeat("░", $bar_width - $filled);
        echo "\033[0m\n\n";

        echo "  \033[1;33m{$this->value}\033[0m / {$this->max}\n\n";

        echo "  \033[90m{$this->changeValueInstruction}\033[0m\n";
        echo "  \033[90m{$this->quickInputInstruction}\033[0m\n";
    }
}
