<?php

declare(strict_types=1);

namespace Tests\Unit\Terminal;

use ChernegaSergiy\TerminalUi\Terminal\Key;
use PHPUnit\Framework\TestCase;

final class KeyTest extends TestCase
{
    public function testEnumValues() : void
    {
        $this->assertSame('up', Key::Up->value);
        $this->assertSame('down', Key::Down->value);
        $this->assertSame('left', Key::Left->value);
        $this->assertSame('right', Key::Right->value);
        $this->assertSame('enter', Key::Enter->value);
        $this->assertSame('esc', Key::Esc->value);
        $this->assertSame('tab', Key::Tab->value);
        $this->assertSame('backspace', Key::Backspace->value);
        $this->assertSame('delete', Key::Delete->value);
        $this->assertSame('home', Key::Home->value);
        $this->assertSame('end', Key::End->value);
        $this->assertSame('page_up', Key::PageUp->value);
        $this->assertSame('page_down', Key::PageDown->value);
    }

    public function testCasesCountAndResolution() : void
    {
        $cases = Key::cases();
        $this->assertCount(13, $cases);

        $this->assertSame(Key::Up, Key::from('up'));
        $this->assertSame(Key::Down, Key::from('down'));
        $this->assertSame(Key::Left, Key::from('left'));
        $this->assertSame(Key::Right, Key::from('right'));
        $this->assertSame(Key::Enter, Key::from('enter'));
        $this->assertSame(Key::Esc, Key::from('esc'));
        $this->assertSame(Key::Tab, Key::from('tab'));
        $this->assertSame(Key::Backspace, Key::from('backspace'));
        $this->assertSame(Key::Delete, Key::from('delete'));
        $this->assertSame(Key::Home, Key::from('home'));
        $this->assertSame(Key::End, Key::from('end'));
        $this->assertSame(Key::PageUp, Key::from('page_up'));
        $this->assertSame(Key::PageDown, Key::from('page_down'));

        $this->assertNull(Key::tryFrom('unknown_key'));
    }
}
