<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

use ChernegaSergiy\TerminalUi\Component\Checkbox;
use ChernegaSergiy\TerminalUi\Component\MultiSelect;
use ChernegaSergiy\TerminalUi\Component\SearchSelect;
use ChernegaSergiy\TerminalUi\Component\Slider;
use ChernegaSergiy\TerminalUi\Component\TextInput;
use ChernegaSergiy\TerminalUi\Runner;
use ChernegaSergiy\TerminalUi\Terminal\SignalException;
use ChernegaSergiy\TerminalUi\Validator\CallbackValidator;

try {
    // Step 1: Project name
    $nameValidator = new CallbackValidator(static function (string $val): ?string {
        $trimmed = trim($val);
        if ('' === $trimmed) {
            return 'Project name cannot be empty';
        }
        if (!preg_match('/^[a-z0-9_-]+$/i', $trimmed)) {
            return 'Project name can only contain alphanumeric characters, dashes, and underscores';
        }
        return null;
    });

    $nameInput = new TextInput('Project name', placeholder: 'my-app', validator: $nameValidator);
    $projectName = Runner::run($nameInput);
    if (null === $projectName) {
        echo "\nCanceled.\n";
        exit(0);
    }

    // Step 2: Template
    $templates = [
        'laravel' => 'Laravel 11',
        'symfony' => 'Symfony 7',
        'slim'    => 'Slim 4',
        'spiral'  => 'Spiral Framework',
        'cli'     => 'CLI Application',
        'api'     => 'REST API Boilerplate',
    ];

    $templateSelect = new SearchSelect('Template', $templates, limit: 4);
    $selectedTemplate = Runner::run($templateSelect);
    if (null === $selectedTemplate) {
        echo "\nCanceled.\n";
        exit(0);
    }

    // Step 3: Modules
    $modules = [
        'pgsql'  => 'PostgreSQL database',
        'redis'  => 'Redis cache and queues',
        'docker' => 'Docker environment',
        'ci'     => 'GitHub Actions CI',
        'tests'  => 'PHPUnit testing suite',
    ];

    $moduleSelect = new MultiSelect('Modules (Space to select)', $modules, default: ['pgsql', 'docker']);
    $selectedModules = Runner::run($moduleSelect);
    if (null === $selectedModules) {
        echo "\nCanceled.\n";
        exit(0);
    }

    // Step 4: Background workers
    $workerSlider = new Slider('Background workers', 1, 16, 4, 1);
    $workers = Runner::run($workerSlider);
    if (null === $workers) {
        echo "\nCanceled.\n";
        exit(0);
    }

    // Step 5: Install dependencies
    $confirmCheck = new Checkbox('Install dependencies automatically?', true);
    $autoInstall = Runner::run($confirmCheck);
    if (null === $autoInstall) {
        echo "\nCanceled.\n";
        exit(0);
    }

    echo "\nConfiguration summary:\n";
    echo "  project:      {$projectName}\n";
    echo "  template:     " . ($templates[$selectedTemplate] ?? (string) $selectedTemplate) . "\n";
    echo "  modules:      " . (empty($selectedModules) ? 'none' : implode(', ', $selectedModules)) . "\n";
    echo "  workers:      {$workers}\n";
    echo "  dependencies: " . ($autoInstall ? 'install' : 'skip') . "\n";
} catch (SignalException $e) {
    echo "\n";
    exit($e->getCode());
}

