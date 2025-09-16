<?php
// This file creates reusable components for the website


//MARK: Should we instead move the JS to an observer? Use CSS background-swap or apply preload/lazy?
function echoProgImg(string $blurrySrc, string $fullSrc, string $alt = "", array $classes = [], ?string $id = null): void {
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

function echoImageDownloadBadge(string $slug, string $url): void {
    $proxyUrl = "endpoints/downloadProxy.php?url=" . urlencode($url) . "&filename=" . urlencode($slug) . "&filetype=png";

    $html = <<<EOF
<a class="image-photo-download grid-item-download-badge" 
   href="{$proxyUrl}" target="_blank">
    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" 
         viewBox="0 0 24 24" fill="none" stroke="currentColor" 
         stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
         <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
         <path d="M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2 -2v-2" />
         <path d="M7 11l5 5l5 -5" />
         <path d="M12 4l0 12" />
    </svg>
</a>
EOF;

    echo $html;
}

function echoLocationData(bool $autoFetchDetails, array $geoNames = [], array $coords = [], array $identifiers = [], $translateNonLatin = false, ?GTranslate $translator = null): void {
    echo '<div class="image-location-data">';

        // If not autoFetchDetails and $geoNames and $coords are empty we instead show button stub for JS to request with
        if (!$autoFetchDetails) { //MARK: Maybe to broad of a condition and should empty-check $geoNames and $coords

            echo '<div class="image-geonames-wrapper image-geonames-notfetched">';
                echo localize('<button class="img-fetch-geonames" data-id="' . $identifiers["id"] . '">%fetch.geo.data%</button>');
                echo '<p class="img-fetch-geonames-info text-info-smaller" data-id="' . $identifiers["id"] . '" style="display:none;"></p>';
            echo '</div>';

        } else {

            // Echo geonames
            echo '<div class="image-geonames-wrapper">';
                //// Iterate all geo names and translate if needed
                $translated = [];
                $didTranslateAny = false;

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
                    if ($containsNonLatin) {
                        $translated[$key] = [$text, $translator->translate($text)];
                        $didTranslateAny = true;
                    } else {
                        $translated[$key] = [$text, null];
                    }
                }

                //// Echo all the geonames using the translated text if any
                foreach ($translated as $key => [$text, $translatedText]) {
                    echo '<div class="location-text'; if ($translatedText !== null) { echo ' location-text-translated'; } echo '">';
                    if ($translatedText !== null) {
                        echo localize('<p> <span>' . ucfirst("%$key%") . ': </span> <span>' . htmlspecialchars($translatedText, ENT_QUOTES, 'UTF-8') . '</span> </p>');
                    }
                    else {
                        echo localize('<p> <span>' . ucfirst("%$key%") . ': </span> <span>' . htmlspecialchars($text, ENT_QUOTES, 'UTF-8') . '</span> </p>');
                    }
                    echo '</div>';
                }

                //// If any translation was done, show a note about it
                if ($didTranslateAny) {
                    echo localize('<p class="translated-geonames text-info-smaller" data-id="' . $identifiers["id"] . '">(%translated.place%)</p>');
                    echo '<div id="translated-geonames-' . $identifiers["id"] . '" class="translated-geonames-content" style="display:none;">';
                    // Echo the original texts here
                    foreach ($translated as $key => [$text, $translatedText]) {
                        if ($translatedText !== null) {
                            echo '<div class="location-text-original">';
                                echo localize('<p> <span>' . ucfirst("%$key%") . ': </span> <span>' . htmlspecialchars($text, ENT_QUOTES, 'UTF-8') . '</span> </p>');
                            echo '</div>';
                        }
                    }
                    echo '</div>';
                }
            //// End geonames container
            echo '</div>';

            // Echo the location data HTML
            if (!empty($coords['latitude'])) {
                echo '<div class="location-text">';
                    echo localize('<p> <span> %lat%: </span> <span>' . htmlspecialchars($coords['latitude'], ENT_QUOTES, 'UTF-8') . '</span> </p>');
                echo '</div>';
            }
            if (!empty($coords['longitude'])) {
                echo '<div class="location-text">';
                    echo localize('<p> <span> %lon%: </span> <span>' . htmlspecialchars($coords['longitude'], ENT_QUOTES, 'UTF-8') . '</span> </p>');
                echo '</div>';
            }

        }

    echo '</div>';
}

// Function to return the HTML for an image
function echoImageHTML(UnsplashAPIImage $image, bool $autoFetchDetails, $translateNonLatin = false, ?GTranslate $translator = null): void {
    $displayUrl = $image->GetImageDisplayUrl();
    //$blurUrl = $image->GetImageThumbnailUrl();
    $blurUrl = $image->GetBlurAsImage(32); // width=32, height=auto (based on original aspect ratio)
    $geoNames = $image->GetGeoNames();
    $coords = $image->GetCoordinates();
    $identifiers = $image->GetIdentifiers();
    $userLink = $image->GetUserInfo();
    $GMapsLink = $image->GetMostPreciseGMapsUrl();

    //$downloadUrl = $image->GetDownloadUrl();
    $downloadUrl = $image->GetRawUrl();
    
    echo '<div class="image-container" data-id="' . $identifiers["id"] . '">';
        echo '<div class="position-grid-container">';
            echo '<div class="image-layer-container">';
                echo '<div class="image" class="image-w-blur-bg" style="background-image: url(' . $blurUrl . '); background-size: cover;">';
                    echoProgImg($blurUrl, $displayUrl, "", [], $identifiers["id"]);
                echo '</div>';
                echoImageDownloadBadge($identifiers["slug"], $downloadUrl);
                //echo '<a class="image-photo-download grid-item-download-badge" href="' . $downloadUrl . '" download="' . $identifiers["slug"] . '"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2 -2v-2" /><path d="M7 11l5 5l5 -5" /><path d="M12 4l0 12" /></svg></a>';
                echo '<div class="image-photo-gmaps grid-item-badge"> <a class="no-link-style" href="' . $GMapsLink . '"> <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M9 11a3 3 0 1 0 6 0a3 3 0 0 0 -6 0" /><path d="M17.657 16.657l-4.243 4.243a2 2 0 0 1 -2.827 0l-4.244 -4.243a8 8 0 1 1 11.314 0z" /></svg></a> </div>';
                echo localize('<div class="image-photo-credit grid-item-text"> %img.credit.start% <a class="image-photo-credit-link" href="' . $userLink["profile"]. '">@' . $userLink["username"]. '</a> %img.credit.end%</div>');
            echo '</div>';
        echo '</div>';

        echoLocationData($autoFetchDetails, $geoNames, $coords, $identifiers, $translateNonLatin, $translator);
    echo '</div>';
}

function echoSearchResultGrid(array $images, int $pageNr, bool $autoFetchDetails, $translateNonLatin = false, ?GTranslate $translator = null): void {
    echo '<div class="images-page" data-page-nr="' . $pageNr . '">';
        echo '<div class="images-page-title hflex-space-out">';
            echo '<h3>' . localize('%search.page%') . ': ' . $pageNr . '</h3>'; // "Page: 2"
        echo '</div>';
        echo '<div class="images-page-container">';
            foreach ($images as $image) {
                echoImageHTML($image, $autoFetchDetails, $translateNonLatin, $translator);
            }
        echo '</div>';
    echo '</div>';
}

// Return <select> with <option>s inside for the filter options
function echoFilter(array $options, $selected = null): void {
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
