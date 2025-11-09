<?php
$data = json_decode(file_get_contents('php://input'), true);
file_put_contents('bluesky_bot_logs.txt', date('Y-m-d H:i:s') . " - " . json_encode($data) . PHP_EOL, FILE_APPEND);
?>
