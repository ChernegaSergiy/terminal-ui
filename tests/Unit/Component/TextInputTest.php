<?php

declare(strict_types=1);

namespace Tests\Unit\Component;

use ChernegaSergiy\TerminalUi\Component\TextInput;
use ChernegaSergiy\TerminalUi\Terminal\Ansi;
use ChernegaSergiy\TerminalUi\Terminal\Key;
use ChernegaSergiy\TerminalUi\Validator\CallbackValidator;
use PHPUnit\Framework\TestCase;

final class TextInputTest extends TestCase
{
    protected function setUp() : void
    {
        Ansi::setEnabled(false);
    }

    public function testInitialState() : void
    {
        $input = new TextInput('Username:', 'john');

        $this->assertSame('Username:', $input->getLabel());
        $this->assertSame('john', $input->getResult());
        $this->assertSame('john', $input->getValue());
        $this->assertSame(4, $input->getCursorPosition());
        $this->assertFalse($input->isDone());
        $this->assertTrue($input->isValid());
    }

    public function testCursorNavigation() : void
    {
        $input = new TextInput('Name:', 'cat');

        $input->handleKey(Key::Left);
        $this->assertSame(2, $input->getCursorPosition());

        $input->handleKey(Key::Left);
        $this->assertSame(1, $input->getCursorPosition());

        $input->handleKey(Key::Home);
        $this->assertSame(0, $input->getCursorPosition());

        // Clamps at 0
        $input->handleKey(Key::Left);
        $this->assertSame(0, $input->getCursorPosition());

        $input->handleKey(Key::Right);
        $this->assertSame(1, $input->getCursorPosition());

        $input->handleKey(Key::End);
        $this->assertSame(3, $input->getCursorPosition());

        // Clamps at len
        $input->handleKey(Key::Right);
        $this->assertSame(3, $input->getCursorPosition());
    }

    public function testTypingAndMaxLength() : void
    {
        $input = new TextInput('Code:', maxLength: 4);

        $input->handleKey('a');
        $input->handleKey('b');
        $this->assertSame('ab', $input->getValue());
        $this->assertSame(2, $input->getCursorPosition());

        // Insert in middle
        $input->handleKey(Key::Left);
        $input->handleKey('x');
        $this->assertSame('axb', $input->getValue());
        $this->assertSame(2, $input->getCursorPosition());

        // Reaching maxLength limit (3 + 1 = 4)
        $input->handleKey(Key::End);
        $input->handleKey('z');
        $this->assertSame('axbz', $input->getValue());

        // Exceeding maxLength ignored
        $input->handleKey('q');
        $this->assertSame('axbz', $input->getValue());

        // Empty string or control character ignored
        $input->handleKey('');
        $input->handleKey("\x01");
        $this->assertSame('axbz', $input->getValue());
    }

    public function testBackspaceAndDelete() : void
    {
        $input = new TextInput('Text:', 'hello');

        // Backspace at end
        $input->handleKey(Key::Backspace);
        $this->assertSame('hell', $input->getValue());
        $this->assertSame(4, $input->getCursorPosition());

        // Move to position 1 and delete
        $input->handleKey(Key::Home);
        $input->handleKey(Key::Right);
        $this->assertSame(1, $input->getCursorPosition());

        $input->handleKey(Key::Delete);
        $this->assertSame('hll', $input->getValue());
        $this->assertSame(1, $input->getCursorPosition());

        // Backspace at position 1
        $input->handleKey(Key::Backspace);
        $this->assertSame('ll', $input->getValue());
        $this->assertSame(0, $input->getCursorPosition());

        // Backspace at 0 does nothing
        $input->handleKey(Key::Backspace);
        $this->assertSame('ll', $input->getValue());

        // Delete at end does nothing
        $input->handleKey(Key::End);
        $input->handleKey(Key::Delete);
        $this->assertSame('ll', $input->getValue());
    }

    public function testMultiByteUtf8Support() : void
    {
        $input = new TextInput('Місто:');

        $input->handleKey('К');
        $input->handleKey('и');
        $input->handleKey('ї');
        $input->handleKey('в');

        $this->assertSame('Київ', $input->getValue());
        $this->assertSame(4, $input->getCursorPosition());

        $input->handleKey(Key::Left);
        $this->assertSame(3, $input->getCursorPosition());
        $input->handleKey(Key::Backspace);
        $this->assertSame('Кив', $input->getValue());
    }

    public function testValidationAndErrorRendering() : void
    {
        $validator = new CallbackValidator(static function (string $val) : ?string {
            return str_contains($val, '@') ? null : 'Email must contain @';
        });

        $input = new TextInput('Email:', validator: $validator);

        $this->assertFalse($input->isValid());

        $input->handleKey(Key::Enter);
        $this->assertFalse($input->isDone());

        $rendered = $input->render();
        $this->assertStringContainsString('✗ Email must contain @', $rendered);

        $input->handleKey('a');
        $input->handleKey('@');
        $input->handleKey('b');
        $this->assertTrue($input->isValid());

        $input->handleKey(Key::Enter);
        $this->assertTrue($input->isDone());
    }

    public function testRenderWithPlaceholder() : void
    {
        $input = new TextInput('Search:', placeholder: 'Type here...');

        $rendered = $input->render();
        $this->assertStringContainsString('Type here...', $rendered);
    }

    public function testRenderWithCursorInMiddle() : void
    {
        $input = new TextInput('Word:', 'test');
        $input->handleKey(Key::Left);
        $input->handleKey(Key::Left);

        $rendered = $input->render();
        $this->assertStringContainsString('Word:', $rendered);
    }
}
