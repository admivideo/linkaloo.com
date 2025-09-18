<?php
require_once 'session.php';
$queryParams = $_GET;
$query = '';
if(!empty($queryParams)){
    $query = http_build_query($queryParams, '', '&', PHP_QUERY_RFC3986);
}
$suffix = $query !== '' ? '?' . $query : '';
if(isset($_SESSION['user_id'])){
    header('Location: panel.php' . $suffix);
} else {
    header('Location: login.php' . $suffix);
}
exit;
