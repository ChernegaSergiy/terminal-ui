<?php

declare(strict_types=1);

namespace ChernegaSergiy\TerminalUi\Terminal;

/**
 * Thrown when an interactive terminal session is interrupted by a POSIX signal.
 */
class SignalException extends \RuntimeException
{
    public function __construct(private int $signal)
    {
        parent::__construct(sprintf('Process interrupted by signal %d', $signal), 128 + $signal);
    }

    public function getSignal() : int
    {
        return $this->signal;
    }
}
