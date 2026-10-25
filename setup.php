<?php
/**
 * Installment Management System - Database Installer & Auto Setup
 */
header('Content-Type: text/html; charset=utf-8');

$host = '127.0.0.1';
$port = 3306;
$user = 'root';
$pass = '';
$dbname = 'installment_db';

try {
    $pdo = new PDO("mysql:host={$host};port={$port};charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
    ]);

    $sqlFile = __DIR__ . '/config/database.sql';
    if (!file_exists($sqlFile)) {
        die("SQL file not found at " . $sqlFile);
    }

    $sql = file_get_contents($sqlFile);
    
    // Execute SQL script
    $pdo->exec($sql);

    echo "<!DOCTYPE html>
    <html lang='en'>
    <head>
        <meta charset='UTF-8'>
        <title>Setup Successful - Installment Management System</title>
        <link rel='stylesheet' href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css'>
        <style>
            body { background: #f0f2f5; font-family: system-ui, -apple-system, sans-serif; display: flex; align-items: center; justify-content: center; min-height: 100vh; }
            .setup-card { background: #fff; padding: 35px; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.08); max-width: 650px; width: 100%; }
        </style>
    </head>
    <body>
        <div class='setup-card text-center'>
            <div class='text-success mb-3'>
                <svg width='64' height='64' fill='currentColor' class='bi bi-check-circle-fill' viewBox='0 0 16 16'>
                    <path d='M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zm-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z'/>
                </svg>
            </div>
            <h2 class='fw-bold'>Installation & Database Setup Completed!</h2>
            <p class='text-muted'>Installment Management System database <strong>{$dbname}</strong> was created and populated with complete sample records.</p>
            
            <div class='alert alert-info text-start my-4'>
                <h6 class='fw-bold mb-2'>Default Login Credentials:</h6>
                <div><strong>Username:</strong> <code>admin</code></div>
                <div><strong>Password:</strong> <code>admin123</code></div>
                <div class='mt-2 text-muted small'>Role: Super Administrator (Full Control)</div>
            </div>

            <a href='index.php' class='btn btn-primary btn-lg w-100 py-2'>Go to Login / Dashboard &rarr;</a>
        </div>
    </body>
    </html>";
} catch (PDOException $e) {
    echo "<h2 style='color:red;'>Database Setup Error</h2><pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
}
