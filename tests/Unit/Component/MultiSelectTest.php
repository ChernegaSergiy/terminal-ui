<?php

declare(strict_types=1);

namespace Tests\Unit\Component;

use ChernegaSergiy\TerminalUi\Component\MultiSelect;
use ChernegaSergiy\TerminalUi\Terminal\Ansi;
use ChernegaSergiy\TerminalUi\Terminal\Key;
use PHPUnit\Framework\TestCase;

final class MultiSelectTest extends TestCase
{
    protected function setUp() : void
    {
        Ansi::setEnabled(false);
    }

    public function testInitialStateWithDefaults() : void
    {
        $options = ['php' => 'PHP', 'js' => 'JavaScript', 'py' => 'Python'];
        $select = new MultiSelect('Select languages:', $options, default: ['php']);

        $this->assertSame('Select languages:', $select->getLabel());
        $this->assertSame(0, $select->getCursor());
        $this->assertFalse($select->isDone());
        $this->assertFalse($select->isCancelled());
        $this->assertTrue($select->isValid());
        $this->assertSame(['php'], $select->getSelectedKeys());
        $this->assertSame(['php' => 'PHP'], $select->getSelectedValues());
        $this->assertSame(['php'], $select->getResult());

        $rendered = $select->render();
        $this->assertStringContainsString('▶ [x] PHP', $rendered);
        $this->assertStringContainsString('  [ ] JavaScript', $rendered);
    }

    public function testNavigationKeys() : void
    {
        $options = ['a' => 'A', 'b' => 'B', 'c' => 'C'];
        $select = new MultiSelect('Items', $options);

        // Up at top clamps
        $select->handleKey(Key::Up);
        $this->assertSame(0, $select->getCursor());

        // Down advances
        $select->handleKey(Key::Down);
        $this->assertSame(1, $select->getCursor());

        $select->handleKey(Key::Down);
        $this->assertSame(2, $select->getCursor());

        // Down at bottom clamps
        $select->handleKey(Key::Down);
        $this->assertSame(2, $select->getCursor());

        // Home jumps to 0
        $select->handleKey(Key::Home);
        $this->assertSame(0, $select->getCursor());

        // End jumps to last
        $select->handleKey(Key::End);
        $this->assertSame(2, $select->getCursor());

        // Up moves back
        $select->handleKey(Key::Up);
        $this->assertSame(1, $select->getCursor());
    }

    public function testSpaceTogglesItem() : void
    {
        $select = new MultiSelect('Toggle', ['opt1' => 'One', 'opt2' => 'Two']);

        $this->assertEmpty($select->getSelectedKeys());

        $select->handleKey(' ');
        $this->assertSame(['opt1'], $select->getSelectedKeys());

        $select->handleKey(' ');
        $this->assertEmpty($select->getSelectedKeys());
    }

    public function testToggleAllWithA() : void
    {
        $select = new MultiSelect('Toggle All', ['x' => 'X', 'y' => 'Y']);

        // None selected: 'a' selects all
        $select->handleKey('a');
        $this->assertSame(['x', 'y'], $select->getSelectedKeys());

        // All selected: 'A' deselects all
        $select->handleKey('A');
        $this->assertEmpty($select->getSelectedKeys());
    }

    public function testMinMaxValidationAndErrorRendering() : void
    {
        $options = ['1' => 'One', '2' => 'Two', '3' => 'Three'];
        $select = new MultiSelect('Limits', $options, min: 1, max: 2);

        // Initially 0 selected: below min (1)
        $this->assertFalse($select->isValid());

        $select->handleKey(Key::Enter);
        $this->assertFalse($select->isDone());

        $rendered = $select->render();
        $this->assertStringContainsString('✗ Please select at least 1 item(s).', $rendered);

        // Select all 3: exceeds max (2)
        $select->handleKey('a');
        $this->assertFalse($select->isValid());

        $select->handleKey(Key::Enter);
        $this->assertFalse($select->isDone());

        $rendered2 = $select->render();
        $this->assertStringContainsString('✗ Please select at most 2 item(s).', $rendered2);

        // Deselect one to make count 2 (within range 1..2)
        $select->handleKey(' ');
        $this->assertTrue($select->isValid());

        $select->handleKey(Key::Enter);
        $this->assertTrue($select->isDone());
        $this->assertFalse($select->isCancelled());
        $this->assertNotNull($select->getResult());
    }

    public function testEscCancelsSelection() : void
    {
        $select = new MultiSelect('Cancel', ['k' => 'V'], default: ['k']);

        $select->handleKey(Key::Esc);
        $this->assertTrue($select->isDone());
        $this->assertTrue($select->isCancelled());
        $this->assertNull($select->getResult());
    }

    public function testEmptyOptionsBehavior() : void
    {
        $select = new MultiSelect('Empty', []);

        // Other key ignored
        $select->handleKey('x');
        $this->assertFalse($select->isDone());

        // Enter confirms empty
        $select->handleKey(Key::Enter);
        $this->assertTrue($select->isDone());
        $this->assertFalse($select->isCancelled());

        // Another empty select with Esc
        $select2 = new MultiSelect('Empty 2', []);
        $select2->handleKey(Key::Esc);
        $this->assertTrue($select2->isDone());
        $this->assertTrue($select2->isCancelled());
    }
}
