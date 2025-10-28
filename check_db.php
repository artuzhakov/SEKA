<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

echo "=== Checking users table structure ===\n";

// Проверяем колонки
$columns = Schema::getColumnListing('users');
echo "Columns in users table: " . implode(', ', $columns) . "\n";

// Проверяем конкретно avatar
if (in_array('avatar', $columns)) {
    $avatarInfo = DB::select("SHOW COLUMNS FROM users WHERE Field = 'avatar'")[0];
    echo "Avatar column type: " . $avatarInfo->Type . "\n";
    echo "Avatar default: " . ($avatarInfo->Default ?? 'NULL') . "\n";
} else {
    echo "Avatar column does NOT exist\n";
}

echo "=====================================\n";