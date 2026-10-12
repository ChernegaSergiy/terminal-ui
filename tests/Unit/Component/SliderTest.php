<?php

declare(strict_types=1);

namespace Tests\Unit\Component;

use ChernegaSergiy\TerminalUi\Component\Slider;
use ChernegaSergiy\TerminalUi\Terminal\Ansi;
use ChernegaSergiy\TerminalUi\Terminal\Key;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class SliderTest extends TestCase
{
    protected function setUp() : void
    {
        Ansi::setEnabled(false);
    }

    public function testConstructorValidatesRange() : void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Slider minimum (10) must not exceed its maximum (5).');
        new Slider('Range', 10, 5);
    }

    public function testConstructorValidatesStep() : void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Slider step must be at least 1, 0 given.');
        new Slider('Step', 0, 10, step: 0);
    }

    public function testConstructorInitialValuesAndClamping() : void
    {
        $slider1 = new Slider('Default min', 5, 20);
        $this->assertSame(5, $slider1->getResult());
        $this->assertSame('Default min', $slider1->getLabel());
        $this->assertFalse($slider1->isDone());

        $slider2 = new Slider('Clamped high', 0, 100, default: 150);
        $this->assertSame(100, $slider2->getResult());

        $slider3 = new Slider('Clamped low', 10, 50, default: 0);
        $this->assertSame(10, $slider3->getResult());
    }

    public function testArrowKeysNavigation() : void
    {
        $slider = new Slider('Speed', 0, 10, default: 5, step: 2);

        $slider->handleKey(Key::Right);
        $this->assertSame(7, $slider->getResult());

        $slider->handleKey(Key::Right);
        $this->assertSame(9, $slider->getResult());

        $slider->handleKey(Key::Right);
        $this->assertSame(10, $slider->getResult());

        $slider->handleKey(Key::Left);
        $this->assertSame(8, $slider->getResult());

        $slider->handleKey(Key::Left);
        $slider->handleKey(Key::Left);
        $slider->handleKey(Key::Left);
        $slider->handleKey(Key::Left);
        $slider->handleKey(Key::Left);
        $this->assertSame(0, $slider->getResult());
    }

    public function testHomeAndEndKeys() : void
    {
        $slider = new Slider('Volume', 0, 100, default: 50);

        $slider->handleKey(Key::Home);
        $this->assertSame(0, $slider->getResult());

        $slider->handleKey(Key::End);
        $this->assertSame(100, $slider->getResult());
    }

    public function testEnterKeyCompletes() : void
    {
        $slider = new Slider('Quality', 0, 10);
        $this->assertFalse($slider->isDone());

        $slider->handleKey(Key::Enter);
        $this->assertTrue($slider->isDone());
    }

    public function testQuickDigitInputAndBackspace() : void
    {
        $slider = new Slider('Brightness', 0, 100, default: 0);

        $slider->handleKey('4');
        $this->assertSame(4, $slider->getResult());

        $slider->handleKey('2');
        $this->assertSame(42, $slider->getResult());

        $slider->handleKey(Key::Backspace);
        $this->assertSame(4, $slider->getResult());

        $slider->handleKey(Key::Backspace);
        $this->assertSame(4, $slider->getResult());

        // Further backspace on empty typed does nothing
        $slider->handleKey(Key::Backspace);
        $this->assertSame(4, $slider->getResult());
    }

    public function testRenderOutput() : void
    {
        $slider = new Slider('Volume', 0, 100, default: 50);
        $rendered = $slider->render();

        $this->assertStringContainsString('Volume', $rendered);
        $this->assertStringContainsString('50 / 100', $rendered);
        $this->assertStringContainsString('← → to change the value', $rendered);
        $this->assertStringContainsString('Or type a digit for quick input', $rendered);
    }

    public function testRenderWhenMinEqualsMax() : void
    {
        $slider = new Slider('Fixed', 10, 10);
        $rendered = $slider->render();

        $this->assertStringContainsString('Fixed', $rendered);
        $this->assertStringContainsString('10 / 10', $rendered);
    }
}
