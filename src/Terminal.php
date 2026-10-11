<?php

declare(strict_types=1);

namespace ChernegaSergiy\TerminalUi;

/**
 * Provides utility functions for terminal interaction.
 */
class Terminal
{
    private static $stty_settings = null;
    private static bool $cursorHidden = false;
    private static bool $alternativeScreen = false;
    private static bool $restoreRegistered = false;

    /**
     * Enables raw mode for terminal input.
     */
    public static function enableRawMode() : void
    {
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            return;
        }
        self::registerRestoreHandlers();
        self::$stty_settings = shell_exec('stty -g');
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
     * @return string The key pressed (e.g., 'up', 'down', 'enter', 'esc', or the character itself).
     * @throws \RuntimeException If STDIN is closed (EOF).
     */
    public static function readKey() : string
    {
        $key = fread(STDIN, 3);

        if ($key === false || $key === '') {
            throw new \RuntimeException('STDIN was closed while waiting for a key press.');
        }

        if ($key === "\033[A") return 'up';
        if ($key === "\033[B") return 'down';
        if ($key === "\033[C") return 'right';
        if ($key === "\033[D") return 'left';

        if ($key === "\n" || $key === "\r") return 'enter';

        if ($key === "\x7f" || $key === "\x08") return 'backspace';

        if ($key === "\033") return 'esc';

        if ($key === "\t") return 'tab';

        return $key;
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
