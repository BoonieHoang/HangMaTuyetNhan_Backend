#!/bin/bash
# Exit immediately if a command exits with a non-zero status
set -e

echo "=================================================="
echo "      RUNNING RAILWAY PRE-DEPLOYMENT TASKS        "
echo "=================================================="

# 1. Diagnose Database Connection
echo "--> Diagnosing database connectivity..."
php -r '
try {
    // Manually load Laravel bootstrap/autoload to access env helper
    require __DIR__ . "/vendor/autoload.php";
    $app = require_once __DIR__ . "/bootstrap/app.php";
    $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

    $host = env("MYSQLHOST", env("DB_HOST", "127.0.0.1"));
    $port = env("MYSQLPORT", env("DB_PORT", "3307"));
    $db = env("MYSQLDATABASE", env("DB_DATABASE"));
    $user = env("MYSQLUSER", env("DB_USERNAME"));
    $pass = env("MYSQLPASSWORD", env("DB_PASSWORD"));

    echo "Diagnostic Details:\n";
    echo "  - Host: " . $host . "\n";
    echo "  - Port: " . $port . "\n";
    echo "  - Database: " . $db . "\n";
    echo "  - Username: " . $user . "\n";
    
    if ($host === "127.0.0.1" || $host === "localhost") {
        echo "WARNING: You are trying to connect to localhost (127.0.0.1) on Railway.\n";
        echo "Please make sure you have created a MySQL service in Railway and linked it to this service,\n";
        echo "or manually set MYSQLHOST/DB_HOST to your Railway database address.\n";
    }

    echo "Attempting raw PDO connection...\n";
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$db", $user, $pass, [
        PDO::ATTR_TIMEOUT => 5,
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    echo "SUCCESS: Raw database connection established!\n";
} catch (\Exception $e) {
    echo "ERROR: Database connection failed!\n";
    echo "Reason: " . $e->getMessage() . "\n";
}
'

# 2. Run migrations
echo "--> Running database migrations..."
php artisan migrate --seed --force

# 3. Clear cached configuration to ensure environment variables are read dynamically
echo "--> Clearing configuration cache..."
php artisan config:clear
php artisan route:clear
php artisan view:clear

# 4. Re-cache routes and views for production performance
echo "--> Caching routes and views..."
php artisan route:cache
php artisan view:cache

echo "=================================================="
echo "    PRE-DEPLOYMENT TASKS COMPLETED SUCCESSFULLY   "
echo "=================================================="