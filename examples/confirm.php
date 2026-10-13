<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

use ChernegaSergiy\TerminalUi\Component\Confirm;
use ChernegaSergiy\TerminalUi\Runner;
use ChernegaSergiy\TerminalUi\Terminal\SignalException;

try {
    // 1. Standard Yes/No confirmation
    $confirm = new Confirm('Do you want to run database migrations?', default: true);
    $runMigrations = Runner::run($confirm);

    echo "\nResult 1:\n";
    echo '  Run migrations: ' . ($runMigrations ? 'Yes' : 'No') . "\n\n";

    // 2. Custom confirmation options
    $customConfirm = new Confirm(
        'Purge temporary cache files?',
        default: false,
        yes: 'Purge',
        no: 'Keep',
        instruction: 'Use ← → / p/k to select, Enter to confirm'
    );
    $purgeCache = Runner::run($customConfirm);

    echo "Result 2:\n";
    echo '  Purge cache: ' . ($purgeCache ? 'Purge' : 'Keep') . "\n\n";
} catch (SignalException $e) {
    echo "\n";
    exit($e->getCode());
}
