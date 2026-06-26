<?php
// =====================================================
// Brain4 — cPanel Setup Script
// Visit this file ONCE in your browser, then DELETE it.
// =====================================================

echo "<pre>";

// Detect available shell function
$fn = null;
foreach (['shell_exec', 'exec', 'system', 'passthru'] as $f) {
    if (function_exists($f) && !in_array($f, explode(',', ini_get('disable_functions') ?? ''))) {
        $fn = $f;
        break;
    }
}

if (!$fn) {
    echo "ERROR: No shell execution function available. Contact your host to enable shell_exec or exec.\n";
    echo "Then run these commands manually in cPanel → Terminal:\n\n";
    echo "cd /home/rmqpvpsg/public_html\n";
    echo "composer install --no-dev\n";
    echo "cp .env.example .env\n";
    echo "php artisan key:generate\n";
    echo "php artisan migrate --force\n";
    echo "php artisan optimize\n";
    echo "chmod -R 775 storage bootstrap/cache\n";
    exit;
}

$run = function($cmd) use ($fn) {
    echo "> $cmd\n";
    if ($fn === 'exec') {
        $output = [];
        $rc = 0;
        exec($cmd . ' 2>&1', $output, $rc);
        echo implode("\n", $output) . "\n";
        echo "[exit code: $rc]\n";
        return $rc === 0;
    }
    if ($fn === 'system') {
        ob_flush(); flush();
        $rc = 0;
        system($cmd . ' 2>&1', $rc);
        echo "\n[exit code: $rc]\n";
        return $rc === 0;
    }
    if ($fn === 'passthru') {
        ob_flush(); flush();
        $rc = 0;
        passthru($cmd . ' 2>&1', $rc);
        echo "\n[exit code: $rc]\n";
        return $rc === 0;
    }
    // shell_exec
    $output = shell_exec($cmd . ' 2>&1');
    echo $output . "\n";
    return $output !== null;
};

echo "=== Brain4 cPanel Setup ===" . "\n\n";

// 1. Composer install
echo "--- Step 1/6: Installing Composer dependencies ---\n";
if (file_exists('composer.json')) {
    $run('composer install --no-dev --no-interaction --quiet');
} else {
    echo "composer.json not found. Are you in the right directory?\n";
    exit;
}

// 2. Copy .env
echo "\n--- Step 2/6: Creating .env file ---\n";
if (!file_exists('.env')) {
    if (file_exists('.env.example')) {
        copy('.env.example', '.env');
        echo ".env created from .env.example\n";
    } else {
        echo "ERROR: .env.example not found\n";
        exit;
    }
} else {
    echo ".env already exists, skipping\n";
}

// 3. Generate app key
echo "\n--- Step 3/6: Generating app key ---\n";
if (file_exists('artisan')) {
    $run('php artisan key:generate --force');
} else {
    echo "artisan not found\n";
}

// 4. Run migrations
echo "\n--- Step 4/6: Running migrations ---\n";
$run('php artisan migrate --force');

// 5. Optimize
echo "\n--- Step 5/6: Optimizing ---\n";
$run('php artisan optimize');

// 6. Permissions
echo "\n--- Step 6/6: Setting storage permissions ---\n";
$directories = ['storage', 'bootstrap/cache'];
foreach ($directories as $dir) {
    $path = __DIR__ . '/' . $dir;
    if (file_exists($path)) {
        @chmod($path, 0755);
        $it = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS)
        );
        foreach ($it as $f) {
            @chmod($f, $f->isDir() ? 0755 : 0644);
        }
        echo "Permissions set on $dir/\n";
    }
}

echo "\n=== Setup complete! ===\n";
echo "Next: Edit your .env file with your database credentials.\n";
echo "Then: Delete this setup.php file for security.\n";
echo "WARNING: Setup environment not yet configured.\n";

// Self-delete prompt
echo "\n--- Important ---\n";
echo "Delete this file now: rm setup.php\n";
