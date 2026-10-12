<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

use ChernegaSergiy\TerminalUi\Component\Checkbox;
use ChernegaSergiy\TerminalUi\Component\Form;
use ChernegaSergiy\TerminalUi\Component\PasswordInput;
use ChernegaSergiy\TerminalUi\Component\TextInput;
use ChernegaSergiy\TerminalUi\Runner;
use ChernegaSergiy\TerminalUi\Terminal\SignalException;
use ChernegaSergiy\TerminalUi\Validator\CallbackValidator;

try {
    $emailValidator = new CallbackValidator(static function (string $val): ?string {
        if ('' === trim($val)) {
            return 'Email cannot be empty';
        }
        if (!filter_var($val, FILTER_VALIDATE_EMAIL)) {
            return 'Invalid email address';
        }
        return null;
    });

    $passwordValidator = new CallbackValidator(static function (string $val): ?string {
        if (mb_strlen($val) < 6) {
            return 'Password must be at least 6 characters long';
        }
        return null;
    });

    $loginForm = new Form('Login', [
        'email' => new TextInput('Email', placeholder: 'user@example.com', validator: $emailValidator),
        'password' => new PasswordInput('Password', placeholder: 'Enter password', validator: $passwordValidator),
        'remember' => new Checkbox('Remember session', true),
    ]);

    $results = Runner::run($loginForm);

    if (null === $results) {
        echo "\nCanceled.\n";
        exit(0);
    }

    echo "\nResult:\n";
    echo "  email:    " . (string) $results['email'] . "\n";
    echo "  password: " . str_repeat('*', mb_strlen((string) $results['password'])) . "\n";
    echo "  remember: " . ($results['remember'] ? 'yes' : 'no') . "\n";
} catch (SignalException $e) {
    echo "\n";
    exit($e->getCode());
}

