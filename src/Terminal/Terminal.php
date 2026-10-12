<?php

declare(strict_types=1);

namespace ChernegaSergiy\TerminalUi\Terminal;

/**
 * Provides utility functions for terminal interaction.
 */
class Terminal
{
    private static $stty_settings = null;
    private static bool $cursorHidden = false;
    private static bool $alternativeScreen = false;
    private static bool $restoreRegistered = false;
    private static $inputStream = null;

    /** How long to wait (in microseconds) for the rest of an escape sequence before reading ESC as the Esc key. */
    private const ESCAPE_TIMEOUT_US = 50000;

    /**
     * Checks whether the current input stream is attached to an interactive terminal.
     */
    public static function isInteractive() : bool
    {
        return 'Windows' !== PHP_OS_FAMILY && stream_isatty(self::getInputStream());
    }

    /**
     * Enables raw mode for terminal input if running in an interactive terminal.
     */
    public static function enableRawMode() : void
    {
        if (! self::isInteractive()) {
            return;
        }

        $settings = self::execStty('stty -g');
        if (is_string($settings) && '' !== trim($settings)) {
            self::registerRestoreHandlers();
            self::$stty_settings = $settings;
            self::execStty('stty -icanon -echo');
        }
    }

    /**
     * Restores everything this class has changed: input mode, cursor
     * visibility and the alternative screen. Safe to call several times.
     */
    public static function restore() : void
    {
        if (self::$alternativeScreen) {
            self::exitAlternativeScreen();
        }
        if (self::$cursorHidden) {
            self::showCursor();
        }
        self::disableRawMode();
    }

    /**
     * Makes sure the terminal is restored when the script exits through
     * exit(), a fatal error or a termination signal, none of which run
     * finally blocks.
     */
    private static function registerRestoreHandlers() : void
    {
        if (self::$restoreRegistered) {
            return;
        }
        self::$restoreRegistered = true;

        register_shutdown_function([self::class, 'restore']);

        if (function_exists('pcntl_signal') && function_exists('pcntl_async_signals')) {
            pcntl_async_signals(true);
            $handler = [self::class, 'handleSignal'];
            foreach ([SIGINT, SIGTERM, SIGHUP] as $signal) {
                pcntl_signal($signal, $handler);
            }
        }
    }

    public static function handleSignal(int $signal) : void
    {
        self::restore();
        throw new SignalException($signal);
    }

    /**
     * Disables raw mode for terminal input, restoring original settings.
     */
    public static function disableRawMode() : void
    {
        if (null !== self::$stty_settings) {
            self::execStty('stty ' . trim(self::$stty_settings));
            self::$stty_settings = null;
        }
    }

    private static function execStty(string $command) : ?string
    {
        $process = proc_open($command, [0 => self::getInputStream(), 1 => ['pipe', 'w'], 2 => ['null']], $pipes);
        $output = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        $status = proc_close($process);

        return 0 === $status ? $output : null;
    }

    /**
     * Reads a single key press from the terminal.
     *
     * Input is consumed byte by byte, so text that arrives in one piece (a paste, fast typing) is
     * returned key by key, escape sequences are never cut in the middle and multi-byte UTF-8
     * characters come back whole. Escape sequences the library does not know are skipped.
     *
     * @return Key|string        A Key case for arrows, Home, End, Delete, PageUp, PageDown, Enter, Esc, Tab and Backspace,
     *                           otherwise the typed character itself.
     * @throws \RuntimeException If STDIN is closed (EOF).
     */
    public static function readKey() : Key|string
    {
        while (true) {
            $byte = self::readByte();
            if (null === $byte) {
                throw new \RuntimeException('STDIN was closed while waiting for a key press.');
            }

            if ("\033" === $byte) {
                $key = self::readEscapeSequence();
                if (null !== $key) {
                    return $key;
                }
                continue; // An unknown sequence: wait for the next key.
            }

            if ("\n" === $byte || "\r" === $byte) {
                return Key::Enter;
            }
            if ("\x7f" === $byte || "\x08" === $byte) {
                return Key::Backspace;
            }
            if ("\t" === $byte) {
                return Key::Tab;
            }

            $code = ord($byte);
            if ($code >= 0x80 && $code < 0xC0) {
                continue; // A stray UTF-8 continuation byte is not a key.
            }
            if ($code >= 0xC0) {
                return $byte . self::readContinuationBytes($code >= 0xF0 ? 3 : ($code >= 0xE0 ? 2 : 1));
            }

            return $byte;
        }
    }

    /**
     * Reads the rest of an escape sequence after its leading ESC byte.
     *
     * @return Key|null The key it stands for; Key::Esc when ESC was pressed on its own; null for unknown sequences.
     */
    private static function readEscapeSequence() : ?Key
    {
        if (! self::inputPending()) {
            return Key::Esc;
        }

        $introducer = self::readByte();
        if ('O' === $introducer) {
            $final = self::inputPending() ? self::readByte() : null;
            return null === $final ? null : self::mapCsi('', $final);
        }
        if ('[' !== $introducer) {
            return null; // Alt+key and other sequences are not supported.
        }

        // A CSI sequence: parameter bytes, then one final byte in the range @ to ~.
        $params = '';
        while (self::inputPending()) {
            $byte = self::readByte();
            if (null === $byte) {
                return null;
            }
            if ($byte >= '@' && $byte <= '~') {
                return self::mapCsi($params, $byte);
            }
            $params .= $byte;
        }

        return null;
    }

    /**
     * Maps a CSI/SS3 sequence to a key. Modifier parameters (e.g. Ctrl in "ESC[1;5A") are ignored.
     */
    private static function mapCsi(string $params, string $final) : ?Key
    {
        return match ($final) {
            'A' => Key::Up,
            'B' => Key::Down,
            'C' => Key::Right,
            'D' => Key::Left,
            'H' => Key::Home,
            'F' => Key::End,
            '~' => match ((int) explode(';', $params)[0]) {
                1, 7 => Key::Home,
                4, 8 => Key::End,
                3 => Key::Delete,
                5 => Key::PageUp,
                6 => Key::PageDown,
                default => null,
            },
            default => null,
        };
    }

    /**
     * Reads $count UTF-8 continuation bytes; stops early when the input ends.
     */
    private static function readContinuationBytes(int $count) : string
    {
        $bytes = '';
        for ($i = 0; $i < $count; $i++) {
            $byte = self::readByte();
            if (null === $byte) {
                break;
            }
            $bytes .= $byte;
        }

        return $bytes;
    }

    /**
     * @param resource|null $stream
     */
    public static function setInputStream($stream) : void
    {
        self::$inputStream = $stream;
    }

    /**
     * @return resource
     */
    private static function getInputStream()
    {
        return self::$inputStream ?? STDIN;
    }

    /**
     * Reads one byte, blocking until it is available; null on EOF.
     *
     * The wait happens in select() rather than in fread(): PHP restarts an interrupted read after a
     * signal, so a blocking fread() would keep Ctrl+C waiting until the next key press, whereas
     * select() is interrupted at once and lets the handlers from registerRestoreHandlers() run.
     */
    private static function readByte() : ?string
    {
        while (true) {
            $stream = self::getInputStream();
            $read = [$stream];
            $write = null;
            $except = null;

            $ready = @stream_select($read, $write, $except, null);
            if ($ready > 0) {
                break;
            }
        }

        $byte = fread(self::getInputStream(), 1);

        return (false === $byte || '' === $byte) ? null : $byte;
    }

    /**
     * Whether more input follows within a short grace period. A real escape sequence arrives in one
     * piece, so a pause after ESC means the user pressed the Esc key itself.
     */
    private static function inputPending() : bool
    {
        $stream = self::getInputStream();
        $read = [$stream];
        $write = null;
        $except = null;

        return stream_select($read, $write, $except, 0, self::ESCAPE_TIMEOUT_US) > 0;
    }

    /**
     * Clears the terminal screen.
     */
    public static function clearScreen() : void
    {
        echo "\033[2J\033[H";
    }

    /**
     * Moves the terminal cursor to a specific position.
     *
     * @param int $row The row number (1-based).
     * @param int $col The column number (1-based).
     */
    public static function moveCursor(int $row, int $col) : void
    {
        echo "\033[{$row};{$col}H";
    }

    /**
     * Hides the terminal cursor.
     */
    public static function hideCursor() : void
    {
        echo "\033[?25l";
        self::$cursorHidden = true;
    }

    /**
     * Shows the terminal cursor.
     */
    public static function showCursor() : void
    {
        echo "\033[?25h";
        self::$cursorHidden = false;
    }

    /**
     * Enters the alternative terminal screen buffer.
     */
    public static function enterAlternativeScreen() : void
    {
        echo "\033[?1049h";
        self::$alternativeScreen = true;
    }

    /**
     * Exits the alternative terminal screen buffer.
     */
    public static function exitAlternativeScreen() : void
    {
        echo "\033[?1049l";
        self::$alternativeScreen = false;
    }
}
