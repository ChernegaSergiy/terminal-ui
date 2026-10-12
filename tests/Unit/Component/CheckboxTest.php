<?php

declare(strict_types=1);

namespace Tests\Unit\Component;

use ChernegaSergiy\TerminalUi\Component\Checkbox;
use ChernegaSergiy\TerminalUi\Terminal\Ansi;
use ChernegaSergiy\TerminalUi\Terminal\Key;
use PHPUnit\Framework\TestCase;

final class CheckboxTest extends TestCase
{
    protected function setUp() : void
    {
        Ansi::setEnabled(false);
    }

    public function testDefaultUncheckedState() : void
    {
        $checkbox = new Checkbox('Accept terms');

        $this->assertSame('Accept terms', $checkbox->getLabel());
        $this->assertFalse($checkbox->getResult());
        $this->assertFalse($checkbox->isDone());
        $this->assertTrue($checkbox->isValid());

        $rendered = $checkbox->render();
        $this->assertStringContainsString('[ ] Accept terms', $rendered);
        $this->assertStringContainsString('Press Space to toggle, Enter to confirm', $rendered);
    }

    public function testDefaultCheckedState() : void
    {
        $checkbox = new Checkbox('Enable notifications', default_checked: true, instruction: 'Custom hint');

        $this->assertTrue($checkbox->getResult());
        $this->assertStringContainsString('[x] Enable notifications', $checkbox->render());
        $this->assertStringContainsString('Custom hint', $checkbox->render());
    }

    public function testToggleWithSpaceKey() : void
    {
        $checkbox = new Checkbox('Opt in');

        $checkbox->handleKey(' ');
        $this->assertTrue($checkbox->getResult());
        $this->assertStringContainsString('[x] Opt in', $checkbox->render());

        $checkbox->handleKey(' ');
        $this->assertFalse($checkbox->getResult());
        $this->assertStringContainsString('[ ] Opt in', $checkbox->render());
    }

    public function testConfirmWithEnterKey() : void
    {
        $checkbox = new Checkbox('Confirm');

        $this->assertFalse($checkbox->isDone());
        $checkbox->handleKey(Key::Enter);
        $this->assertTrue($checkbox->isDone());
    }
}
