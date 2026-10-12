<?php

declare(strict_types=1);

namespace Tests\Unit\Component;

use ChernegaSergiy\TerminalUi\Component\SearchSelect;
use ChernegaSergiy\TerminalUi\Terminal\Ansi;
use ChernegaSergiy\TerminalUi\Terminal\Key;
use PHPUnit\Framework\TestCase;

final class SearchSelectTest extends TestCase
{
    protected function setUp() : void
    {
        Ansi::setEnabled(false);
    }

    public function testInitialState() : void
    {
        $options = ['kyiv' => 'Київ', 'lviv' => 'Львів', 'odesa' => 'Одеса'];
        $select = new SearchSelect('Choose city:', $options);

        $this->assertSame('Choose city:', $select->getLabel());
        $this->assertSame('', $select->getQuery());
        $this->assertSame(0, $select->getSelectedIndex());
        $this->assertFalse($select->isDone());
        $this->assertFalse($select->isCancelled());
        $this->assertSame('kyiv', $select->getResult());
        $this->assertSame('Київ', $select->getSelectedValue());

        $rendered = $select->render();
        $this->assertStringContainsString('Choose city:', $rendered);
        $this->assertStringContainsString('Search: Type to search...', $rendered);
        $this->assertStringContainsString('▶ Київ', $rendered);
        $this->assertStringContainsString('  Львів', $rendered);
    }

    public function testTypingAndFiltering() : void
    {
        $options = ['kyiv' => 'Київ', 'lviv' => 'Львів', 'dnipro' => 'Дніпро'];
        $select = new SearchSelect('Cities', $options);

        $select->handleKey('Л');
        $select->handleKey('ь');
        $select->handleKey('в');

        $this->assertSame('Льв', $select->getQuery());
        $this->assertSame(['lviv'], $select->getFilteredKeys());
        $this->assertSame('lviv', $select->getResult());
        $this->assertSame('Львів', $select->getSelectedValue());

        // Backspace removes character
        $select->handleKey(Key::Backspace);
        $this->assertSame('Ль', $select->getQuery());

        // Empty query restores all options
        $select->handleKey(Key::Backspace);
        $select->handleKey(Key::Backspace);
        $this->assertSame('', $select->getQuery());
        $this->assertSame(['kyiv', 'lviv', 'dnipro'], $select->getFilteredKeys());

        // Backspace on empty does nothing
        $select->handleKey(Key::Backspace);
        $this->assertSame('', $select->getQuery());

        // Control characters or empty string ignored
        $select->handleKey('');
        $select->handleKey("\x03");
        $this->assertSame('', $select->getQuery());
    }

    public function testFilterByKeyAsWellAsLabel() : void
    {
        $options = ['ua' => 'Ukraine', 'pl' => 'Poland'];
        $select = new SearchSelect('Country', $options);

        $select->handleKey('u');
        $select->handleKey('a');

        $this->assertSame('ua', $select->getResult());
        $this->assertSame('Ukraine', $select->getSelectedValue());
    }

    public function testNavigationKeys() : void
    {
        $options = ['1' => 'One', '2' => 'Two', '3' => 'Three'];
        $select = new SearchSelect('Numbers', $options);

        // Up clamps at 0
        $select->handleKey(Key::Up);
        $this->assertSame(0, $select->getSelectedIndex());

        // Down advances
        $select->handleKey(Key::Down);
        $this->assertSame(1, $select->getSelectedIndex());

        $select->handleKey(Key::Down);
        $this->assertSame(2, $select->getSelectedIndex());

        // Down clamps at end
        $select->handleKey(Key::Down);
        $this->assertSame(2, $select->getSelectedIndex());

        // Home jumps to 0
        $select->handleKey(Key::Home);
        $this->assertSame(0, $select->getSelectedIndex());

        // End jumps to last
        $select->handleKey(Key::End);
        $this->assertSame(2, $select->getSelectedIndex());

        // Up moves back
        $select->handleKey(Key::Up);
        $this->assertSame(1, $select->getSelectedIndex());
    }

    public function testEnterConfirmsAndEscCancels() : void
    {
        $select = new SearchSelect('Item', ['a' => 'A', 'b' => 'B']);

        $select->handleKey(Key::Down);
        $select->handleKey(Key::Enter);

        $this->assertTrue($select->isDone());
        $this->assertFalse($select->isCancelled());
        $this->assertSame('b', $select->getResult());

        $select2 = new SearchSelect('Cancel', ['x' => 'X']);
        $select2->handleKey(Key::Esc);

        $this->assertTrue($select2->isDone());
        $this->assertTrue($select2->isCancelled());
        $this->assertNull($select2->getResult());
        $this->assertNull($select2->getSelectedValue());
    }

    public function testNoMatchesRenderingAndEnterBlocked() : void
    {
        $select = new SearchSelect('Test', ['cat' => 'Cat']);

        $select->handleKey('z');
        $select->handleKey('z');

        $this->assertEmpty($select->getFilteredKeys());
        $this->assertNull($select->getResult());
        $this->assertNull($select->getSelectedValue());

        // Enter does not complete when no matches
        $select->handleKey(Key::Enter);
        $this->assertFalse($select->isDone());

        $rendered = $select->render();
        $this->assertStringContainsString('(No matches found)', $rendered);
    }

    public function testPaginationWindowRendering() : void
    {
        $options = [];
        for ($i = 1; $i <= 10; $i++) {
            $options["k{$i}"] = "Item {$i}";
        }

        $select = new SearchSelect('Many', $options, limit: 3);

        // Jump to middle item
        $select->handleKey(Key::Down);
        $select->handleKey(Key::Down);
        $select->handleKey(Key::Down);
        $select->handleKey(Key::Down);
        $select->handleKey(Key::Down);

        $rendered = $select->render();
        $this->assertStringContainsString('(Showing 3 of 10 matches)', $rendered);
    }
}
