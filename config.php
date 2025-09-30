<?php
// Database configuration for linkaloo
$host     = '82.223.84.165';
$dbname   = 'smartlinks';
$username = 'smartuserIOn0s';
$password = 'WMCuxq@ts8s8g8^w';
$charset  = 'utf8mb4';

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
    PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4',
];

try {
    $dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";
    $pdo = new PDO($dsn, $username, $password, $options);
    $pdo->exec("SET NAMES 'utf8mb4'");
} catch (PDOException $e) {
    throw new PDOException($e->getMessage(), (int)$e->getCode());
}

// Google OAuth configuration
$googleClientId     = getenv('GOOGLE_CLIENT_ID') ?: '170566271159-0hsib0odo3gq4rdpno3aqhnnvgbe397s.apps.googleusercontent.com';
$googleClientSecret = getenv('GOOGLE_CLIENT_SECRET') ?: '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';
$googleRedirectUri  = getenv('GOOGLE_REDIRECT_URI') ?: 'https://linkaloo.com/oauth2callback.php';

// reCAPTCHA v3 configuration (set your keys in environment variables)
// Use environment variables `RECAPTCHA_SITE_KEY` and `RECAPTCHA_SECRET_KEY` or
// fall back to hard-coded keys if provided.
$recaptchaSiteKey   = getenv('RECAPTCHA_SITE_KEY') ?: '6Lf8pckrAAAAAE5BqEQKcugNtA_34k6-ErygC4vB';
$recaptchaSecretKey = getenv('RECAPTCHA_SECRET_KEY') ?: '6Lf8pckrAAAAAGdoqnT9mw0PwMzBB9VIuKuxsN-_';
?>
