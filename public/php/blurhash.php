<?php
// Contains helpers for handling blur hashes
// Uses Extensions: GD

function blurHashToDataUrl(string $blurHash, int $width = 32, int $height = 32): string {
    // Internal helper functions
    $decode83 = function($str) {
        $chars = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz#$%*+,-.:;=?@[]^_{|}~';
        $value = 0;
        for ($i = 0; $i < strlen($str); $i++) {
            $value = $value * 83 + strpos($chars, $str[$i]);
        }
        return $value;
    };

    $srgbToLinear = function($value) {
        $v = $value / 255;
        return ($v <= 0.04045) ? $v / 12.92 : pow(($v + 0.055) / 1.055, 2.4);
    };

    $linearToSrgb = function($value) {
        $v = max(0, min(1, $value));
        return ($v <= 0.0031308) ? round($v * 12.92 * 255) : round((1.055 * pow($v, 1 / 2.4) - 0.055) * 255);
    };

    $signPow = function($val, $exp) {
        return pow(abs($val), $exp) * ($val < 0 ? -1 : 1);
    };

    // Decode size info
    $numY = intval($decode83($blurHash[0]) / 9) + 1;
    $numX = ($decode83($blurHash[0]) % 9) + 1;

    // Decode maximum value
    $quantMax = $decode83($blurHash[1]);
    $maxVal = ($quantMax + 1) / 166;

    // Decode DC (average color)
    $decodeDC = function($value) use ($linearToSrgb) {
        $r = $linearToSrgb(($value >> 16) & 255);
        $g = $linearToSrgb(($value >> 8) & 255);
        $b = $linearToSrgb($value & 255);
        return [$r, $g, $b];
    };

    // Decode AC (color variations)
    $decodeAC = function($value, $maxVal) use ($signPow, $linearToSrgb) {
        $quantR = intval($value / (19 * 19));
        $quantG = intval(($value / 19) % 19);
        $quantB = $value % 19;

        $r = $signPow(($quantR - 9) / 9, 2.0) * $maxVal;
        $g = $signPow(($quantG - 9) / 9, 2.0) * $maxVal;
        $b = $signPow(($quantB - 9) / 9, 2.0) * $maxVal;

        return [$linearToSrgb($r), $linearToSrgb($g), $linearToSrgb($b)];
    };

    $colors = [];
    $colors[] = $decodeDC($decode83(substr($blurHash, 2, 4)));

    for ($i = 1; $i < $numX * $numY; $i++) {
        $colors[] = $decodeAC($decode83(substr($blurHash, 4 + ($i - 1) * 2, 2)), $maxVal);
    }

    // Create image
    $img = imagecreatetruecolor($width, $height);

    for ($y = 0; $y < $height; $y++) {
        for ($x = 0; $x < $width; $x++) {
            $r = $g = $b = 0.0;
            for ($j = 0; $j < $numY; $j++) {
                for ($i = 0; $i < $numX; $i++) {
                    $basis = cos(pi() * $x * $i / $width) * cos(pi() * $y * $j / $height);
                    $idx = $i + $j * $numX;
                    $r += $colors[$idx][0] * $basis;
                    $g += $colors[$idx][1] * $basis;
                    $b += $colors[$idx][2] * $basis;
                }
            }
            $color = imagecolorallocate(
                $img,
                (int)round(max(0, min(255, $r))),
                (int)round(max(0, min(255, $g))),
                (int)round(max(0, min(255, $b)))
            );
            imagesetpixel($img, $x, $y, $color);
        }
    }

    ob_start();
    imagepng($img);
    $data = ob_get_clean();
    imagedestroy($img);

    return "data:image/png;base64," . base64_encode($data);
}
?>