<?php

declare(strict_types=1);

namespace ChernegaSergiy\TerminalUi;

/**
 * Runs an interactive component, handling the main event loop.
 */
class ComponentRunner
{
    /**
     * Runs the given component until it is done.
     * @param Component $component The component to run.
     * @return mixed The result from the component.
     */
    public static function run(Component $component): mixed
    {
        Terminal::enableRawMode();
        Terminal::hideCursor();

        try {
            // Initial render
            $component->render();

            while (!$component->isDone()) {
                $key = Terminal::readKey();
                $component->handleKey($key);
                $component->render();
            }
        } finally {
            Terminal::disableRawMode();
            Terminal::showCursor();
            Terminal::clearScreen();
        }

        return $component->getResult();
    }
}
