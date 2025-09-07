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
        $img = @imagecreatefromstring($data);
        if($img !== false){
            $width = imagesx($img);
            $height = imagesy($img);
            $final = $img;
            if($width > 300){
                $newHeight = (int)($height * 300 / $width);
                $resized = imagecreatetruecolor(300, $newHeight);
                if(in_array($ext, ['png','gif','webp'])){
                    imagecolortransparent($resized, imagecolorallocatealpha($resized, 0, 0, 0, 127));
                    imagealphablending($resized, false);
                    imagesavealpha($resized, true);
                }
                imagecopyresampled($resized, $img, 0, 0, 0, 0, 300, $newHeight, $width, $height);
                imagedestroy($img);
                $final = $resized;
            }
            imageresolution($final, 75, 75);
            switch($ext){
                case 'png':
                    imagepng($final, $fullPath);
                    break;
                case 'gif':
                    imagegif($final, $fullPath);
                    break;
                case 'webp':
                    imagewebp($final, $fullPath);
                    break;
                case 'bmp':
                    imagebmp($final, $fullPath);
                    break;
                default:
                    imagejpeg($final, $fullPath, 85);
                    break;
            }
            imagedestroy($final);
        } else {
            file_put_contents($fullPath, $data);
        }
        return '/fichas/' . $userId . '/' . $filename;
    }

    return '';
}
?>
