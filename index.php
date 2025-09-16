<?php
require_once 'session.php';
$query = $_SERVER['QUERY_STRING'] ?? '';
$suffix = $query ? '?' . $query : '';
if(isset($_SESSION['user_id'])){
    header('Location: panel.php' . $suffix);
} else {
    header('Location: login.php' . $suffix);
}
exit;
