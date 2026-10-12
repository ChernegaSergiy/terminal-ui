<?php

declare(strict_types=1);

if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
} else {
    spl_autoload_register(static function (string $class) : void {
        $prefix = 'ChernegaSergiy\\TerminalUi\\';
        $baseDir = __DIR__ . '/../src/';
        $len = strlen($prefix);
        if (0 !== strncmp($prefix, $class, $len)) {
            return;
        }
        $relativeClass = substr($class, $len);
        $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
        if (file_exists($file)) {
            require $file;
        }
    });
}
