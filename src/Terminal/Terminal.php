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

    /** How long to wait (in microseconds) for the rest of an escape sequence before reading ESC as the Esc key. */
    private const ESCAPE_TIMEOUT_US = 50000;

    /**
     * Enables raw mode for terminal input.
     * @throws \RuntimeException On Windows, when STDIN is not a terminal, or when stty is unavailable.
     */
    public static function enableRawMode() : void
    {
        if (PHP_OS_FAMILY === 'Windows') {
            throw new \RuntimeException('Windows is not supported: reading single key presses requires stty.');
        }
        if (!stream_isatty(STDIN)) {
            throw new \RuntimeException('STDIN is not an interactive terminal.');
        }

        $settings = shell_exec('stty -g 2>/dev/null');
        if (!is_string($settings) || trim($settings) === '') {
            throw new \RuntimeException('Unable to read terminal settings with stty.');
        }

        self::registerRestoreHandlers();
        self::$stty_settings = $settings;
        shell_exec('stty -icanon -echo');
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
            $handler = static function (int $signal): void {
                self::restore();
                exit(128 + $signal);
            };
            foreach ([SIGINT, SIGTERM, SIGHUP] as $signal) {
                pcntl_signal($signal, $handler);
            }
        }
    }

    /**
     * Disables raw mode for terminal input, restoring original settings.
     */
    public static function disableRawMode() : void
    {
        if (self::$stty_settings !== null) {
            shell_exec('stty ' . trim(self::$stty_settings));
            self::$stty_settings = null;
        }
    }

    /**
     * Reads a single key press from the terminal.
     *
     * Input is consumed byte by byte, so text that arrives in one piece (a paste, fast typing) is
     * returned key by key, escape sequences are never cut in the middle and multi-byte UTF-8
     * characters come back whole. Escape sequences the library does not know are skipped.
     *
     * @return Key|string A Key case for arrows, Enter, Esc, Tab and Backspace, otherwise the typed character itself.
     * @throws \RuntimeException If STDIN is closed (EOF).
     */
    public static function readKey() : Key|string
    {
        while (true) {
            $byte = self::readByte();
            if ($byte === null) {
                throw new \RuntimeException('STDIN was closed while waiting for a key press.');
            }

            if ($byte === "\033") {
                $key = self::readEscapeSequence();
                if ($key !== null) {
                    return $key;
                }
                continue; // An unknown sequence: wait for the next key.
            }

            if ($byte === "\n" || $byte === "\r") return Key::Enter;
            if ($byte === "\x7f" || $byte === "\x08") return Key::Backspace;
            if ($byte === "\t") return Key::Tab;

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
     * @return Key|null The key it stands for; Key::Esc when ESC was pressed on its own; null for unknown sequences.
     */
    private static function readEscapeSequence() : ?Key
    {
        if (!self::inputPending()) {
            return Key::Esc;
        }

        $introducer = self::readByte();
        if ($introducer === 'O') {
            $final = self::inputPending() ? self::readByte() : null;
            return $final === null ? null : self::mapCsi('', $final);
        }
        if ($introducer !== '[') {
            return null; // Alt+key and other sequences are not supported.
        }

        // A CSI sequence: parameter bytes, then one final byte in the range @ to ~.
        $params = '';
        while (self::inputPending()) {
            $byte = self::readByte();
            if ($byte === null) {
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
            if ($byte === null) {
                break;
            }
            $bytes .= $byte;
        }

        return $bytes;
    }

    /**
     * Reads one byte, blocking until it is available; null on EOF.
     */
    private static function readByte() : ?string
    {
        $byte = fread(STDIN, 1);

        return ($byte === false || $byte === '') ? null : $byte;
    }

    /**
     * Whether more input follows within a short grace period. A real escape sequence arrives in one
     * piece, so a pause after ESC means the user pressed the Esc key itself.
     */
    private static function inputPending() : bool
    {
        $read = [STDIN];
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
