<?php
function getLocalFavicon($domain){
    if(empty($domain)){
        return '';
    }
    $base = preg_replace('/\.[^.]+$/', '', $domain);
    $dir = __DIR__ . '/local_favicons/' . $base;
    $path = $dir . '/favicon.png';
    $relative = '/local_favicons/' . $base . '/favicon.png';
    if(file_exists($path)){
        return $relative;
    }
    if(!is_dir($dir)){
        mkdir($dir, 0755, true);
    }
    $url = 'https://www.google.com/s2/favicons?domain=' . urlencode($domain) . '&sz=128';
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
        $img = @imagecreatefromstring($data);
        if($img){
            $resized = imagecreatetruecolor(25, 25);
            imagecolortransparent($resized, imagecolorallocatealpha($resized, 0, 0, 0, 127));
            imagealphablending($resized, false);
            imagesavealpha($resized, true);
            $width = imagesx($img);
            $height = imagesy($img);
            imagecopyresampled($resized, $img, 0, 0, 0, 0, 25, 25, $width, $height);
            imagepng($resized, $path);
            imagedestroy($img);
            imagedestroy($resized);
            return $relative;
        }
    }
    return $url;
}
?>
