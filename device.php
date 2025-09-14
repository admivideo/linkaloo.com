<?php
function isMobile(){
    return preg_match('/Mobile|Android|iP(hone|od|ad)|IEMobile|BlackBerry|Opera Mini/i',
        $_SERVER['HTTP_USER_AGENT'] ?? '');
}
?>
