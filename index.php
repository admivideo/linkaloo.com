<?php
require_once 'session.php';
if(isset($_SESSION['user_id'])){
    header('Location: panel.php');
} else {
    header('Location: login.php');
}
exit;
