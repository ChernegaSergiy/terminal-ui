<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

use ChernegaSergiy\TerminalUi\Component\Checkbox;
use ChernegaSergiy\TerminalUi\Component\Form;
use ChernegaSergiy\TerminalUi\Component\PasswordInput;
use ChernegaSergiy\TerminalUi\Component\TextInput;
use ChernegaSergiy\TerminalUi\Runner;
use ChernegaSergiy\TerminalUi\Terminal\Ansi;
use ChernegaSergiy\TerminalUi\Validator\CallbackValidator;

$emailValidator = new CallbackValidator(static function (string $val): ?string {
    if ('' === trim($val)) {
        return 'Email не може бути порожнім';
    }
    if (!filter_var($val, FILTER_VALIDATE_EMAIL)) {
        return 'Введіть коректну адресу електронної пошти';
    }
    return null;
});

$passwordValidator = new CallbackValidator(static function (string $val): ?string {
    if (mb_strlen($val) < 6) {
        return 'Пароль повинен містити щонайменше 6 символів';
    }
    return null;
});

$loginForm = new Form('Вхід до облікового запису', [
    'email' => new TextInput('Електронна пошта', placeholder: 'user@example.com', validator: $emailValidator),
    'password' => new PasswordInput('Пароль', placeholder: 'Введіть пароль', validator: $passwordValidator),
    'remember' => new Checkbox('Запам\'ятати сесію на цьому пристрої', default: true),
]);

$results = Runner::run($loginForm);

if (null === $results) {
    echo Ansi::style(Ansi::YELLOW, "\nВхід скасовано користувачем.\n");
    exit(0);
}

echo "\n" . Ansi::style(Ansi::BOLD_GREEN, "✔ Успішна автентифікація!") . "\n";
echo "----------------------------------------\n";
echo "Email:       " . Ansi::style(Ansi::BOLD_CYAN, (string) $results['email']) . "\n";
echo "Пароль:      " . Ansi::style(Ansi::GRAY, str_repeat('•', mb_strlen((string) $results['password']))) . " (приховано)\n";
echo "Запам'ятати: " . Ansi::style(Ansi::BOLD_MAGENTA, $results['remember'] ? 'Так' : 'Ні') . "\n";
echo "----------------------------------------\n";
