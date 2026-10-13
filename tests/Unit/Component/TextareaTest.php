<?php

declare(strict_types=1);

namespace Tests\Unit\Component;

use ChernegaSergiy\TerminalUi\Component\Textarea;
use ChernegaSergiy\TerminalUi\Terminal\Ansi;
use ChernegaSergiy\TerminalUi\Terminal\Key;
use ChernegaSergiy\TerminalUi\Validator\CallbackValidator;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class TextareaTest extends TestCase
{
    protected function setUp() : void
    {
        Ansi::setEnabled(false);
    }

    public function testConstructorValidatesRows() : void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Textarea rows must be at least 1, 0 given.');
        new Textarea('Prompt', '', null, null, null, 0);
    }

    public function testConstructorValidatesCols() : void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Textarea cols must be at least 10, 5 given.');
        new Textarea('Prompt', '', null, null, null, 5, 5);
    }

    public function testConstructorValidatesMaxLength() : void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Textarea maxLength must be at least 1, 0 given.');
        new Textarea('Prompt', '', null, null, 0);
    }

    public function testInitialStateAndGetters() : void
    {
        $textarea = new Textarea('Your bio:', 'Hello', null, 'Enter text...', 100, 4, 30, 'Esc to submit');

        $this->assertSame('Your bio:', $textarea->getLabel());
        $this->assertSame('Hello', $textarea->getValue());
        $this->assertSame('Hello', $textarea->getResult());
        $this->assertSame(5, $textarea->getCursorPosition());
        $this->assertSame([0, 5], $textarea->getCursorRowAndCol());
        $this->assertSame(1, $textarea->getLineCount());
        $multiline = new Textarea('Your bio:', "Line 1\nLine 2\nLine 3");
        $this->assertSame(3, $multiline->getLineCount());
        $this->assertSame(4, $textarea->getRows());
        $this->assertSame(30, $textarea->getCols());
        $this->assertNull($textarea->getErrorMessage());
        $this->assertFalse($textarea->isDone());
        $this->assertTrue($textarea->isValid());
    }

    public function testTypingCharacters() : void
    {
        $textarea = new Textarea('Input:');
        $textarea->handleKey('H');
        $textarea->handleKey('i');
        $textarea->handleKey('!');

        $this->assertSame('Hi!', $textarea->getValue());
        $this->assertSame(3, $textarea->getCursorPosition());

        // Multi-byte UTF-8 Cyrillic and Emoji
        $textarea->handleKey(' ');
        $textarea->handleKey('П');
        $textarea->handleKey('р');
        $textarea->handleKey('и');
        $textarea->handleKey('в');
        $textarea->handleKey('і');
        $textarea->handleKey('т');
        $textarea->handleKey('👋');

        $this->assertSame('Hi! Привіт👋', $textarea->getValue());
    }

    public function testIgnoreControlCharacters() : void
    {
        $textarea = new Textarea('Input:');
        $textarea->handleKey("\x00");
        $textarea->handleKey("\x01");
        $textarea->handleKey("\x1f");
        $textarea->handleKey("\x7f");

        $this->assertSame('', $textarea->getValue());
    }

    public function testMaxLengthEnforcement() : void
    {
        $textarea = new Textarea('Short:', '123', null, null, 5);
        $textarea->handleKey('4');
        $textarea->handleKey('5');
        $this->assertSame('12345', $textarea->getValue());

        // Should not exceed 5
        $textarea->handleKey('6');
        $this->assertSame('12345', $textarea->getValue());

        // Newline also respects maxLength
        $textarea->handleKey(Key::Enter);
        $this->assertSame('12345', $textarea->getValue());
    }

    public function testEnterInsertsNewline() : void
    {
        $textarea = new Textarea('Input:', 'Line 1');
        $textarea->handleKey(Key::Enter);
        $textarea->handleKey('L');
        $textarea->handleKey('i');
        $textarea->handleKey('n');
        $textarea->handleKey('e');
        $textarea->handleKey(' ');
        $textarea->handleKey('2');

        $this->assertSame("Line 1\nLine 2", $textarea->getValue());
        $this->assertSame([1, 6], $textarea->getCursorRowAndCol());
    }

    public function testLeftAndRightNavigation() : void
    {
        $textarea = new Textarea('Input:', 'AB');
        $this->assertSame(2, $textarea->getCursorPosition());

        $textarea->handleKey(Key::Left);
        $this->assertSame(1, $textarea->getCursorPosition());

        $textarea->handleKey(Key::Left);
        $this->assertSame(0, $textarea->getCursorPosition());

        // Clamps at 0
        $textarea->handleKey(Key::Left);
        $this->assertSame(0, $textarea->getCursorPosition());

        $textarea->handleKey(Key::Right);
        $this->assertSame(1, $textarea->getCursorPosition());

        $textarea->handleKey(Key::Right);
        $this->assertSame(2, $textarea->getCursorPosition());

        // Clamps at end
        $textarea->handleKey(Key::Right);
        $this->assertSame(2, $textarea->getCursorPosition());
    }

    public function testHomeAndEndNavigation() : void
    {
        $textarea = new Textarea('Input:', "First line\nSecond line");
        // Start at end of second line
        $this->assertSame([1, 11], $textarea->getCursorRowAndCol());

        $textarea->handleKey(Key::Home);
        $this->assertSame([1, 0], $textarea->getCursorRowAndCol());

        $textarea->handleKey(Key::End);
        $this->assertSame([1, 11], $textarea->getCursorRowAndCol());

        // Move to first line and test Home/End
        $textarea->handleKey(Key::Up);
        $this->assertSame([0, 10], $textarea->getCursorRowAndCol());

        $textarea->handleKey(Key::Home);
        $this->assertSame([0, 0], $textarea->getCursorRowAndCol());

        $textarea->handleKey(Key::End);
        $this->assertSame([0, 10], $textarea->getCursorRowAndCol());
    }

    public function testUpAndDownNavigation() : void
    {
        $textarea = new Textarea('Input:', "Line 1 long text\nShort\nLine 3");
        // Move to row 0, col 0
        $textarea->handleKey(Key::Up);
        $textarea->handleKey(Key::Up);
        $textarea->handleKey(Key::Home);
        $this->assertSame([0, 0], $textarea->getCursorRowAndCol());

        for ($i = 0; $i < 12; $i++) {
            $textarea->handleKey(Key::Right);
        }
        $this->assertSame([0, 12], $textarea->getCursorRowAndCol());

        // Up on top row stays on top row
        $textarea->handleKey(Key::Up);
        $this->assertSame([0, 12], $textarea->getCursorRowAndCol());

        // Down to row 1 (which only has 5 characters) clamps col to 5
        $textarea->handleKey(Key::Down);
        $this->assertSame([1, 5], $textarea->getCursorRowAndCol());

        // Down to row 2
        $textarea->handleKey(Key::Down);
        $this->assertSame([2, 5], $textarea->getCursorRowAndCol());

        // Down on bottom row stays on bottom row
        $textarea->handleKey(Key::Down);
        $this->assertSame([2, 5], $textarea->getCursorRowAndCol());

        // Up back to row 1
        $textarea->handleKey(Key::Up);
        $this->assertSame([1, 5], $textarea->getCursorRowAndCol());
    }

    public function testPageUpAndPageDownNavigation() : void
    {
        $lines = ["L0", "L1", "L2", "L3", "L4", "L5", "L6", "L7"];
        $textarea = new Textarea('Input:', implode("\n", $lines), null, null, null, 3);

        // Initially at L7
        $this->assertSame([7, 2], $textarea->getCursorRowAndCol());

        // PageUp moves up by 3 rows -> L4
        $textarea->handleKey(Key::PageUp);
        $this->assertSame([4, 2], $textarea->getCursorRowAndCol());

        // PageUp moves up by 3 rows -> L1
        $textarea->handleKey(Key::PageUp);
        $this->assertSame([1, 2], $textarea->getCursorRowAndCol());

        // PageUp clamps to 0 -> L0
        $textarea->handleKey(Key::PageUp);
        $this->assertSame([0, 2], $textarea->getCursorRowAndCol());

        // PageDown moves down by 3 rows -> L3
        $textarea->handleKey(Key::PageDown);
        $this->assertSame([3, 2], $textarea->getCursorRowAndCol());

        // PageDown moves down by 3 rows -> L6
        $textarea->handleKey(Key::PageDown);
        $this->assertSame([6, 2], $textarea->getCursorRowAndCol());

        // PageDown clamps to max -> L7
        $textarea->handleKey(Key::PageDown);
        $this->assertSame([7, 2], $textarea->getCursorRowAndCol());
    }

    public function testBackspaceAndMergeLines() : void
    {
        $textarea = new Textarea('Input:', "A\nB");
        $this->assertSame(3, $textarea->getCursorPosition());

        // Backspace deletes 'B'
        $textarea->handleKey(Key::Backspace);
        $this->assertSame("A\n", $textarea->getValue());
        $this->assertSame(2, $textarea->getCursorPosition());

        // Backspace deletes '\n', merging lines into "A"
        $textarea->handleKey(Key::Backspace);
        $this->assertSame("A", $textarea->getValue());
        $this->assertSame(1, $textarea->getCursorPosition());

        // Backspace deletes 'A'
        $textarea->handleKey(Key::Backspace);
        $this->assertSame("", $textarea->getValue());
        $this->assertSame(0, $textarea->getCursorPosition());

        // Backspace at 0 does nothing
        $textarea->handleKey(Key::Backspace);
        $this->assertSame("", $textarea->getValue());
    }

    public function testDeleteAndMergeLines() : void
    {
        $textarea = new Textarea('Input:', "A\nB");
        // Move to pos 0
        $textarea->handleKey(Key::Home);
        $textarea->handleKey(Key::Up);
        $this->assertSame(0, $textarea->getCursorPosition());

        // Delete 'A' -> "\nB"
        $textarea->handleKey(Key::Delete);
        $this->assertSame("\nB", $textarea->getValue());

        // Delete '\n' -> "B"
        $textarea->handleKey(Key::Delete);
        $this->assertSame("B", $textarea->getValue());

        // Delete 'B' -> ""
        $textarea->handleKey(Key::Delete);
        $this->assertSame("", $textarea->getValue());

        // Delete at end does nothing
        $textarea->handleKey(Key::Delete);
        $this->assertSame("", $textarea->getValue());
    }

    public function testSubmitWithEscAndCtrlD() : void
    {
        $textarea = new Textarea('Input:', 'Some content');
        $this->assertFalse($textarea->isDone());

        $textarea->handleKey(Key::Esc);
        $this->assertTrue($textarea->isDone());

        $textarea2 = new Textarea('Input:', 'Another content');
        $textarea2->handleKey("\x04");
        $this->assertTrue($textarea2->isDone());
    }

    public function testValidationFailureAndSuccess() : void
    {
        $validator = new CallbackValidator(static function (string $value) : ?string {
            return mb_strlen($value) < 5 ? 'Minimum 5 characters required' : null;
        });

        $textarea = new Textarea('Description:', 'Hi', $validator);
        $this->assertFalse($textarea->isValid());

        $textarea->handleKey(Key::Esc);
        $this->assertFalse($textarea->isDone());
        $this->assertSame('Minimum 5 characters required', $textarea->getErrorMessage());

        // Typing clears error message
        $textarea->handleKey('!');
        $this->assertNull($textarea->getErrorMessage());

        $textarea->handleKey('!');
        $textarea->handleKey('!');
        $this->assertTrue($textarea->isValid());

        $textarea->handleKey(Key::Esc);
        $this->assertTrue($textarea->isDone());
        $this->assertNull($textarea->getErrorMessage());
    }

    public function testRenderEmptyWithPlaceholder() : void
    {
        $textarea = new Textarea('Notes:', '', null, 'Write something here...', null, 3, 30);
        $output = $textarea->render();

        $this->assertStringContainsString('Notes:', $output);
        $this->assertStringContainsString('Write something here...', $output);
        $this->assertStringContainsString('┌' . str_repeat('─', 32) . '┐', $output);
        $this->assertStringContainsString('└' . str_repeat('─', 32) . '┘', $output);
        $this->assertStringContainsString('Enter for new line, Esc to submit', $output);
    }

    public function testRenderWithCursorAtEndAndInsideText() : void
    {
        $textarea = new Textarea('Code:', 'echo 1;', null, null, null, 2, 20);
        $outputEnd = $textarea->render();
        $this->assertStringContainsString('echo 1;_', $outputEnd);

        // Move cursor inside text
        $textarea->handleKey(Key::Left);
        $textarea->handleKey(Key::Left);
        $outputInside = $textarea->render();
        $this->assertStringContainsString('echo 1;', $outputInside);
    }

    public function testRenderWithVerticalScrollingIndicators() : void
    {
        $lines = ["Line 1", "Line 2", "Line 3", "Line 4", "Line 5"];
        $textarea = new Textarea('Story:', implode("\n", $lines), null, null, null, 2, 25);

        // Initially cursor is at Line 5 (scrollOffset = 3)
        $output = $textarea->render();
        $this->assertStringContainsString('↑ (3 more)', $output);

        // Move to Line 1 (scrollOffset = 0, linesBelow = 3)
        for ($i = 0; $i < 5; $i++) {
            $textarea->handleKey(Key::Up);
        }
        $outputTop = $textarea->render();
        $this->assertStringContainsString('↓ (3 more)', $outputTop);
    }

    public function testRenderHorizontalScrollWhenLineExceedsCols() : void
    {
        $longLine = str_repeat('A', 35);
        $textarea = new Textarea('Long text:', $longLine, null, null, null, 2, 15);
        $output = $textarea->render();

        $this->assertStringContainsString('│', $output);
    }

    public function testRenderWithErrorMessageAndMaxLengthCounter() : void
    {
        $validator = new CallbackValidator(static fn (string $val) => 'Invalid value');
        $textarea = new Textarea('Field:', 'abc', $validator, null, 50, 2, 20);
        $textarea->handleKey(Key::Esc);

        $output = $textarea->render();
        $this->assertStringContainsString('✗ Invalid value', $output);
        $this->assertStringContainsString('(3/50)', $output);
    }
}
