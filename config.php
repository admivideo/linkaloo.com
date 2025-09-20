<?php

if (!function_exists('envOrFail')) {
    /**
     * Retrieve an environment variable or throw when it's not available.
     */
    function envOrFail(string $key): string
    {
        $value = getenv($key);

        if ($value === false || $value === '') {
            throw new \RuntimeException(sprintf('Environment variable %s is not set.', $key));
        }

        return $value;
    }
}

// Database configuration for linkaloo
$host     = envOrFail('DB_HOST');
$dbname   = envOrFail('DB_NAME');
$username = envOrFail('DB_USERNAME');
$password = envOrFail('DB_PASSWORD');
$charset  = envOrFail('DB_CHARSET');

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
$googleClientId     = envOrFail('GOOGLE_CLIENT_ID');
$googleClientSecret = envOrFail('GOOGLE_CLIENT_SECRET');
$googleRedirectUri  = envOrFail('GOOGLE_REDIRECT_URI');

// reCAPTCHA v3 configuration
$recaptchaSiteKey   = envOrFail('RECAPTCHA_SITE_KEY');
$recaptchaSecretKey = envOrFail('RECAPTCHA_SECRET_KEY');
?>
