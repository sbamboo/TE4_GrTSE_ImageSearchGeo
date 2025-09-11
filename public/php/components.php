<?php
// This file creates reusable components for the website

function echoProgImg($blurrySrc, $fullSrc, $alt = "") {
    // Ensure special chars in alt text will be handled correctly
    $alt = htmlspecialchars($alt, ENT_QUOTES, 'UTF-8');
    // Echo the progressive image HTML
    echo '
    <img 
        src="' . $blurrySrc . '" 
        alt="' . $alt . '" 
        onload="(function(img){
            const full = new Image();
            full.src = \'' . $fullSrc . '\';
            full.onload = () => { img.src = full.src; };
        })(this)"
    >
    ';
}


?>
