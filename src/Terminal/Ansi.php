<?php

declare(strict_types=1);

namespace ChernegaSergiy\TerminalUi\Terminal;

/**
 * Wraps text in ANSI colour and style codes, but only where they are wanted.
 *
 * Styling is skipped when the NO_COLOR environment variable is set to a
 * non-empty value (see https://no-color.org) or when STDOUT is not a terminal,
 * so escape codes do not leak into log files and pipes.
 */
final class Ansi
{
    public const RESET = '0';
    public const BOLD = '1';
    public const DIM = '2';
    public const ITALIC = '3';
    public const UNDERLINE = '4';
    public const INVERSE = '7';

    public const BLACK = '30';
    public const RED = '31';
    public const GREEN = '32';
    public const YELLOW = '33';
    public const BLUE = '34';
    public const MAGENTA = '35';
    public const CYAN = '36';
    public const WHITE = '37';
    public const GRAY = '90';

    public const BOLD_RED = '1;31';
    public const BOLD_GREEN = '1;32';
    public const BOLD_YELLOW = '1;33';
    public const BOLD_BLUE = '1;34';
    public const BOLD_MAGENTA = '1;35';
    public const BOLD_CYAN = '1;36';
    public const BOLD_WHITE = '1;37';

    private static ?bool $enabled = null;

    /**
     * Wraps $text in the given SGR codes (e.g. Ansi::BOLD_YELLOW) and resets the style afterwards.
     * Returns $text unchanged when styling is disabled.
     */
    public static function style(string $codes, string $text) : string
    {
        return self::isEnabled() ? "\033[{$codes}m{$text}\033[0m" : $text;
    }

    public static function isEnabled() : bool
    {
        return self::$enabled ??= self::detect();
    }

    /**
     * Forces styling on or off, or restores auto-detection when given null.
     */
    public static function setEnabled(?bool $enabled) : void
    {
        self::$enabled = $enabled;
    }

    private static function detect() : bool
    {
        $noColor = getenv('NO_COLOR');
        if (false !== $noColor && '' !== $noColor) {
            return false;
        }

        return defined('STDOUT') && stream_isatty(STDOUT);
    }
}
