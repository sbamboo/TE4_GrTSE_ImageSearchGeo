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

// Function to return the HTML for an image
function echoImageHTML(UnsplashAPIImage $image) {
    $displayUrl = $image->GetImageDisplayUrl();
    $blurUrl = $image->GetImageThumbnailUrl();
    $location = $image->GetLocation();
    $coords = $image->GetCoordinates();
    
    echo '<div id="image-container">';
        echo '<div id="image">';
            echoProgImg($blurUrl, $displayUrl, "",'image');
        echo '</div>';
        echo '<div id="image-location-data">';
            echo $image->ToArray()['id'] . "<br>";
            if(!empty($location['country'])){echo $location['country'] . "<br>";}
            if(!empty($location['city'])){echo $location['city'] . "<br>";}
            if(!empty($location['name'])){echo $location['name'] . "<br>";}
            if(!empty($coords['latitude'])){echo $coords['latitude'] . "<br>";}
            if(!empty($coords['longitude'])){echo $coords['longitude'] . "<br>";}
        echo '</div>';
    echo '</div>';
}

// Return <select> with <option>s inside for the filter options
function echoFilter(array $options, $selected = null){
    $html = "<select name=\"orderBy\" id=\"order-by\">\n";
    foreach($options as $value => $label){
        $valueLC = strtolower($value);
        $isSelected = ($valueLC == strtolower($selected)) ? " selected" : "";
        $html .= "<option value=\"$valueLC\"$isSelected>$label</option>\n";
    }
    $html .= "</select>\n";
    echo $html;
}

?>
