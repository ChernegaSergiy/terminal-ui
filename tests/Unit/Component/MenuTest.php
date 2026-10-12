<?php

declare(strict_types=1);

namespace Tests\Unit\Component;

use ChernegaSergiy\TerminalUi\Component\Menu;
use ChernegaSergiy\TerminalUi\Terminal\Ansi;
use ChernegaSergiy\TerminalUi\Terminal\Key;
use PHPUnit\Framework\TestCase;

final class MenuTest extends TestCase
{
    protected function setUp() : void
    {
        Ansi::setEnabled(false);
    }

    public function testInitialState() : void
    {
        $menu = new Menu('Main Menu', ['Option 1', 'Option 2', 'Option 3']);

        $this->assertSame('Main Menu', $menu->getLabel());
        $this->assertSame(0, $menu->getResult());
        $this->assertFalse($menu->isDone());
        $this->assertTrue($menu->isValid());

        $rendered = $menu->render();
        $this->assertStringContainsString('Main Menu', $rendered);
        $this->assertStringContainsString('▶ Option 1', $rendered);
        $this->assertStringContainsString('  Option 2', $rendered);
        $this->assertStringContainsString('  Option 3', $rendered);
        $this->assertStringContainsString('Use ↑↓ to navigate, Enter to select, ESC to cancel', $rendered);
    }

    public function testNavigationUpDownHomeEnd() : void
    {
        $menu = new Menu('Navigation', ['First', 'Second', 'Third']);

        // Up at top does not go below 0
        $menu->handleKey(Key::Up);
        $this->assertSame(0, $menu->getResult());

        // Down advances
        $menu->handleKey(Key::Down);
        $this->assertSame(1, $menu->getResult());

        $menu->handleKey(Key::Down);
        $this->assertSame(2, $menu->getResult());

        // Down at bottom clamps
        $menu->handleKey(Key::Down);
        $this->assertSame(2, $menu->getResult());

        // Home jumps to 0
        $menu->handleKey(Key::Home);
        $this->assertSame(0, $menu->getResult());

        // End jumps to last item
        $menu->handleKey(Key::End);
        $this->assertSame(2, $menu->getResult());

        // Up moves back
        $menu->handleKey(Key::Up);
        $this->assertSame(1, $menu->getResult());
    }

    public function testEnterConfirmsSelection() : void
    {
        $menu = new Menu('Select', ['Alpha', 'Beta']);
        $menu->handleKey(Key::Down);
        $menu->handleKey(Key::Enter);

        $this->assertTrue($menu->isDone());
        $this->assertSame(1, $menu->getResult());
    }

    public function testEscCancelsMenu() : void
    {
        $menu = new Menu('Cancel Me', ['Item 1', 'Item 2']);
        $menu->handleKey(Key::Esc);

        $this->assertTrue($menu->isDone());
        $this->assertNull($menu->getResult());
    }

    public function testEmptyItemsNavigation() : void
    {
        $menu = new Menu('Empty', []);
        $menu->handleKey(Key::End);
        $this->assertSame(0, $menu->getResult());
    }
}
