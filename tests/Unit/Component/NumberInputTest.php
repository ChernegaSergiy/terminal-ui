<?php

declare(strict_types=1);

namespace Tests\Unit\Component;

use ChernegaSergiy\TerminalUi\Component\NumberInput;
use ChernegaSergiy\TerminalUi\Terminal\Ansi;
use ChernegaSergiy\TerminalUi\Terminal\Key;
use ChernegaSergiy\TerminalUi\Validator\CallbackValidator;
use PHPUnit\Framework\TestCase;

final class NumberInputTest extends TestCase
{
    protected function setUp() : void
    {
        Ansi::setEnabled(false);
    }

    public function testInitialStateWithoutValidator() : void
    {
        $input = new NumberInput('Enter age:');

        $this->assertSame('Enter age:', $input->getLabel());
        $this->assertSame(0, $input->getResult());
        $this->assertFalse($input->isDone());
        $this->assertTrue($input->isValid());

        $rendered = $input->render();
        $this->assertStringContainsString('Enter age:', $rendered);
        $this->assertStringContainsString('_', $rendered);
    }

    public function testTypingDigitsAndLengthLimit() : void
    {
        $input = new NumberInput('Code:');

        $input->handleKey('1');
        $input->handleKey('2');
        $input->handleKey('3');
        $this->assertSame(123, $input->getResult());

        // Max length is 3: fourth digit ignored
        $input->handleKey('4');
        $this->assertSame(123, $input->getResult());

        // Non-digit character ignored
        $input->handleKey('a');
        $this->assertSame(123, $input->getResult());
    }

    public function testBackspace() : void
    {
        $input = new NumberInput('Number:');

        // Backspace on empty does nothing
        $input->handleKey(Key::Backspace);
        $this->assertSame(0, $input->getResult());

        $input->handleKey('5');
        $input->handleKey('9');
        $this->assertSame(59, $input->getResult());

        $input->handleKey(Key::Backspace);
        $this->assertSame(5, $input->getResult());

        $input->handleKey(Key::Backspace);
        $this->assertSame(0, $input->getResult());
    }

    public function testValidationFailureAndSuccess() : void
    {
        $validator = new CallbackValidator(static function (string $value) : ?string {
            $num = (int) $value;
            return $num >= 18 ? null : 'Must be at least 18';
        });

        $input = new NumberInput('Age:', $validator);

        // Initially invalid (empty / 0 < 18)
        $this->assertFalse($input->isValid());

        // Pressing Enter when invalid sets error and does not finish
        $input->handleKey(Key::Enter);
        $this->assertFalse($input->isDone());
        $this->assertFalse($input->isValid());

        $rendered = $input->render();
        $this->assertStringContainsString('✗ Must be at least 18', $rendered);

        // Typing clears error message
        $input->handleKey('1');
        $input->handleKey('8');
        $this->assertTrue($input->isValid());

        // Pressing Enter when valid completes
        $input->handleKey(Key::Enter);
        $this->assertTrue($input->isDone());
        $this->assertSame(18, $input->getResult());
    }
}
