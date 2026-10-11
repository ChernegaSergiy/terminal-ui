<?php

declare(strict_types=1);

namespace ChernegaSergiy\TerminalUi\Terminal;

/**
 * The non-printable keys the library understands.
 *
 * Printable characters (letters, digits, the space bar) have no case here:
 * they are passed around as the plain one-character string that was typed.
 */
enum Key : string
{
    case Up = 'up';
    case Down = 'down';
    case Left = 'left';
    case Right = 'right';
    case Enter = 'enter';
    case Esc = 'esc';
    case Tab = 'tab';
    case Backspace = 'backspace';
    case Delete = 'delete';
    case Home = 'home';
    case End = 'end';
    case PageUp = 'page_up';
    case PageDown = 'page_down';
}
