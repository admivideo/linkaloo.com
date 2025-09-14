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
$googleClientId     = getenv('GOOGLE_CLIENT_ID') ?: '731706222639-8ise1fcsud3mv3552bt7hrck3o7o6n31.apps.googleusercontent.com';
$googleClientSecret = getenv('GOOGLE_CLIENT_SECRET') ?: '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';
$googleRedirectUri  = getenv('GOOGLE_REDIRECT_URI') ?: 'https://linkaloo.com/oauth2callback.php';

// reCAPTCHA configuration (set your keys in environment variables)
$recaptchaSiteKey   = getenv('6Lf8pckrAAAAAE5BqEQKcugNtA_34k6-ErygC4vB') ?: '';
$recaptchaSecretKey = getenv('6Lf8pckrAAAAAGdoqnT9mw0PwMzBB9VIuKuxsN-_') ?: '';
?>
