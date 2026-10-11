<?php

declare(strict_types=1);

/**
 * Automated test suite for chernegasergiy/terminal-ui.
 *
 * Runs comprehensive assertions on all components, ANSI styling,
 * Key enums, and Form composition without requiring an interactive TTY.
 *
 * Usage:
 *   php test.php
 *   php test.php --interactive (to launch an interactive demo)
 */

if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
} else {
    spl_autoload_register(function (string $class): void {
        $prefix = 'ChernegaSergiy\\TerminalUi\\';
        $baseDir = __DIR__ . '/src/';
        $len = strlen($prefix);
        if (strncmp($prefix, $class, $len) !== 0) {
            return;
        }
        $relativeClass = substr($class, $len);
        $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
        if (file_exists($file)) {
            require $file;
        }
    });
}

use ChernegaSergiy\TerminalUi\Component\Checkbox;
use ChernegaSergiy\TerminalUi\Component\Form;
use ChernegaSergiy\TerminalUi\Component\Menu;
use ChernegaSergiy\TerminalUi\Component\MultiSelect;
use ChernegaSergiy\TerminalUi\Component\NumberInput;
use ChernegaSergiy\TerminalUi\Component\Slider;
use ChernegaSergiy\TerminalUi\Component\TextInput;
use ChernegaSergiy\TerminalUi\Runner;
use ChernegaSergiy\TerminalUi\Terminal\Ansi;
use ChernegaSergiy\TerminalUi\Terminal\Key;
use ChernegaSergiy\TerminalUi\Validator\CallbackValidator;

// If interactive mode is requested, launch the demo
if (in_array('--interactive', $argv ?? [], true) || in_array('-i', $argv ?? [], true)) {
    runInteractiveDemo();
    exit(0);
}

// -----------------------------------------------------------------------------
// Test Runner Harness
// -----------------------------------------------------------------------------

class TestRunner
{
    private int $passed = 0;
    private int $failed = 0;
    private array $failures = [];

    public function assert(bool $condition, string $description): void
    {
        if ($condition) {
            $this->passed++;
            echo "\033[32m  ✓ \033[0m{$description}\n";
        } else {
            $this->failed++;
            $this->failures[] = $description;
            echo "\033[31m  ✗ \033[0m{$description}\n";
        }
    }

    public function assertSame(mixed $expected, mixed $actual, string $description): void
    {
        $match = ($expected === $actual);
        if (!$match) {
            $description .= sprintf(' (Expected: %s, Got: %s)', var_export($expected, true), var_export($actual, true));
        }
        $this->assert($match, $description);
    }

    public function section(string $title): void
    {
        echo "\n\033[1;36m▶ {$title}\033[0m\n";
    }

    public function summary(): bool
    {
        echo "\n" . str_repeat('─', 50) . "\n";
        $total = $this->passed + $this->failed;
        if ($this->failed === 0) {
            echo "\033[1;32mВСІ ТЕСТИ ПРОЙДЕНО! ({$this->passed}/{$total})\033[0m\n";
            return true;
        }

        echo "\033[1;31mТЕСТИ ПРОВАЛЕНО: {$this->failed} з {$total}\033[0m\n";
        foreach ($this->failures as $failure) {
            echo "  - {$failure}\n";
        }
        return false;
    }
}

$t = new TestRunner();

// -----------------------------------------------------------------------------
// 1. Terminal\Ansi Tests
// -----------------------------------------------------------------------------
$t->section('1. Terminal\\Ansi (Colors & Styling)');

Ansi::setEnabled(true);
$styled = Ansi::style(Ansi::BOLD_GREEN, 'Active');
$t->assert(str_contains($styled, "\033[1;32m") && str_contains($styled, "\033[0m"), 'Ansi::style wraps text in ANSI escape codes when enabled');

Ansi::setEnabled(false);
$plain = Ansi::style(Ansi::BOLD_GREEN, 'Active');
$t->assertSame('Active', $plain, 'Ansi::style returns plain text when disabled');
Ansi::setEnabled(null); // Restore automatic detection

// -----------------------------------------------------------------------------
// 2. Terminal\Key Enum Tests
// -----------------------------------------------------------------------------
$t->section('2. Terminal\\Key Enum');

$t->assertSame('up', Key::Up->value, 'Key::Up has correct value');
$t->assertSame('down', Key::Down->value, 'Key::Down has correct value');
$t->assertSame('left', Key::Left->value, 'Key::Left has correct value');
$t->assertSame('right', Key::Right->value, 'Key::Right has correct value');
$t->assertSame('enter', Key::Enter->value, 'Key::Enter has correct value');
$t->assertSame('esc', Key::Esc->value, 'Key::Esc has correct value');
$t->assertSame('home', Key::Home->value, 'Key::Home has correct value');
$t->assertSame('end', Key::End->value, 'Key::End has correct value');

// -----------------------------------------------------------------------------
// 3. Slider Component Tests
// -----------------------------------------------------------------------------
$t->section('3. Component\\Slider');

// Validation exceptions
$minMaxFailed = false;
try {
    new Slider('Test', 100, 10);
} catch (\InvalidArgumentException) {
    $minMaxFailed = true;
}
$t->assert($minMaxFailed, 'Slider constructor throws if min > max');

$stepFailed = false;
try {
    new Slider('Test', 0, 100, 50, 0);
} catch (\InvalidArgumentException) {
    $stepFailed = true;
}
$t->assert($stepFailed, 'Slider constructor throws if step < 1');

// Basic navigation & limits
$slider = new Slider('Brightness', 0, 100, 50, 5);
$t->assertSame(50, $slider->getResult(), 'Initial value matches default');
$t->assert(!$slider->isDone(), 'Slider starts not done');

$slider->handleKey(Key::Right);
$t->assertSame(55, $slider->getResult(), 'Key::Right increments by step');

$slider->handleKey(Key::Left);
$slider->handleKey(Key::Left);
$t->assertSame(45, $slider->getResult(), 'Key::Left decrements by step');

// Home and End keys
$slider->handleKey(Key::Home);
$t->assertSame(0, $slider->getResult(), 'Key::Home jumps to minimum');

$slider->handleKey(Key::Left);
$t->assertSame(0, $slider->getResult(), 'Value clamped at minimum');

$slider->handleKey(Key::End);
$t->assertSame(100, $slider->getResult(), 'Key::End jumps to maximum');

$slider->handleKey(Key::Right);
$t->assertSame(100, $slider->getResult(), 'Value clamped at maximum');

// Quick digit input
$slider->handleKey('7');
$slider->handleKey('5');
$t->assertSame(75, $slider->getResult(), 'Typing digits updates slider value directly');

// Enter confirms
$slider->handleKey(Key::Enter);
$t->assert($slider->isDone(), 'Key::Enter marks slider as done');
$t->assert(str_contains($slider->render(), 'Brightness'), 'Slider::render includes label');

// -----------------------------------------------------------------------------
// 4. Checkbox Component Tests
// -----------------------------------------------------------------------------
$t->section('4. Component\\Checkbox');

$checkbox = new Checkbox('Enable dark mode', false);
$t->assertSame(false, $checkbox->getResult(), 'Checkbox initial state is false');
$t->assert(str_contains($checkbox->render(), '[ ]'), 'Unchecked render displays [ ]');

$checkbox->handleKey(' ');
$t->assertSame(true, $checkbox->getResult(), 'Space toggles checkbox to true');
$t->assert(str_contains($checkbox->render(), '[x]'), 'Checked render displays [x]');

$checkbox->handleKey(' ');
$t->assertSame(false, $checkbox->getResult(), 'Space toggles checkbox back to false');

$checkbox->handleKey(Key::Enter);
$t->assert($checkbox->isDone(), 'Key::Enter marks checkbox as done');

// -----------------------------------------------------------------------------
// 5. Menu Component Tests
// -----------------------------------------------------------------------------
$t->section('5. Component\\Menu');

$menu = new Menu('Choose Theme', ['Light', 'Dark', 'Monokai', 'Solarized']);
$t->assertSame(0, $menu->getResult(), 'Menu initial selected index is 0');
$t->assert(!$menu->isDone(), 'Menu starts not done');

$menu->handleKey(Key::Down);
$t->assertSame(1, $menu->getResult(), 'Key::Down moves selection to next item');

$menu->handleKey(Key::End);
$t->assertSame(3, $menu->getResult(), 'Key::End jumps to last item');

$menu->handleKey(Key::Down);
$t->assertSame(3, $menu->getResult(), 'Key::Down clamped at last item');

$menu->handleKey(Key::Home);
$t->assertSame(0, $menu->getResult(), 'Key::Home jumps to first item');

$menu->handleKey(Key::Up);
$t->assertSame(0, $menu->getResult(), 'Key::Up clamped at first item');

$menu->handleKey(Key::Enter);
$t->assert($menu->isDone(), 'Key::Enter confirms selection and completes menu');
$t->assertSame(0, $menu->getResult(), 'Result is selected index');

// Cancellation with Esc
$cancelMenu = new Menu('Confirm Exit', ['Yes', 'No']);
$cancelMenu->handleKey(Key::Esc);
$t->assert($cancelMenu->isDone(), 'Key::Esc completes menu');
$t->assertSame(null, $cancelMenu->getResult(), 'Cancelled menu returns null');

// -----------------------------------------------------------------------------
// 6. NumberInput & Validator Tests
// -----------------------------------------------------------------------------
$t->section('6. Component\\NumberInput & Validator');

$validator = new CallbackValidator(function (string $val): ?string {
    if ($val === '') {
        return 'Обов\'язкове поле';
    }
    $num = (int) $val;
    if ($num < 18 || $num > 100) {
        return 'Вік повинен бути від 18 до 100';
    }
    return null;
});

$numInput = new NumberInput('Введіть вік (18-100)', $validator);
$t->assertSame(0, $numInput->getResult(), 'Empty input defaults to 0');
$t->assert(!$numInput->isValid(), 'Initially invalid due to empty value');

// Typing digits
$numInput->handleKey('1');
$numInput->handleKey('5');
$t->assertSame(15, $numInput->getResult(), 'Typing 1 and 5 sets value to 15');
$t->assert(!$numInput->isValid(), '15 is below 18, so isValid is false');

// Attempting to submit invalid input
$numInput->handleKey(Key::Enter);
$t->assert(!$numInput->isDone(), 'Key::Enter on invalid input does not finish');
$t->assert(str_contains($numInput->render(), 'Вік повинен бути від 18 до 100'), 'Error message displayed in render output');

// Backspace and correction
$numInput->handleKey(Key::Backspace);
$numInput->handleKey('8');
$t->assertSame(18, $numInput->getResult(), 'Corrected to 18');
$t->assert($numInput->isValid(), '18 passes validation');

$numInput->handleKey(Key::Enter);
$t->assert($numInput->isDone(), 'Key::Enter on valid input finishes');
$t->assertSame(18, $numInput->getResult(), 'Result is valid int');

// Max 3 digits check
$smallInput = new NumberInput('Код');
$smallInput->handleKey('9');
$smallInput->handleKey('9');
$smallInput->handleKey('9');
$smallInput->handleKey('9'); // 4th digit should be ignored
$t->assertSame(999, $smallInput->getResult(), 'NumberInput limits input to 3 digits');

// -----------------------------------------------------------------------------
// 7. TextInput Component Tests
// -----------------------------------------------------------------------------
$t->section('7. Component\\TextInput');

$textInput = new TextInput('Ім\'я користувача', 'Сергій');
$t->assertSame('Сергій', $textInput->getResult(), 'Default value initialized correctly');
$t->assertSame(6, $textInput->getCursorPosition(), 'Cursor positioned at end of default string');
$t->assert(!$textInput->isDone(), 'TextInput starts not done');

// Appending text and spaces
$textInput->handleKey(' ');
$textInput->handleKey('Ч');
$t->assertSame('Сергій Ч', $textInput->getResult(), 'Cyrillic character and space appended');
$t->assertSame(8, $textInput->getCursorPosition(), 'Cursor advanced after typing');

// Cursor navigation (Left, Right, Home, End)
$textInput->handleKey(Key::Left);
$t->assertSame(7, $textInput->getCursorPosition(), 'Key::Left moves cursor left');
$textInput->handleKey(Key::Home);
$t->assertSame(0, $textInput->getCursorPosition(), 'Key::Home jumps to start (0)');
$textInput->handleKey(Key::Right);
$t->assertSame(1, $textInput->getCursorPosition(), 'Key::Right moves cursor right');
$textInput->handleKey(Key::End);
$t->assertSame(8, $textInput->getCursorPosition(), 'Key::End jumps to end');

// Backspace & Delete
$textInput->handleKey(Key::Backspace);
$t->assertSame('Сергій ', $textInput->getResult(), 'Backspace removes char before cursor');
$textInput->handleKey(Key::Left);
$textInput->handleKey(Key::Delete);
$t->assertSame('Сергій', $textInput->getResult(), 'Delete removes char at cursor');

// Max length limit
$limitInput = new TextInput('Нік', maxLength: 4);
$limitInput->handleKey('t');
$limitInput->handleKey('e');
$limitInput->handleKey('s');
$limitInput->handleKey('t');
$limitInput->handleKey('!');
$t->assertSame('test', $limitInput->getResult(), 'TextInput respects maxLength limit');

// Validation and Enter
$emailValidator = new CallbackValidator(function (string $val): ?string {
    if ($val === '') {
        return 'Email є обов\'язковим';
    }
    if (!str_contains($val, '@')) {
        return 'Некоректний email';
    }
    return null;
});

$valEmail = new TextInput('Електронна пошта', '', $emailValidator, placeholder: 'name@example.com');
$valEmail->handleKey(Key::Enter);
$t->assert(!$valEmail->isDone(), 'Invalid empty input does not finish on Enter');
$t->assert(str_contains($valEmail->render(), 'Email є обов\'язковим'), 'Validator error shown in render');

$valEmail->handleKey('u');
$valEmail->handleKey('s');
$valEmail->handleKey('e');
$valEmail->handleKey('r');
$valEmail->handleKey('@');
$valEmail->handleKey('test.com');
$t->assert($valEmail->isValid(), 'Valid email passes validator');
$valEmail->handleKey(Key::Enter);
$t->assert($valEmail->isDone(), 'Valid input completes on Enter');
$t->assertSame('user@test.com', $valEmail->getResult(), 'Result is valid email string');

// -----------------------------------------------------------------------------
// 8. MultiSelect Component Tests
// -----------------------------------------------------------------------------
$t->section('8. Component\\MultiSelect');

$ms = new MultiSelect('Мови', ['php' => 'PHP', 'py' => 'Python', 'go' => 'Go'], default: ['php']);
$t->assertSame(['php'], $ms->getResult(), 'Default selection is php');
$t->assert(!$ms->isDone(), 'MultiSelect starts not done');

// Navigation
$ms->handleKey(Key::Down);
$t->assertSame(1, $ms->getCursor(), 'Cursor moves down');
$ms->handleKey(Key::End);
$t->assertSame(2, $ms->getCursor(), 'Cursor jumps to end');
$ms->handleKey(Key::Home);
$t->assertSame(0, $ms->getCursor(), 'Cursor jumps to home');

// Toggling with Space
$ms->handleKey(Key::Down); // cursor on 'py'
$ms->handleKey(' '); // toggle 'py'
$t->assertSame(['php', 'py'], $ms->getResult(), 'Space toggles item selection');
$t->assertSame(['php' => 'PHP', 'py' => 'Python'], $ms->getSelectedValues(), 'getSelectedValues returns associative selected map');

// Toggle all with 'a'
$ms->handleKey('a'); // some selected -> deselect all
$t->assertSame([], $ms->getResult(), 'Key a deselects all when some are selected');
$ms->handleKey('a'); // none selected -> select all
$t->assertSame(['php', 'py', 'go'], $ms->getResult(), 'Key a selects all when none are selected');

// Cancellation with Esc
$cancelMs = new MultiSelect('Пакет', ['a' => 'A', 'b' => 'B']);
$cancelMs->handleKey(Key::Esc);
$t->assert($cancelMs->isDone(), 'Esc completes multi-select');
$t->assertSame(null, $cancelMs->getResult(), 'Cancelled multi-select returns null');
$t->assert($cancelMs->isCancelled(), 'isCancelled returns true');

// Validation (min and max)
$validMs = new MultiSelect('Вибір', ['opt1' => 'One', 'opt2' => 'Two', 'opt3' => 'Three'], min: 2, max: 2);
$validMs->handleKey(Key::Enter); // 0 selected, min is 2 -> fails
$t->assert(!$validMs->isDone(), 'Fails on Enter when below min selections');
$t->assert(str_contains($validMs->render(), 'at least 2 item(s)'), 'Displays min error message');

$validMs->handleKey(' '); // select 'opt1'
$validMs->handleKey(Key::Down);
$validMs->handleKey(' '); // select 'opt2'
$validMs->handleKey(Key::Down);
$validMs->handleKey(' '); // select 'opt3' (3 selected, max is 2)
$validMs->handleKey(Key::Enter);
$t->assert(!$validMs->isDone(), 'Fails on Enter when above max selections');
$t->assert(str_contains($validMs->render(), 'at most 2 item(s)'), 'Displays max error message');

$validMs->handleKey(' '); // deselect 'opt3' (now 2 selected)
$validMs->handleKey(Key::Enter);
$t->assert($validMs->isDone(), 'Completes when selections satisfy min and max');
$t->assertSame(['opt1', 'opt2'], $validMs->getResult(), 'Result contains valid selections');

// -----------------------------------------------------------------------------
// 9. Form Component Tests
// -----------------------------------------------------------------------------
$t->section('9. Component\\Form (Composite Component)');


// Form constructor validation
$invalidFormKey = false;
try {
    new Form('Bad Form', [new Slider('A', 0, 10)]); // Unkeyed array
} catch (\InvalidArgumentException) {
    $invalidFormKey = true;
}
$t->assert($invalidFormKey, 'Form constructor throws if fields lack string keys');

// Building form
$form = new Form('User Profile');
$form->add('speed', new Slider('Speed', 1, 10, 5));
$form->add('active', new Checkbox('Active', true));

$dupFailed = false;
try {
    $form->add('speed', new Slider('Dup', 0, 5));
} catch (\InvalidArgumentException) {
    $dupFailed = true;
}
$t->assert($dupFailed, 'Form::add throws on duplicate field name');

// Form navigation and submission
$fSlider = new Slider('Volume', 0, 100, 20, 10);
$fCheck = new Checkbox('Mute', false);
$fNum = new NumberInput('Channel');

$testForm = new Form('Audio Settings', [
    'volume' => $fSlider,
    'mute' => $fCheck,
    'channel' => $fNum,
]);

$t->assert(!$testForm->isDone(), 'Form starts not done');

// Field 1: adjust volume
$testForm->handleKey(Key::Right); // volume = 30
$testForm->handleKey(Key::Enter); // advance to next field

// Field 2: toggle mute
$testForm->handleKey(' '); // mute = true
$testForm->handleKey(Key::Enter); // advance to next field

// Field 3: enter channel 7
$testForm->handleKey('7');
$testForm->handleKey(Key::Enter); // last field, submits form

$t->assert($testForm->isDone(), 'Form completes when last valid field is confirmed');

$expectedResult = [
    'volume' => 30,
    'mute' => true,
    'channel' => 7,
];
$t->assertSame($expectedResult, $testForm->getResult(), 'Form::getResult returns array keyed by field names');

// -----------------------------------------------------------------------------
// Summary
// -----------------------------------------------------------------------------
$success = $t->summary();
echo "\nПідказка: для запуску інтерактивного демо в терміналі виконайте:\n";
echo "  \033[33mphp test.php --interactive\033[0m\n\n";

exit($success ? 0 : 1);

// -----------------------------------------------------------------------------
// Interactive Demo Routine
// -----------------------------------------------------------------------------
function runInteractiveDemo(): void
{
    echo "Запуск інтерактивного тестування...\n";

    // 1. Interactive Menu Demo
    $menu = new Menu('Тест меню: Оберіть режим роботи', [
        'Швидкий тест слайдера та прапорця',
        'Тест множинного вибору (MultiSelect)',
        'Повна форма конфігурації (Form)',
        'Вийти з тесту',
    ]);

    $choice = Runner::run($menu);

    if ($choice === null || $choice === 3) {
        echo "Тестування завершено (скасовано користувачем).\n";
        return;
    }

    if ($choice === 0) {
        $slider = new Slider('Налаштування гучності', 0, 100, 40, 5);
        $volume = Runner::run($slider);
        echo "Обрана гучність: {$volume}\n\n";

        $checkbox = new Checkbox('Увімкнути турбо-режим', true);
        $turbo = Runner::run($checkbox);
        echo "Турбо-режим: " . ($turbo ? 'ТАК' : 'НІ') . "\n";
        return;
    }

    if ($choice === 1) {
        $multiSelect = new MultiSelect('Оберіть необхідні модулі для встановлення', [
            'db'    => 'База даних (PostgreSQL)',
            'cache' => 'Кеш (Redis)',
            'queue' => 'Черги повідомлень (RabbitMQ)',
            'auth'  => 'Модуль автентифікації (JWT)',
        ], default: ['db', 'auth'], min: 1);

        $selected = Runner::run($multiSelect);
        if ($selected === null) {
            echo "Вибір скасовано.\n";
        } else {
            echo "Обрані модулі: " . implode(', ', $selected) . "\n";
        }
        return;
    }

    // Full Form Demo
    $ageValidator = new CallbackValidator(function (string $val): ?string {
        if ($val === '') {
            return 'Вік є обов\'язковим';
        }
        $num = (int) $val;
        if ($num < 10 || $num > 99) {
            return 'Вік повинен бути від 10 до 99';
        }
        return null;
    });

    $form = new Form('Інтерактивна тестова форма бібліотеки TerminalUi', [
        'text_username' => new TextInput('Ім\'я користувача', placeholder: 'Введіть ваше ім\'я'),
        'slider_brightness' => new Slider('Яскравість екрана', 0, 100, 75, 5),
        'check_sound' => new Checkbox('Звукові ефекти', true),
        'number_age' => new NumberInput('Введіть вік (10-99)', $ageValidator),
    ]);

    $results = Runner::run($form);

    echo "\n=== РЕЗУЛЬТАТИ ТЕСТОВОЇ ФОРМИ ===\n";
    foreach ($results as $field => $val) {
        if (is_bool($val)) {
            $valFormatted = $val ? 'true' : 'false';
        } elseif (is_array($val)) {
            $valFormatted = '[' . implode(', ', $val) . ']';
        } else {
            $valFormatted = (string) $val;
        }
        echo "  [{$field}]: {$valFormatted}\n";
    }
    echo "=================================\n";
    echo "Інтерактивний тест успішно пройдено!\n";
}
