<?php

declare(strict_types=1);

namespace ChernegaSergiy\TerminalUi;

use ChernegaSergiy\TerminalUi\Component\Component;
use ChernegaSergiy\TerminalUi\Terminal\Terminal;

/**
 * Runs an interactive component, handling the main event loop.
 */
class Runner
{
    /**
     * Runs the given component until it is done.
     * @param Component $component The component to run.
     * @return mixed The result from the component.
     */
    public static function run(Component $component): mixed
    {
        Terminal::enableRawMode();
        Terminal::enterAlternativeScreen();
        Terminal::hideCursor();

        try {
            self::draw($component);

            while (!$component->isDone()) {
                $key = Terminal::readKey();
                $component->handleKey($key);
                self::draw($component);
            }
        } finally {
            // Leaves the alternative screen, so whatever was on the terminal
            // before the component started is shown again untouched.
            Terminal::restore();
        }

        return $component->getResult();
    }

    /**
     * Draws a single frame: every frame replaces the previous one.
     * The component only describes its lines; the runner owns the margin around them.
     */
    private static function draw(Component $component): void
    {
        $frame = "\n";
        foreach (explode("\n", $component->render()) as $line) {
            $frame .= ($line === '' ? '' : '  ' . $line) . "\n";
        }

        Terminal::clearScreen();
        echo $frame;
    }
}
