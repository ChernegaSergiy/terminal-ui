<?php

declare(strict_types=1);

namespace Tests\Unit\Terminal;

use ChernegaSergiy\TerminalUi\Terminal\SignalException;
use PHPUnit\Framework\TestCase;

final class SignalExceptionTest extends TestCase
{
    public function testSignalExceptionProperties() : void
    {
        $exception = new SignalException(SIGINT);

        $this->assertSame(SIGINT, $exception->getSignal());
        $this->assertSame(128 + SIGINT, $exception->getCode());
        $this->assertSame(sprintf('Process interrupted by signal %d', SIGINT), $exception->getMessage());
    }
}
