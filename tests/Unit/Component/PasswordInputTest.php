<?php

declare(strict_types=1);

namespace Tests\Unit\Component;

use ChernegaSergiy\TerminalUi\Component\PasswordInput;
use ChernegaSergiy\TerminalUi\Terminal\Ansi;
use ChernegaSergiy\TerminalUi\Terminal\Key;
use ChernegaSergiy\TerminalUi\Validator\CallbackValidator;
use PHPUnit\Framework\TestCase;

final class PasswordInputTest extends TestCase
{
    protected function setUp() : void
    {
        Ansi::setEnabled(false);
    }

    public function testInitialStateAndDefaultMask() : void
    {
        $input = new PasswordInput('Password:');

        $this->assertSame('Password:', $input->getLabel());
        $this->assertSame('*', $input->getMask());
        $this->assertSame('', $input->getValue());
        $this->assertSame('', $input->getResult());
        $this->assertSame(0, $input->getCursorPosition());
        $this->assertFalse($input->isDone());
    }

    public function testMaskedRendering() : void
    {
        $input = new PasswordInput('Pin:', mask: '•');
        $input->handleKey('1');
        $input->handleKey('2');
        $input->handleKey('3');

        $this->assertSame('123', $input->getResult());

        $rendered = $input->render();
        $this->assertStringNotContainsString('123', $rendered);
        $this->assertStringContainsString('•••_', $rendered);
    }

    public function testRenderWithPlaceholder() : void
    {
        $input = new PasswordInput('Secret:', placeholder: 'Enter secret');

        $rendered = $input->render();
        $this->assertStringContainsString('Enter secret', $rendered);
    }

    public function testRenderWithCursorInsideSecret() : void
    {
        $input = new PasswordInput('Pass:');
        $input->handleKey('a');
        $input->handleKey('b');
        $input->handleKey('c');
        $input->handleKey(Key::Left);

        $rendered = $input->render();
        $this->assertStringContainsString('Pass:', $rendered);
    }

    public function testRenderWithEmptyMaskSilentMode() : void
    {
        $input = new PasswordInput('Silent:', mask: '');
        $input->handleKey('x');
        $input->handleKey('y');
        $input->handleKey(Key::Left);

        $rendered = $input->render();
        $this->assertStringContainsString('Silent:', $rendered);
    }

    public function testValidationAndErrors() : void
    {
        $validator = new CallbackValidator(static function (string $val) : ?string {
            return strlen($val) >= 6 ? null : 'Minimum 6 chars';
        });

        $input = new PasswordInput('Password:', validator: $validator);
        $input->handleKey('1');
        $input->handleKey('2');
        $input->handleKey(Key::Enter);

        $this->assertFalse($input->isDone());

        $rendered = $input->render();
        $this->assertStringContainsString('✗ Minimum 6 chars', $rendered);

        $input->handleKey('3');
        $input->handleKey('4');
        $input->handleKey('5');
        $input->handleKey('6');
        $input->handleKey(Key::Enter);

        $this->assertTrue($input->isDone());
        $this->assertSame('123456', $input->getResult());
    }
}
