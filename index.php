<?php
session_start();
if(isset($_SESSION['user_id'])){
    header('Location: panel_de_control.php');
} else {
    header('Location: login.php');
}
exit;
