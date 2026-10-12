<?php

declare(strict_types=1);

namespace Tests\Unit\Terminal;

use ChernegaSergiy\TerminalUi\Terminal\Ansi;
use PHPUnit\Framework\TestCase;

final class AnsiTest extends TestCase
{
    protected function tearDown() : void
    {
        putenv('NO_COLOR');
        Ansi::setEnabled(null);
    }

    public function testConstants() : void
    {
        $this->assertSame('0', Ansi::RESET);
        $this->assertSame('1', Ansi::BOLD);
        $this->assertSame('2', Ansi::DIM);
        $this->assertSame('3', Ansi::ITALIC);
        $this->assertSame('4', Ansi::UNDERLINE);
        $this->assertSame('7', Ansi::INVERSE);

        $this->assertSame('30', Ansi::BLACK);
        $this->assertSame('31', Ansi::RED);
        $this->assertSame('32', Ansi::GREEN);
        $this->assertSame('33', Ansi::YELLOW);
        $this->assertSame('34', Ansi::BLUE);
        $this->assertSame('35', Ansi::MAGENTA);
        $this->assertSame('36', Ansi::CYAN);
        $this->assertSame('37', Ansi::WHITE);
        $this->assertSame('90', Ansi::GRAY);

        $this->assertSame('1;31', Ansi::BOLD_RED);
        $this->assertSame('1;32', Ansi::BOLD_GREEN);
        $this->assertSame('1;33', Ansi::BOLD_YELLOW);
        $this->assertSame('1;34', Ansi::BOLD_BLUE);
        $this->assertSame('1;35', Ansi::BOLD_MAGENTA);
        $this->assertSame('1;36', Ansi::BOLD_CYAN);
        $this->assertSame('1;37', Ansi::BOLD_WHITE);
    }

    public function testStyleWhenEnabled() : void
    {
        Ansi::setEnabled(true);
        $this->assertTrue(Ansi::isEnabled());

        $styled = Ansi::style(Ansi::BOLD_YELLOW, 'Hello');
        $this->assertSame("\033[1;33mHello\033[0m", $styled);
    }

    public function testStyleWhenDisabled() : void
    {
        Ansi::setEnabled(false);
        $this->assertFalse(Ansi::isEnabled());

        $styled = Ansi::style(Ansi::BOLD_YELLOW, 'Hello');
        $this->assertSame('Hello', $styled);
    }

    public function testDetectWithNoColorSet() : void
    {
        putenv('NO_COLOR=1');
        Ansi::setEnabled(null);

        $this->assertFalse(Ansi::isEnabled());
    }

    public function testDetectWithoutNoColor() : void
    {
        putenv('NO_COLOR');
        Ansi::setEnabled(null);

        $expected = defined('STDOUT') && stream_isatty(STDOUT);
        $this->assertSame($expected, Ansi::isEnabled());
    }
}
