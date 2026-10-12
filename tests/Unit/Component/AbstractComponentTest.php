<?php

declare(strict_types=1);

namespace Tests\Unit\Component;

use ChernegaSergiy\TerminalUi\Component\AbstractComponent;
use ChernegaSergiy\TerminalUi\Terminal\Key;
use PHPUnit\Framework\TestCase;

final class AbstractComponentTest extends TestCase
{
    private function createComponent(string $label) : AbstractComponent
    {
        return new class ($label) extends AbstractComponent {
            public function __construct(string $label)
            {
                parent::__construct($label);
            }

            public function render() : string
            {
                return $this->label;
            }

            public function handleKey(Key|string $key) : void
            {
                $this->done = true;
            }

            public function getResult() : mixed
            {
                return 'result';
            }
        };
    }

    public function testGetLabel() : void
    {
        $component = $this->createComponent('Test Label');

        $this->assertSame('Test Label', $component->getLabel());
    }

    public function testIsDoneInitiallyFalse() : void
    {
        $component = $this->createComponent('Test');

        $this->assertFalse($component->isDone());

        $component->handleKey(Key::Enter);
        $this->assertTrue($component->isDone());
    }

    public function testIsValidDefaultsToTrue() : void
    {
        $component = $this->createComponent('Test');

        $this->assertTrue($component->isValid());
    }
}
