<?php
echo '<h2>Clearing Laravel Cache...</h2>';

// Clear bootstrap/cache
$cacheDir = __DIR__ . '/bootstrap/cache';
if (is_dir($cacheDir)) {
    $files = glob($cacheDir . '/*');
    foreach ($files as $f) {
        if (basename($f) !== '.gitkeep') {
            unlink($f);
        }
    }
    echo '<p>✓ bootstrap/cache cleaned</p>';
}

// Clear storage/framework/views
$viewsDir = __DIR__ . '/storage/framework/views';
if (is_dir($viewsDir)) {
    $files = glob($viewsDir . '/*');
    foreach ($files as $f) {
        if (basename($f) !== '.gitignore') {
            unlink($f);
        }
    }
    echo '<p>✓ storage/framework/views cleaned</p>';
}

// Clear storage/framework/cache/data
$cacheDataDir = __DIR__ . '/storage/framework/cache/data';
if (is_dir($cacheDataDir)) {
    $files = glob($cacheDataDir . '/*');
    foreach ($files as $f) {
        if (basename($f) !== '.gitignore') {
            unlink($f);
        }
    }
    echo '<p>✓ storage/framework/cache/data cleaned</p>';
}

echo '<h3 style="color:green">Done! 500 error should be fixed.</h3>';
echo '<p>Delete this clear.php file now for security.</p>';
