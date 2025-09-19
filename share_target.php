<?php
require_once 'session.php';

function extractSharedUrl(array $values): string
{
    foreach ($values as $value) {
        if (!is_string($value)) {
            continue;
        }

        $candidate = trim($value);
        if ($candidate === '') {
            continue;
        }

        if (isValidSharedUrl($candidate)) {
            return $candidate;
        }

        if (preg_match('/https?:\/\/[^\s<>"\']+/i', $candidate, $matches)) {
            $match = rtrim($matches[0], ".,;\"')>]");
            if (isValidSharedUrl($match)) {
                return $match;
            }
        }
    }

    return '';
}

$sharedUrl = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sharedUrl = extractSharedUrl([
        $_POST['url'] ?? '',
        $_POST['text'] ?? '',
        $_POST['title'] ?? '',
    ]);
} else {
    $sharedUrl = extractSharedUrl([
        $_GET['url'] ?? '',
        $_GET['text'] ?? '',
        $_GET['shared'] ?? '',
    ]);
}

if ($sharedUrl === '') {
    header('Location: panel.php');
    exit;
}

header('Location: panel.php?shared=' . rawurlencode($sharedUrl));
exit;
