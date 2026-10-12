<?php

declare(strict_types=1);

namespace Tests\Unit\Terminal;

use ChernegaSergiy\TerminalUi\Terminal\Key;
use ChernegaSergiy\TerminalUi\Terminal\Terminal;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;

final class TerminalTest extends TestCase
{
    protected function tearDown() : void
    {
        Terminal::restore();
        Terminal::setExitHandler(null);
        if (method_exists(Terminal::class, 'setInputStream')) {
            Terminal::setInputStream(null);
        }
    }

    public function testClearScreen() : void
    {
        ob_start();
        Terminal::clearScreen();
        $output = ob_get_clean();

        $this->assertSame("\033[2J\033[H", $output);
    }

    public function testMoveCursor() : void
    {
        ob_start();
        Terminal::moveCursor(5, 12);
        $output = ob_get_clean();

        $this->assertSame("\033[5;12H", $output);
    }

    public function testHideAndShowCursor() : void
    {
        ob_start();
        Terminal::hideCursor();
        $hideOutput = ob_get_clean();
        $this->assertSame("\033[?25l", $hideOutput);

        $ref = new ReflectionClass(Terminal::class);
        $cursorProp = $ref->getProperty('cursorHidden');
        $this->assertTrue($cursorProp->getValue());

        ob_start();
        Terminal::showCursor();
        $showOutput = ob_get_clean();
        $this->assertSame("\033[?25h", $showOutput);
        $this->assertFalse($cursorProp->getValue());
    }

    public function testEnterAndExitAlternativeScreen() : void
    {
        ob_start();
        Terminal::enterAlternativeScreen();
        $enterOutput = ob_get_clean();
        $this->assertSame("\033[?1049h", $enterOutput);

        $ref = new ReflectionClass(Terminal::class);
        $screenProp = $ref->getProperty('alternativeScreen');
        $this->assertTrue($screenProp->getValue());

        ob_start();
        Terminal::exitAlternativeScreen();
        $exitOutput = ob_get_clean();
        $this->assertSame("\033[?1049l", $exitOutput);
        $this->assertFalse($screenProp->getValue());
    }

    public function testRestoreResetsState() : void
    {
        ob_start();
        Terminal::enterAlternativeScreen();
        Terminal::hideCursor();
        ob_clean();

        Terminal::restore();
        $restoreOutput = ob_get_clean();

        $this->assertSame("\033[?1049l\033[?25h", $restoreOutput);

        $ref = new ReflectionClass(Terminal::class);
        $this->assertFalse($ref->getProperty('alternativeScreen')->getValue());
        $this->assertFalse($ref->getProperty('cursorHidden')->getValue());
    }

    public function testDisableRawModeWithSettings() : void
    {
        $ref = new ReflectionClass(Terminal::class);
        $settingsProp = $ref->getProperty('stty_settings');
        $settingsProp->setValue(null, 'sane');

        Terminal::disableRawMode();

        $this->assertNull($settingsProp->getValue());
    }

    public function testEnableRawModeThrowsWhenStdinIsNotInteractive() : void
    {
        [$reader, $writer] = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);
        Terminal::setInputStream($reader);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('STDIN is not an interactive terminal.');
        Terminal::enableRawMode();
    }

    public function testRegisterRestoreHandlersIsIdempotent() : void
    {
        $method = new ReflectionMethod(Terminal::class, 'registerRestoreHandlers');
        $method->invoke(null);
        $method->invoke(null);

        $ref = new ReflectionClass(Terminal::class);
        $this->assertTrue($ref->getProperty('restoreRegistered')->getValue());
    }

    public function testMapCsi() : void
    {
        $method = new ReflectionMethod(Terminal::class, 'mapCsi');

        $this->assertSame(Key::Up, $method->invoke(null, '', 'A'));
        $this->assertSame(Key::Down, $method->invoke(null, '', 'B'));
        $this->assertSame(Key::Right, $method->invoke(null, '', 'C'));
        $this->assertSame(Key::Left, $method->invoke(null, '', 'D'));
        $this->assertSame(Key::Home, $method->invoke(null, '', 'H'));
        $this->assertSame(Key::End, $method->invoke(null, '', 'F'));

        $this->assertSame(Key::Home, $method->invoke(null, '1', '~'));
        $this->assertSame(Key::Home, $method->invoke(null, '7', '~'));
        $this->assertSame(Key::End, $method->invoke(null, '4', '~'));
        $this->assertSame(Key::End, $method->invoke(null, '8', '~'));
        $this->assertSame(Key::Delete, $method->invoke(null, '3', '~'));
        $this->assertSame(Key::PageUp, $method->invoke(null, '5', '~'));
        $this->assertSame(Key::PageDown, $method->invoke(null, '6', '~'));
        $this->assertNull($method->invoke(null, '99', '~'));
        $this->assertNull($method->invoke(null, '', 'Z'));
    }

    public function testReadKeyFromStream() : void
    {
        [$reader, $writer] = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);
        Terminal::setInputStream($reader);

        fwrite($writer, "a\n\r\t\x7f\x08");
        fwrite($writer, "ї€😀");
        fwrite($writer, "\033");

        $this->assertSame('a', Terminal::readKey());
        $this->assertSame(Key::Enter, Terminal::readKey());
        $this->assertSame(Key::Enter, Terminal::readKey());
        $this->assertSame(Key::Tab, Terminal::readKey());
        $this->assertSame(Key::Backspace, Terminal::readKey());
        $this->assertSame(Key::Backspace, Terminal::readKey());

        $this->assertSame('ї', Terminal::readKey());
        $this->assertSame('€', Terminal::readKey());
        $this->assertSame('😀', Terminal::readKey());

        $this->assertSame(Key::Esc, Terminal::readKey());

        fwrite($writer, "\033[A\033[B\033[C\033[D\033[H\033[F");
        $this->assertSame(Key::Up, Terminal::readKey());
        $this->assertSame(Key::Down, Terminal::readKey());
        $this->assertSame(Key::Right, Terminal::readKey());
        $this->assertSame(Key::Left, Terminal::readKey());
        $this->assertSame(Key::Home, Terminal::readKey());
        $this->assertSame(Key::End, Terminal::readKey());

        fwrite($writer, "\033[3~\033[5~\033[6~");
        $this->assertSame(Key::Delete, Terminal::readKey());
        $this->assertSame(Key::PageUp, Terminal::readKey());
        $this->assertSame(Key::PageDown, Terminal::readKey());

        fwrite($writer, "\033OA");
        $this->assertSame(Key::Up, Terminal::readKey());

        fwrite($writer, "\033[99~b\033Xb\x80c");
        $this->assertSame('b', Terminal::readKey());
        $this->assertSame('b', Terminal::readKey());
        $this->assertSame('c', Terminal::readKey());

        fclose($writer);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('STDIN was closed while waiting for a key press.');
        Terminal::readKey();
    }

    public function testReadEscapeSequenceEofDuringCsi() : void
    {
        [$reader, $writer] = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);
        Terminal::setInputStream($reader);

        fwrite($writer, "[");
        fclose($writer);

        $method = new ReflectionMethod(Terminal::class, 'readEscapeSequence');
        $this->assertNull($method->invoke(null));
    }

    public function testReadEscapeSequenceTimeoutWithoutFinalByte() : void
    {
        [$reader, $writer] = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);
        Terminal::setInputStream($reader);

        fwrite($writer, "[1;2");

        $method = new ReflectionMethod(Terminal::class, 'readEscapeSequence');
        $this->assertNull($method->invoke(null));
    }

    public function testReadContinuationBytesEof() : void
    {
        [$reader, $writer] = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);
        Terminal::setInputStream($reader);

        fclose($writer);

        $method = new ReflectionMethod(Terminal::class, 'readContinuationBytes');
        $this->assertSame('', $method->invoke(null, 1));
    }

    public function testEnableRawModeThrowsOnWindows() : void
    {
        Terminal::setOsFamily('Windows');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Windows is not supported: reading single key presses requires stty.');
        Terminal::enableRawMode();
    }

    public function testEnableRawModeThrowsWhenSttySettingsUnavailable() : void
    {
        if (! stream_isatty(STDIN)) {
            $this->markTestSkipped('STDIN is not a TTY');
        }

        Terminal::setInputStream(null);
        Terminal::setSttyExecutor(static fn(string $cmd): string => '');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unable to read terminal settings with stty.');
        Terminal::enableRawMode();
    }

    public function testSignalHandlerRestoresAndExits() : void
    {
        $exitedCode = null;
        Terminal::setExitHandler(static function (int $code) use (&$exitedCode) : void {
            $exitedCode = $code;
        });

        Terminal::handleSignal(SIGINT);

        $this->assertSame(128 + SIGINT, $exitedCode);
    }

    public function testReadByteHandlesSignalInterruptionAndErrors() : void
    {
        [$reader, $writer] = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);
        fwrite($writer, 'q');
        Terminal::setInputStream($reader);

        $called = 0;
        Terminal::setStreamSelector(static function (array &$r, ?array &$w, ?array &$e) use (&$called) {
            if (0 === $called++) {
                @trigger_error('Interrupted system call', E_USER_WARNING);
                return false;
            }
            return @stream_select($r, $w, $e, 0);
        });

        $readByte = new ReflectionMethod(Terminal::class, 'readByte');
        $this->assertSame('q', $readByte->invoke(null));

        // Test non-interrupted select failure returns null
        Terminal::setStreamSelector(static function (array &$r, ?array &$w, ?array &$e) : bool {
            @trigger_error('Unrecoverable stream failure', E_USER_WARNING);
            return false;
        });

        $this->assertNull($readByte->invoke(null));
    }

    public function testEnableRawModeSuccessWhenTty() : void
    {
        if (! stream_isatty(STDIN)) {
            $this->markTestSkipped('STDIN is not a TTY');
        }

        Terminal::setInputStream(null);
        Terminal::enableRawMode();

        $ref = new ReflectionClass(Terminal::class);
        $settings = $ref->getProperty('stty_settings')->getValue();
        $this->assertNotEmpty($settings);

        Terminal::restore();
        $this->assertNull($ref->getProperty('stty_settings')->getValue());
    }
}
