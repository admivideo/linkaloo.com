<?php
function saveImageFromUrl($url, $userId){
    if(empty($url)){
        return '';
    }
    $dir = __DIR__ . '/fichas/' . $userId;
    if(!is_dir($dir)){
        mkdir($dir, 0755, true);
    }
    $pathInfo = pathinfo(parse_url($url, PHP_URL_PATH));
    $ext = strtolower($pathInfo['extension'] ?? 'jpg');
    if(!in_array($ext, ['jpg','jpeg','png','gif','webp','bmp'])){
        $ext = 'jpg';
    }
    $filename = uniqid('img_') . '.' . $ext;
    $fullPath = $dir . '/' . $filename;
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; linkalooBot/1.0)',
        CURLOPT_TIMEOUT => 5,
    ]);
    $data = curl_exec($ch);
    curl_close($ch);
    if($data){
        file_put_contents($fullPath, $data);
        return '/fichas/' . $userId . '/' . $filename;
    }
    return '';
}
?>
