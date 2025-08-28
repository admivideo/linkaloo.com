<?php
// Placeholder for OAuth social login
$provider = $_GET['provider'] ?? '';
header('Content-Type: text/plain');
if(!$provider){
    echo "Proveedor no especificado";
    exit;
}
echo "Autenticación con $provider aún no implementada.";
