<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

use ChernegaSergiy\TerminalUi\Component\Menu;
use ChernegaSergiy\TerminalUi\Runner;
use ChernegaSergiy\TerminalUi\Terminal\Ansi;

$running = true;

while ($running) {
    $menu = new Menu('Головне меню панелі керування', [
        'status'   => '📊 Переглянути стан сервісів',
        'cache'    => '⚡ Очистити та прогріти кеш',
        'database' => '🗄️ Перевірити з\'єднання з базою даних',
        'about'    => 'ℹ️ Інформація про бібліотеку TerminalUI',
        'exit'     => '🚪 Вийти',
    ]);

    $selected = Runner::run($menu);

    if (null === $selected || 'exit' === $selected) {
        echo Ansi::style(Ansi::GRAY, "\nРоботу завершено. До зустрічі!\n");
        break;
    }

    echo "\n";
    match ($selected) {
        'status' => (function (): void {
            echo Ansi::style(Ansi::BOLD_GREEN, "✔ Усі сервіси працюють штатно:\n");
            echo "  - Web Server: " . Ansi::style(Ansi::GREEN, "ONLINE (порт 8080)\n");
            echo "  - Redis Cache: " . Ansi::style(Ansi::GREEN, "CONNECTED (ping 1.2ms)\n");
            echo "  - Worker queue: " . Ansi::style(Ansi::GREEN, "ACTIVE (0 pending tasks)\n");
        })(),
        'cache' => (function (): void {
            echo Ansi::style(Ansi::BOLD_YELLOW, "⚡ Кеш успішно скинуто та прогріто (142 ключі оновлено).\n");
        })(),
        'database' => (function (): void {
            echo Ansi::style(Ansi::BOLD_GREEN, "✔ З'єднання з PostgreSQL 16.2 встановлено успішно (latency: 0.8ms).\n");
        })(),
        'about' => (function (): void {
            echo Ansi::style(Ansi::BOLD_CYAN, "TerminalUI") . " — легковісна бібліотека інтерактивних TTY-компонентів для PHP.\n";
            echo "Автор: Serhii Cherneha (chernegasergiy3@gmail.com)\n";
        })(),
    };

    echo Ansi::style(Ansi::GRAY, "\nНатисніть Enter для повернення в меню...");
    fgets(STDIN);
    echo "\n";
}
