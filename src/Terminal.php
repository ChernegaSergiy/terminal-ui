<?php

declare(strict_types=1);

namespace ChernegaSergiy\TerminalUi;

/**
 * Provides utility functions for terminal interaction.
 */
class Terminal
{
    private static $stty_settings = null;

    /**
     * Enables raw mode for terminal input.
     */
    public static function enableRawMode() : void
    {
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            return;
        }
        self::$stty_settings = shell_exec('stty -g');
        shell_exec('stty -icanon -echo');
    }

    /**
     * Disables raw mode for terminal input, restoring original settings.
     */
    public static function disableRawMode() : void
    {
        if (self::$stty_settings !== null) {
            shell_exec('stty ' . self::$stty_settings);
        }
    }

    /**
     * Reads a single key press from the terminal.
     * @return string The key pressed (e.g., 'up', 'down', 'enter', 'esc', or the character itself).
     */
    public static function readKey() : string
    {
        $key = fread(STDIN, 3);

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
    }

    /**
     * Shows the terminal cursor.
     */
    public static function showCursor() : void
    {
        echo "\033[?25h";
    }

    /**
     * Enters the alternative terminal screen buffer.
     */
    public static function enterAlternativeScreen() : void
    {
        echo "\033[?1049h";
    }

    /**
     * Exits the alternative terminal screen buffer.
     */
    public static function exitAlternativeScreen() : void
    {
        echo "\033[?1049l";
    }
}
