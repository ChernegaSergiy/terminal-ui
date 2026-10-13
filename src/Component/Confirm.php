<?php

declare(strict_types=1);

namespace ChernegaSergiy\TerminalUi\Component;

use ChernegaSergiy\TerminalUi\Terminal\Ansi;
use ChernegaSergiy\TerminalUi\Terminal\Key;

/**
 * An interactive yes/no confirmation prompt for terminal applications.
 */
class Confirm extends AbstractComponent
{
    private bool $value;
    private string $yes;
    private string $no;
    private string $instruction;

    /**
     * @param string $label       The question/prompt to confirm.
     * @param bool   $default     The initial selection (true for yes, false for no).
     * @param string $yes         Custom label for the affirmative option.
     * @param string $no          Custom label for the negative option.
     * @param string $instruction Navigation instructions shown below the options.
     */
    public function __construct(
        string $label,
        bool $default = true,
        string $yes = 'Yes',
        string $no = 'No',
        string $instruction = 'Use ← → / y/n to select, Enter to confirm'
    ) {
        parent::__construct($label);
        $this->value = $default;
        $this->yes = $yes;
        $this->no = $no;
        $this->instruction = $instruction;
    }

    public function handleKey(Key|string $key) : void
    {
        if (Key::Left === $key || Key::Up === $key) {
            $this->value = true;
        } elseif (Key::Right === $key || Key::Down === $key) {
            $this->value = false;
        } elseif (Key::Tab === $key || ' ' === $key) {
            $this->value = ! $this->value;
        } elseif (Key::Enter === $key) {
            $this->done = true;
        } elseif (is_string($key)) {
            $lowerKey = mb_strtolower($key);
            $firstYes = mb_substr(mb_strtolower($this->yes), 0, 1);
            $firstNo = mb_substr(mb_strtolower($this->no), 0, 1);

            if ('y' === $lowerKey) {
                $this->value = true;
            } elseif ('n' === $lowerKey) {
                $this->value = false;
            } elseif ('' !== $firstYes && $lowerKey === $firstYes) {
                $this->value = true;
            } elseif ('' !== $firstNo && $lowerKey === $firstNo) {
                $this->value = false;
            }
        }
    }

    public function getResult() : bool
    {
        return $this->value;
    }

    public function getValue() : bool
    {
        return $this->value;
    }

    public function getYes() : string
    {
        return $this->yes;
    }

    public function getNo() : string
    {
        return $this->no;
    }

    public function getInstruction() : string
    {
        return $this->instruction;
    }

    public function render() : string
    {
        $yesFormatted = $this->value
            ? Ansi::style(Ansi::BOLD_GREEN, "● {$this->yes}")
            : Ansi::style(Ansi::GRAY, "○ {$this->yes}");

        $noFormatted = ! $this->value
            ? Ansi::style(Ansi::BOLD_RED, "● {$this->no}")
            : Ansi::style(Ansi::GRAY, "○ {$this->no}");

        $choices = "{$yesFormatted}  {$noFormatted}";

        $lines = [
            $this->label,
            '',
            $choices,
        ];

        if ('' !== $this->instruction) {
            $lines[] = '';
            $lines[] = Ansi::style(Ansi::GRAY, $this->instruction);
        }

        return implode("\n", $lines);
    }
}
