<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

use ChernegaSergiy\TerminalUi\Component\Textarea;
use ChernegaSergiy\TerminalUi\Runner;
use ChernegaSergiy\TerminalUi\Terminal\SignalException;
use ChernegaSergiy\TerminalUi\Validator\CallbackValidator;

try {
    $validator = new CallbackValidator(static function (string $text) : ?string {
        return mb_strlen(trim($text)) < 10
            ? 'Description must be at least 10 characters long'
            : null;
    });

    $textarea = new Textarea(
        prompt: 'Enter detailed bug report or release notes:',
        default: "Summary:\n- First note\n- Second note",
        validator: $validator,
        placeholder: 'Type your multi-line notes here...',
        maxLength: 500,
        rows: 6,
        cols: 60,
        instruction: 'Enter for new line, Esc to submit'
    );

    $notes = Runner::run($textarea);

    echo "\nSubmitted content:\n";
    echo str_repeat('-', 40) . "\n";
    echo $notes . "\n";
    echo str_repeat('-', 40) . "\n";
} catch (SignalException $e) {
    echo "\n";
    exit($e->getCode());
}
