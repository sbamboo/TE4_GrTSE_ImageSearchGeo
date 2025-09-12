<?php
// This file creates reusable components for the website


//MARK: Should we instead move the JS to an observer? Use CSS background-swap or apply preload/lazy?
function echoProgImg(string $blurrySrc, string $fullSrc, string $alt = "", array $classes = [], ?string $id = null) {
    // Ensure special chars in alt text will be handled correctly
    $alt = htmlspecialchars($alt, ENT_QUOTES, 'UTF-8');
    // Echo the progressive image HTML
    $idStr = ($id != null) ? ('data-id="' . $id . '"') : '';
    echo '
    <img
        class="progressive-image ' . implode(' ', $classes) . '"
        src="' . $blurrySrc . '" 
        alt="' . $alt . '"
        ' . $idStr . '
        onload="{
            const full = new Image();
            full.src = \'' . $fullSrc . '\';
            full.decode().then(() => { this.src = full.src; });
        }"
    />
    ';

}

// Function to return the HTML for an image
function echoImageHTML(UnsplashAPIImage $image, $translateNonLatin = false, ?GTranslate $translator = null) {
    $displayUrl = $image->GetImageDisplayUrl();
    //$blurUrl = $image->GetImageThumbnailUrl();
    $blurUrl = $image->GetBlurAsImage(32); // width=32, height=auto (based on original aspect ratio)
    $geoNames = $image->GetGeoNames();
    $coords = $image->GetCoordinates();
    $id = $image->GetIdentifiers()["id"];
    $userLink = $image->GetUserInfo();
    $GMapsLink = $image->GetMostPreciseGMapsUrl();
    
    echo '<div class="image-container" data-id="' . $id . '">';
        echo '<div class="image">';
            echo $GMapsLink;
            echoProgImg($blurUrl, $displayUrl, "", [], $id);
            echo '<a href="' . $userLink["profile"]. '"> Photo taken by: ' . $userLink["username"]. '</a>';
        echo '</div>';
        echo '<div class="image-location-data">';

            foreach ($geoNames as $key => $text) {

                // If empty continue
                if (empty($text)) {
                    continue;
                }

                // name => place
                if ($key === 'name') {
                    $key = 'place';
                }

                $containsNonLatin = ($translateNonLatin && $translator) ? containsNonLatinLetters($text) : false;

                echo '<div class="location-text'; if ($containsNonLatin) { echo ' location-text-translated'; } echo '">';
                $didTranslate = false;
                if ($containsNonLatin) {
                    $translated = $translator->translate($text);
                    if ($translated) {
                        $didTranslate = true;
                        echo '<p> <span>' . ucfirst($key) . ': </span> <span>' . htmlspecialchars($translated, ENT_QUOTES, 'UTF-8') . '</span> </p>';
                        echo '<p class="text-info-smaller">(translated)</p>';
                        echo '<div class="location-text-original" style="display:none;">';
                            echo '<p>' . htmlspecialchars($text, ENT_QUOTES, 'UTF-8') . '</p>';
                        echo '</div>';
                    }
                }
                if (!$didTranslate) {
                    echo '<p> <span>' . ucfirst($key) . ': </span> <span>' . htmlspecialchars($text, ENT_QUOTES, 'UTF-8') . '</span> </p>';
                }
                echo '</div>';
            }

            // // Echo the location data HTML
            if (!empty($coords['latitude'])) {
                echo '<div class="location-text">';
                    echo '<p> <span>Latitude: </span> <span>' . htmlspecialchars($coords['latitude'], ENT_QUOTES, 'UTF-8') . '</span> </p>';
                echo '</div>';
            }
            if (!empty($coords['longitude'])) {
                echo '<div class="location-text">';
                    echo '<p> <span>Longitude: </span> <span>' . htmlspecialchars($coords['longitude'], ENT_QUOTES, 'UTF-8') . '</span> </p>';
                echo '</div>';
            }

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
