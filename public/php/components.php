<?php
// This file creates reusable components for the website

//MARK: Should we instead move the JS to an observer? Use CSS background-swap or apply preload/lazy?
function echoProgImg($blurrySrc, $fullSrc, $alt = "", $id="") {
    // Ensure special chars in alt text will be handled correctly
    $alt = htmlspecialchars($alt, ENT_QUOTES, 'UTF-8');
    // Echo the progressive image HTML
    echo '
    <img id="' . $id . '"
        src="' . $blurrySrc . '" 
        alt="' . $alt . '" 
        onload="{
            const full = new Image();
            full.src = \'' . $fullSrc . '\';
            full.decode().then(() => { this.src = full.src; });
        }"
    />
    ';

}


?>
