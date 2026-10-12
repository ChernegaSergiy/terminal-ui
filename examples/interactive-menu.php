<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

use ChernegaSergiy\TerminalUi\Component\Menu;
use ChernegaSergiy\TerminalUi\Runner;

$menu = new Menu('Main Menu', [
    'System status',
    'Clear cache',
    'Check database connection',
    'About TerminalUI',
    'Exit',
]);

$selected = Runner::run($menu);

if (null === $selected || 4 === $selected) {
    echo "\nExited.\n";
    exit(0);
}

echo "\n";
match ($selected) {
    0 => (function (): void {
        echo "Services:\n";
        echo "  Web server: online (:8080)\n";
        echo "  Redis:      connected (1.2ms)\n";
        echo "  Workers:    active (0 pending)\n";
    })(),
    1 => (function (): void {
        echo "Cache cleared (142 keys purged).\n";
    })(),
    2 => (function (): void {
        echo "Database: connected to PostgreSQL 16.2 (0.8ms latency)\n";
    })(),
    3 => (function (): void {
        echo "TerminalUI - Lightweight terminal UI components for PHP\n";
        echo "Author: Serhii Cherneha <chernegasergiy3@gmail.com>\n";
    })(),
};
echo "\n";
