<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

use ChernegaSergiy\TerminalUi\Component\Checkbox;
use ChernegaSergiy\TerminalUi\Component\MultiSelect;
use ChernegaSergiy\TerminalUi\Component\SearchSelect;
use ChernegaSergiy\TerminalUi\Component\Slider;
use ChernegaSergiy\TerminalUi\Component\TextInput;
use ChernegaSergiy\TerminalUi\Runner;
use ChernegaSergiy\TerminalUi\Terminal\Ansi;
use ChernegaSergiy\TerminalUi\Validator\CallbackValidator;

echo Ansi::style(Ansi::BOLD_CYAN, "=== Покроковий майстер ініціалізації проєкту ===\n\n");

// Крок 1: Назва проєкту
$nameValidator = new CallbackValidator(static function (string $val): ?string {
    $trimmed = trim($val);
    if ('' === $trimmed) {
        return 'Назва проєкту не може бути порожньою';
    }
    if (!preg_match('/^[a-z0-9_-]+$/i', $trimmed)) {
        return 'Назва проєкту може містити лише латинські літери, цифри, дефіс та підкреслення';
    }
    return null;
});

$nameInput = new TextInput('Крок 1/5: Введіть назву проєкту', placeholder: 'my-awesome-app', validator: $nameValidator);
$projectName = Runner::run($nameInput);
if (null === $projectName) {
    echo Ansi::style(Ansi::YELLOW, "\nМайстер налаштувань скасовано.\n");
    exit(0);
}

// Крок 2: Шаблон (SearchSelect)
$templates = [
    'laravel' => 'Laravel 11 (Повноцінний веб-фреймворк)',
    'symfony' => 'Symfony 7 (Гнучкий модульний фреймворк)',
    'slim'    => 'Slim 4 (Швидкий мікрофреймворк)',
    'spiral'  => 'Spiral Framework (Високопродуктивний GRPC/RoadRunner)',
    'cli'     => 'CLI Console App (Мінімалістична консольна утиліта)',
    'api'     => 'REST API Boilerplate (OpenAPI + JWT)',
];

$templateSelect = new SearchSelect('Крок 2/5: Оберіть шаблон проєкту', $templates, limit: 4);
$selectedTemplate = Runner::run($templateSelect);
if (null === $selectedTemplate) {
    echo Ansi::style(Ansi::YELLOW, "\nМайстер налаштувань скасовано.\n");
    exit(0);
}

// Крок 3: Модулі та інфраструктура (MultiSelect)
$modules = [
    'pgsql'  => 'База даних PostgreSQL',
    'redis'  => 'Кешування та черги Redis',
    'docker' => 'Docker Compose оточення',
    'ci'     => 'GitHub Actions CI/CD пайплайн',
    'tests'  => 'PHPUnit + Pest тестування',
];

$moduleSelect = new MultiSelect('Крок 3/5: Оберіть додаткові компоненти (Пробіл для вибору)', $modules, default: ['pgsql', 'docker']);
$selectedModules = Runner::run($moduleSelect);
if (null === $selectedModules) {
    echo Ansi::style(Ansi::YELLOW, "\nМайстер налаштувань скасовано.\n");
    exit(0);
}

// Крок 4: Кількість фонових воркерів (Slider)
$workerSlider = new Slider('Крок 4/5: Кількість фонових процесів (воркерів)', 1, 16, 4, 1);
$workers = Runner::run($workerSlider);
if (null === $workers) {
    echo Ansi::style(Ansi::YELLOW, "\nМайстер налаштувань скасовано.\n");
    exit(0);
}

// Крок 5: Підтвердження запуску (Checkbox)
$confirmCheck = new Checkbox('Крок 5/5: Запустити встановлення залежностей автоматично?', true);
$autoInstall = Runner::run($confirmCheck);
if (null === $autoInstall) {
    echo Ansi::style(Ansi::YELLOW, "\nМайстер налаштувань скасовано.\n");
    exit(0);
}

// Підсумок конфігурації
echo "\n" . Ansi::style(Ansi::BOLD_GREEN, "🎉 Конфігурацію успішно завершено!") . "\n";
echo "──────────────────────────────────────────────────\n";
echo "  • Проєкт:        " . Ansi::style(Ansi::BOLD_WHITE, $projectName) . "\n";
echo "  • Шаблон:        " . Ansi::style(Ansi::BOLD_CYAN, $templates[$selectedTemplate] ?? (string) $selectedTemplate) . "\n";
echo "  • Компоненти:    " . Ansi::style(Ansi::BOLD_YELLOW, empty($selectedModules) ? 'Не обрано' : implode(', ', $selectedModules)) . "\n";
echo "  • Воркери:       " . Ansi::style(Ansi::BOLD_MAGENTA, "{$workers} процесів") . "\n";
echo "  • Автоінсталяція:" . Ansi::style(Ansi::GREEN, $autoInstall ? ' ТАК' : ' НІ') . "\n";
echo "──────────────────────────────────────────────────\n";
echo Ansi::style(Ansi::GRAY, "Проєкт готовий до генерації.\n");
