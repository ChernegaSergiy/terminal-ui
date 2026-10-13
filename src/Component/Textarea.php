<?php

declare(strict_types=1);

namespace ChernegaSergiy\TerminalUi\Component;

use ChernegaSergiy\TerminalUi\Terminal\Ansi;
use ChernegaSergiy\TerminalUi\Terminal\Key;
use ChernegaSergiy\TerminalUi\Validator\ValidatorInterface;

/**
 * An interactive multi-line text input (textarea) component for terminal applications.
 *
 * Supports multi-line editing, cursor movement across lines (Up/Down, Left/Right, Home/End, PageUp/PageDown),
 * multi-byte UTF-8 characters, vertical viewport scrolling, placeholder, maxLength, and external validation.
 */
class Textarea extends AbstractComponent
{
    protected string $value;
    protected int $cursorPosition;
    protected ?string $errorMessage = null;
    protected ?ValidatorInterface $validator;
    protected ?string $placeholder;
    protected ?int $maxLength;
    protected int $rows;
    protected int $cols;
    protected string $instruction;
    protected int $scrollOffset = 0;

    /**
     * @param  string                    $prompt      Human-readable prompt/label displayed above the textarea.
     * @param  string                    $default     Initial text value (defaults to empty string).
     * @param  ValidatorInterface|null   $validator   Optional validator to check input validity.
     * @param  string|null               $placeholder Placeholder shown when input is empty.
     * @param  int|null                  $maxLength   Maximum allowed character length.
     * @param  int                       $rows        Number of visible text rows inside the box (minimum 1, defaults to 5).
     * @param  int                       $cols        Inner width of visible text rows (minimum 10, defaults to 50).
     * @param  string                    $instruction Instruction hint displayed below the textarea.
     * @throws \InvalidArgumentException If rows < 1, cols < 10, or maxLength < 1.
     */
    public function __construct(
        string $prompt,
        string $default = '',
        ?ValidatorInterface $validator = null,
        ?string $placeholder = null,
        ?int $maxLength = null,
        int $rows = 5,
        int $cols = 50,
        string $instruction = 'Enter for new line, Esc to submit'
    ) {
        if ($rows < 1) {
            throw new \InvalidArgumentException("Textarea rows must be at least 1, {$rows} given.");
        }
        if ($cols < 10) {
            throw new \InvalidArgumentException("Textarea cols must be at least 10, {$cols} given.");
        }
        if (null !== $maxLength && $maxLength < 1) {
            throw new \InvalidArgumentException("Textarea maxLength must be at least 1, {$maxLength} given.");
        }

        parent::__construct($prompt);
        $this->value = $default;
        $this->cursorPosition = mb_strlen($default);
        $this->validator = $validator;
        $this->placeholder = $placeholder;
        $this->maxLength = $maxLength;
        $this->rows = $rows;
        $this->cols = $cols;
        $this->instruction = $instruction;

        $this->adjustScrollOffset();
    }

    public function handleKey(Key|string $key) : void
    {
        $this->errorMessage = null; // Clear previous error on key press

        if (Key::Esc === $key || "\x04" === $key) {
            $this->errorMessage = $this->validate();
            $this->done = null === $this->errorMessage;
        } elseif (Key::Enter === $key) {
            if (null === $this->maxLength || mb_strlen($this->value) + 1 <= $this->maxLength) {
                $before = mb_substr($this->value, 0, $this->cursorPosition);
                $after = mb_substr($this->value, $this->cursorPosition);
                $this->value = $before . "\n" . $after;
                $this->cursorPosition++;
                $this->adjustScrollOffset();
            }
            $this->done = false;
        } elseif (Key::Up === $key) {
            [$row, $col] = $this->getCursorRowAndCol();
            if ($row > 0) {
                $this->cursorPosition = $this->getOffsetFromRowAndCol($row - 1, $col);
                $this->adjustScrollOffset();
            }
            $this->done = false;
        } elseif (Key::Down === $key) {
            [$row, $col] = $this->getCursorRowAndCol();
            $lines = explode("\n", $this->value);
            if ($row < count($lines) - 1) {
                $this->cursorPosition = $this->getOffsetFromRowAndCol($row + 1, $col);
                $this->adjustScrollOffset();
            }
            $this->done = false;
        } elseif (Key::PageUp === $key) {
            [$row, $col] = $this->getCursorRowAndCol();
            $targetRow = max(0, $row - $this->rows);
            $this->cursorPosition = $this->getOffsetFromRowAndCol($targetRow, $col);
            $this->adjustScrollOffset();
            $this->done = false;
        } elseif (Key::PageDown === $key) {
            [$row, $col] = $this->getCursorRowAndCol();
            $lines = explode("\n", $this->value);
            $targetRow = min(count($lines) - 1, $row + $this->rows);
            $this->cursorPosition = $this->getOffsetFromRowAndCol($targetRow, $col);
            $this->adjustScrollOffset();
            $this->done = false;
        } elseif (Key::Left === $key) {
            $this->cursorPosition = max(0, $this->cursorPosition - 1);
            $this->adjustScrollOffset();
            $this->done = false;
        } elseif (Key::Right === $key) {
            $this->cursorPosition = min(mb_strlen($this->value), $this->cursorPosition + 1);
            $this->adjustScrollOffset();
            $this->done = false;
        } elseif (Key::Home === $key) {
            [$row] = $this->getCursorRowAndCol();
            $this->cursorPosition = $this->getOffsetFromRowAndCol($row, 0);
            $this->done = false;
        } elseif (Key::End === $key) {
            [$row] = $this->getCursorRowAndCol();
            $lines = explode("\n", $this->value);
            $this->cursorPosition = $this->getOffsetFromRowAndCol($row, mb_strlen($lines[$row] ?? ''));
            $this->done = false;
        } elseif (Key::Backspace === $key) {
            if ($this->cursorPosition > 0) {
                $before = mb_substr($this->value, 0, $this->cursorPosition - 1);
                $after = mb_substr($this->value, $this->cursorPosition);
                $this->value = $before . $after;
                $this->cursorPosition--;
                $this->adjustScrollOffset();
            }
            $this->done = false;
        } elseif (Key::Delete === $key) {
            $len = mb_strlen($this->value);
            if ($this->cursorPosition < $len) {
                $before = mb_substr($this->value, 0, $this->cursorPosition);
                $after = mb_substr($this->value, $this->cursorPosition + 1);
                $this->value = $before . $after;
                $this->adjustScrollOffset();
            }
            $this->done = false;
        } elseif (is_string($key)) {
            if ('' !== $key && ! preg_match('/[\x00-\x1f\x7f]/', $key)) {
                $keyLen = mb_strlen($key);
                if (null === $this->maxLength || (mb_strlen($this->value) + $keyLen <= $this->maxLength)) {
                    $before = mb_substr($this->value, 0, $this->cursorPosition);
                    $after = mb_substr($this->value, $this->cursorPosition);
                    $this->value = $before . $key . $after;
                    $this->cursorPosition += $keyLen;
                    $this->adjustScrollOffset();
                }
            }
            $this->done = false;
        }
    }

    public function getResult() : string
    {
        return $this->value;
    }

    public function getValue() : string
    {
        return $this->value;
    }

    public function getCursorPosition() : int
    {
        return $this->cursorPosition;
    }

    public function getRows() : int
    {
        return $this->rows;
    }

    public function getCols() : int
    {
        return $this->cols;
    }

    public function getErrorMessage() : ?string
    {
        return $this->errorMessage;
    }

    /**
     * Returns the 0-based [row, col] position of the cursor within the text lines.
     *
     * @return array{0: int, 1: int}
     */
    public function getCursorRowAndCol() : array
    {
        $before = mb_substr($this->value, 0, $this->cursorPosition);
        $lines = explode("\n", $before);
        $row = count($lines) - 1;
        $col = mb_strlen(end($lines));

        return [$row, $col];
    }

    public function getLineCount() : int
    {
        return count(explode("\n", $this->value));
    }

    public function isValid() : bool
    {
        return null === $this->validate();
    }

    private function validate() : ?string
    {
        return $this->validator?->validate($this->value);
    }

    /**
     * Converts a 0-based [row, col] position into a 1D character offset.
     */
    private function getOffsetFromRowAndCol(int $row, int $col) : int
    {
        $lines = explode("\n", $this->value);
        $totalLines = count($lines);
        $clampedRow = max(0, min($totalLines - 1, $row));
        $lineLen = mb_strlen($lines[$clampedRow] ?? '');
        $clampedCol = max(0, min($lineLen, $col));

        $offset = 0;
        for ($i = 0; $i < $clampedRow; $i++) {
            $offset += mb_strlen($lines[$i] ?? '') + 1; // +1 for the newline delimiter
        }

        return $offset + $clampedCol;
    }

    /**
     * Ensures the current cursor row is within the visible vertical window.
     */
    private function adjustScrollOffset() : void
    {
        [$cursorRow] = $this->getCursorRowAndCol();
        if ($cursorRow < $this->scrollOffset) {
            $this->scrollOffset = $cursorRow;
        } elseif ($cursorRow >= $this->scrollOffset + $this->rows) {
            $this->scrollOffset = $cursorRow - $this->rows + 1;
        }
    }

    public function render() : string
    {
        $allLines = explode("\n", $this->value);
        $totalLines = count($allLines);
        [$cursorRow, $cursorCol] = $this->getCursorRowAndCol();
        $isPlaceholder = 0 === mb_strlen($this->value) && null !== $this->placeholder && '' !== $this->placeholder;

        // Top border with scroll indicator if scrolled down
        if ($this->scrollOffset > 0) {
            $indicator = " ↑ ({$this->scrollOffset} more) ";
            $dashes = ($this->cols + 2) - mb_strlen($indicator);
            $left = (int) floor($dashes / 2);
            $right = $dashes - $left;
            $topBorder = '┌' . str_repeat('─', max(0, $left)) . Ansi::style(Ansi::GRAY, $indicator) . str_repeat('─', max(0, $right)) . '┐';
        } else {
            $topBorder = '┌' . str_repeat('─', $this->cols + 2) . '┐';
        }

        // Bottom border with scroll indicator if lines remain below
        $linesBelow = max(0, $totalLines - ($this->scrollOffset + $this->rows));
        if ($linesBelow > 0) {
            $indicator = " ↓ ({$linesBelow} more) ";
            $dashes = ($this->cols + 2) - mb_strlen($indicator);
            $left = (int) floor($dashes / 2);
            $right = $dashes - $left;
            $bottomBorder = '└' . str_repeat('─', max(0, $left)) . Ansi::style(Ansi::GRAY, $indicator) . str_repeat('─', max(0, $right)) . '┘';
        } else {
            $bottomBorder = '└' . str_repeat('─', $this->cols + 2) . '┘';
        }

        $middleRows = [];
        for ($r = 0; $r < $this->rows; $r++) {
            $lineIndex = $this->scrollOffset + $r;
            $lineExists = isset($allLines[$lineIndex]);
            $lineText = $allLines[$lineIndex] ?? '';
            $isCursorLine = ($lineIndex === $cursorRow);

            if ($isPlaceholder && 0 === $r) {
                $slice = mb_substr((string) $this->placeholder, 0, $this->cols);
                $rendered = Ansi::style(Ansi::GRAY, $slice);
                $padding = str_repeat(' ', max(0, $this->cols - mb_strwidth($slice)));
                $middleRows[] = '│ ' . $rendered . $padding . ' │';
                continue;
            }

            if ($isCursorLine) {
                $lineLen = mb_strlen($lineText);
                $hOffset = ($cursorCol >= $this->cols) ? ($cursorCol - $this->cols + 1) : 0;
                $visibleSlice = mb_substr($lineText, $hOffset, $this->cols);
                $relCursor = $cursorCol - $hOffset;

                if ($cursorCol >= $lineLen) {
                    $rendered = Ansi::style(Ansi::BOLD_YELLOW, $visibleSlice . '_');
                    $padding = str_repeat(' ', max(0, $this->cols - (mb_strwidth($visibleSlice) + 1)));
                } else {
                    $before = mb_substr($visibleSlice, 0, $relCursor);
                    $cursorChar = mb_substr($visibleSlice, $relCursor, 1);
                    $after = mb_substr($visibleSlice, $relCursor + 1);
                    $rendered = Ansi::style(Ansi::BOLD_YELLOW, $before)
                        . Ansi::style(Ansi::INVERSE, $cursorChar)
                        . Ansi::style(Ansi::BOLD_YELLOW, $after);
                    $padding = str_repeat(' ', max(0, $this->cols - mb_strwidth($visibleSlice)));
                }
                $middleRows[] = '│ ' . $rendered . $padding . ' │';
            } elseif ($lineExists) {
                $visibleSlice = mb_substr($lineText, 0, $this->cols);
                $rendered = Ansi::style(Ansi::BOLD_YELLOW, $visibleSlice);
                $padding = str_repeat(' ', max(0, $this->cols - mb_strwidth($visibleSlice)));
                $middleRows[] = '│ ' . $rendered . $padding . ' │';
            } else {
                $middleRows[] = '│ ' . str_repeat(' ', $this->cols) . ' │';
            }
        }

        $lines = [$this->label, '', $topBorder];
        foreach ($middleRows as $row) {
            $lines[] = $row;
        }
        $lines[] = $bottomBorder;

        $info = $this->instruction;
        if (null !== $this->maxLength) {
            $info .= ' (' . mb_strlen($this->value) . '/' . $this->maxLength . ')';
        }
        if ('' !== $info) {
            $lines[] = Ansi::style(Ansi::GRAY, $info);
        }

        if (null !== $this->errorMessage) {
            $lines[] = '';
            $lines[] = Ansi::style(Ansi::RED, "✗ {$this->errorMessage}");
        }

        return implode("\n", $lines);
    }
}
