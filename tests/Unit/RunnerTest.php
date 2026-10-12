<?php

declare(strict_types=1);

namespace Tests\Unit;

use ChernegaSergiy\TerminalUi\Component\AbstractComponent;
use ChernegaSergiy\TerminalUi\Runner;
use ChernegaSergiy\TerminalUi\Terminal\Key;
use ChernegaSergiy\TerminalUi\Terminal\Terminal;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

final class RunnerTest extends TestCase
{
    protected function tearDown() : void
    {
        Terminal::restore();
        Terminal::setInputStream(null);
    }

    public function testRunExecutesLoopUntilComponentIsDone() : void
    {
        [$reader, $writer] = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);
        fwrite($writer, "x\n");

        Terminal::setInputStream($reader);

        $component = new class ('Test') extends AbstractComponent {
            private string $pressed = '';

            public function __construct(string $label)
            {
                parent::__construct($label);
            }

            public function render() : string
            {
                return "Prompt\n\nValue: {$this->pressed}";
            }

            public function handleKey(Key|string $key) : void
            {
                if ('x' === $key) {
                    $this->pressed .= 'x';
                } elseif (Key::Enter === $key) {
                    $this->done = true;
                }
            }

            public function getResult() : mixed
            {
                return $this->pressed;
            }
        };

        ob_start();
        $result = Runner::run($component);
        $output = ob_get_clean();

        $this->assertSame('x', $result);
        $this->assertNotEmpty($output);
        $this->assertTrue($component->isDone());
    }

    public function testDrawRendersFrameWithProperMargins() : void
    {
        $component = new class ('Draw Test') extends AbstractComponent {
            public function __construct(string $label)
            {
                parent::__construct($label);
            }

            public function render() : string
            {
                return "Title\n\nBody line";
            }

            public function handleKey(Key|string $key) : void {}

            public function getResult() : mixed
            {
                return null;
            }
        };

        $method = new ReflectionMethod(Runner::class, 'draw');

        ob_start();
        $method->invoke(null, $component);
        $output = ob_get_clean();

        $expectedFrame = "\033[2J\033[H\n  Title\n\n  Body line\n";
        $this->assertSame($expectedFrame, $output);
    }
}
