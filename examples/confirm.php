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

    // 2. Localized confirmation with custom options
    $localizedConfirm = new Confirm(
        'Видалити тимчасові файли кешу?',
        default: false,
        yes: 'Так',
        no: 'Ні',
        instruction: 'Стрілки або т/н для вибору, Enter для підтвердження'
    );
    $deleteCache = Runner::run($localizedConfirm);

    echo "Result 2:\n";
    echo '  Видалити кеш: ' . ($deleteCache ? 'Так' : 'Ні') . "\n\n";
} catch (SignalException $e) {
    echo "\n";
    exit($e->getCode());
}
