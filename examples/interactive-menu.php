<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

use ChernegaSergiy\TerminalUi\Component\Menu;
use ChernegaSergiy\TerminalUi\Runner;
use ChernegaSergiy\TerminalUi\Terminal\Ansi;

$menu = new Menu('Головне меню панелі керування', [
    '📊 Переглянути стан сервісів',
    '⚡ Очистити та прогріти кеш',
    '🗄️ Перевірити з\'єднання з базою даних',
    'ℹ️ Інформація про бібліотеку TerminalUI',
    '🚪 Вийти з програми',
]);

$selected = Runner::run($menu);

if (null === $selected || 4 === $selected) {
    echo Ansi::style(Ansi::GRAY, "\nВибір скасовано або обрано вихід.\n");
    exit(0);
}

echo "\n";
match ($selected) {
    0 => (function (): void {
        echo Ansi::style(Ansi::BOLD_GREEN, "✔ Усі сервіси працюють штатно:\n");
        echo "  - Web Server:   " . Ansi::style(Ansi::GREEN, "ONLINE (порт 8080)\n");
        echo "  - Redis Cache:  " . Ansi::style(Ansi::GREEN, "CONNECTED (ping 1.2ms)\n");
        echo "  - Worker queue: " . Ansi::style(Ansi::GREEN, "ACTIVE (0 pending tasks)\n");
    })(),
    1 => (function (): void {
        echo Ansi::style(Ansi::BOLD_YELLOW, "⚡ Кеш успішно скинуто та прогріто (142 ключі оновлено).\n");
    })(),
    2 => (function (): void {
        echo Ansi::style(Ansi::BOLD_GREEN, "✔ З'єднання з PostgreSQL 16.2 встановлено успішно (latency: 0.8ms).\n");
    })(),
    3 => (function (): void {
        echo Ansi::style(Ansi::BOLD_CYAN, "TerminalUI") . " — легковісна бібліотека інтерактивних TTY-компонентів для PHP.\n";
        echo "Автор: Serhii Cherneha (chernegasergiy3@gmail.com)\n";
    })(),
};
echo "\n";
