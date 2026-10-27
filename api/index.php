<?php

// Suppress deprecation notices from newer PHP runtimes (e.g. PHP 8.5+)
error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);
ini_set('display_errors', '0');

// Initialize serverless storage directories in /tmp for Vercel
$storageDirs = [
    '/tmp/storage',
    '/tmp/storage/app',
    '/tmp/storage/app/public',
    '/tmp/storage/framework',
    '/tmp/storage/framework/cache',
    '/tmp/storage/framework/cache/data',
    '/tmp/storage/framework/sessions',
    '/tmp/storage/framework/views',
    '/tmp/storage/logs',
];

foreach ($storageDirs as $dir) {
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
}

// Copy SQLite database to /tmp for write access on Vercel serverless
$needsMigration = false;
if (!file_exists('/tmp/database.sqlite')) {
    if (file_exists(__DIR__ . '/../database/database.sqlite')) {
        @copy(__DIR__ . '/../database/database.sqlite', '/tmp/database.sqlite');
    } else {
        // Create empty SQLite file so migrations can run
        @touch('/tmp/database.sqlite');
        $needsMigration = true;
    }
}

// Forward Vercel request to Laravel's public entry point
require __DIR__ . '/../public/index.php';
