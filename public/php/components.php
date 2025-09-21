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
        data-swapped="false"
        ' . $idStr . '
        data-fullsrc="' . $fullSrc . '" 
    />
    ';

}

function echoImageDownloadBadge(string $slug, string $url): void {
    $proxyUrl = "endpoints/download.php?url=" . urlencode($url) . "&filename=" . urlencode($slug) . "&filetype=png";

    $html = <<<EOF
<a class="image-photo-download grid-item-download-badge theme-icon" 
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

function formatCoordinate($value, $type) {
    // Determine direction
    if ($type === 'lat') {
        $direction = ($value >= 0) ? 'N' : 'S';
    } elseif ($type === 'lon') {
        $direction = ($value >= 0) ? 'E' : 'W';
    } else {
        return "Invalid type. Use 'lat' or 'lon'.";
    }

    // Make value positive for display
    $absValue = abs($value);

    $absValue = round($absValue, 4);

    return $absValue . "° " . $direction;
}

function echoLocationData(bool $autoFetchDetails, array $geoNames = [], array $coords = [], array $identifiers = [], $translateNonLatin = false, ?GTranslate $translator = null, array $tagWith = [], array $tags = []): void {
    $dataAttributes = '';
    foreach ($tagWith as $key => $value) {
        if($key === null || $value === null){
            continue;
        }
        $dataAttributes .= ' data-' . htmlspecialchars($key) . '="' . htmlspecialchars($value) . '"';
    }

    if ($autoFetchDetails) {
        // Get all tags returned by our search and output as meta comma joined
        $imageTags = [];
        if ($tags && count($tags) > 0) {
            foreach ($tags as $tag) {
                // Tags are {"type":"search","title":"tagname"} check if has title field and that is string if so append to array
                if (isset($tag['title']) && is_string($tag['title'])) {
                    $imageTags[] = $tag['title'];
                }
            }
        }
        if (count($imageTags) > 0) {
            $imgTagsStr = implode(',', $imageTags);
            $dataAttributes .= ' data-tags="' . htmlspecialchars($imgTagsStr, ENT_QUOTES, 'UTF-8') . '"';
        }
    }

    // If translation is needed, translate
    $translatedPlace = $geoNames['name'] ?? 'unknown place';
    if ($translateNonLatin && $translator && containsNonLatinLetters($geoNames['name'])) {
        $translatedPlace = $translator->translate($geoNames['name']);
    }
    //echo '<div class="image-location-data"' . $dataAttributes . " " . ' data-place="' . htmlspecialchars($translatedPlace, ENT_QUOTES, 'UTF-8') . '"' . ($autoFetchDetails ? ('data-lat="' .  $coords['latitude'] . '" data-lon="' . $coords['longitude']) : "") . '">';

    echo '<div class="image-location-data"' . $dataAttributes . ' data-place="' . $translatedPlace . '"' . ($autoFetchDetails ? ' data-lat="' . $coords['latitude'] . '" data-lon="' . $coords['longitude'] . '"' : '') . '>';
        // If not autoFetchDetails and $geoNames and $coords are empty we instead show button stub for JS to request with
        if (!$autoFetchDetails) { //MARK: Maybe to broad of a condition and should empty-check $geoNames and $coords

            echo '<div class="image-geonames-wrapper image-geonames-notfetched">';
                echo localize('<button class="img-fetch-geonames button" data-id="' . $identifiers["id"] . '">%img.fetch-loc-btn.text%</button>');
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
                    echo '<div class="location-text' . ($translatedText !== null ? ' location-text-translated' : "") . '">';
                    if ($translatedText !== null) {
                        echo localize('<p> <span>' . ucfirst("%location.$key%") . ': </span> <span>' . htmlspecialchars($translatedText, ENT_QUOTES, 'UTF-8') . '</span> </p>');
                    }
                    else {
                        echo localize('<p> <span>' . ucfirst("%location.$key%") . ': </span> <span>' . htmlspecialchars($text, ENT_QUOTES, 'UTF-8') . '</span> </p>');
                    }
                    echo '</div>';
                }

                //// If any translation was done, show a note about it
                if ($didTranslateAny) {
                    echo '<div>';
                        echo '<p>';
                            echo localize('<p class="translated-geonames text-info-smaller" data-id="' . $identifiers["id"] . '">(%img.translated%)</p>');
                            echo '<div id="translated-geonames-' . $identifiers["id"] . '" class="translated-geonames-content" style="display:none;">';
                            // Echo the original texts here
                            foreach ($translated as $key => [$text, $translatedText]) {
                                if ($translatedText !== null) {
                                    echo '<div class="location-text-original">';
                                        echo localize('<p> <span>' . ucfirst("%location.translated.$key%") . ': </span> <span>' . htmlspecialchars($text, ENT_QUOTES, 'UTF-8') . '</span> </p>');
                                    echo '</div>';
                                }
                            }
                            echo '</div>';
                        echo '</p>';
                    echo '</div>';
                }
            //// End geonames container
            echo '</div>';

            // Echo the location data HTML
            if (!empty($coords['latitude'])) {
                echo '<div class="location-text">';
                    echo localize('<p> <span> %location.lat%: </span> <span>' . htmlspecialchars(formatCoordinate($coords['latitude'], 'lat'), ENT_QUOTES, 'UTF-8') . '</span> </p>');
                echo '</div>';
            } else {
                echo '<div class="location-text">';
                    echo localize('<p> <span> %location.lat%: </span> <span class="latlon-unknown">%location.unknown%</span> </p>');
                echo '</div>';
            }
            if (!empty($coords['longitude'])) {
                echo '<div class="location-text">';
                    echo localize('<p> <span> %location.lon%: </span> <span>' . htmlspecialchars(formatCoordinate($coords['longitude'], 'lon'), ENT_QUOTES, 'UTF-8') . '</span> </p>');
                echo '</div>';
            } else {
                echo '<div class="location-text">';
                    echo localize('<p> <span> %location.lon%: </span> <span class="latlon-unknown">%location.unknown%</span> </p>');
                echo '</div>';
            }

            // Echo more metadata button
            echo '<div class="img-more-metadata-wrapper">';
                echo localize('<button class="img-more-metadata button" data-id="' . $identifiers["id"] . '">%img.view.metadata%</button>');
            echo '</div>';

        }
    echo '</div>';
}

// Function to return the HTML for an image
function echoImageHTML(UnsplashAPIImage $image, bool $autoFetchDetails, $translateNonLatin = false, ?GTranslate $translator = null, bool $embed = false): void {
    $embed = ($embed === true) ? $image->ParentHasGoogleKey() : false;
    $displayUrl = $image->GetImageDisplayUrl();
    //$blurUrl = $image->GetImageThumbnailUrl();
    $blurUrl = $image->GetBlurAsImage(32); // width=32, height=auto (based on original aspect ratio)
    $geoNames = $image->GetGeoNames();
    $coords = $image->GetCoordinates();
    $identifiers = $image->GetIdentifiers();
    $userLink = $image->GetUserInfo();
    $GMapsLink = $image->GetMostPreciseGMapsUrl($embed, $translator->GetTargetLang());
    $tags = $image->GetTags();
    $exif = $image->GetExif();
    // $exif = [
    //     'make' => $exifData['make'] ?? '',
    //     'model' => $exifData['model'] ?? '',
    //     'name' => $exifData['name'] ?? '',
    //     'exposure_time' => $exifData['exposure_time'] ?? '',
    //     'aperture' => $exifData['aperture'] ?? '',
    //     'focal_length' => $exifData['focal_length'] ?? '',
    //     'iso' => $exifData['iso'] ?? 0,
    //     'location' => [
    //         'latitude' => $exifData['location']['latitude'] ?? null,
    //         'longitude' => $exifData['location']['longitude'] ?? null,
    //         'altitude' => $exifData['location']['altitude'] ?? null
    //     ]
    // ]

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
                if($embed){
                    echo '<div class="image-photo-gmaps grid-item-badge"> <a class="embed-gmap-link no-link-style theme-icon" data-url="' . $GMapsLink . '"> <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M9 11a3 3 0 1 0 6 0a3 3 0 0 0 -6 0" /><path d="M17.657 16.657l-4.243 4.243a2 2 0 0 1 -2.827 0l-4.244 -4.243a8 8 0 1 1 11.314 0z" /></svg></a> </div>';   
                }
                else{
                    echo '<div class="image-photo-gmaps grid-item-badge"> <a class="image-photo-gmaps-link no-link-style theme-icon" href="' . $GMapsLink . '"> <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M9 11a3 3 0 1 0 6 0a3 3 0 0 0 -6 0" /><path d="M17.657 16.657l-4.243 4.243a2 2 0 0 1 -2.827 0l-4.244 -4.243a8 8 0 1 1 11.314 0z" /></svg></a> </div>';
                }
                echo localize('<div class="image-photo-credit grid-item-text"> %img.credit.start% <a class="image-photo-credit-link" href="' . $userLink["profile"]. '">@' . $userLink["username"]. '</a> %img.credit.end%</div>');
            echo '</div>';
        echo '</div>';


        echoLocationData($autoFetchDetails, $geoNames, $coords, $identifiers, $translateNonLatin, $translator, [
            "exif-make" => $exif['make'] ?? null,
            "exif-model" => $exif['model'] ?? null,
            "exif-name" => $exif['name'] ?? null,
            "exif-exposuretime" => $exif['exposure_time'] ?? null,
            "exif-aperture" => $exif['aperture'] ?? null,
            "exif-focallength" => $exif['focal_length'] ?? null,
            "exif-iso" => $exif['iso'] ?? null
        ], $tags);

    echo '</div>';
}

function echoSearchResultGrid(array $images, int $pageNr, bool $autoFetchDetails, $translateNonLatin = false, ?GTranslate $translator = null, bool $embed = false): void {
    echo '<div class="images-page" data-page-nr="' . $pageNr . '">';
        echo '<div class="images-page-title hflex-hcenter">';
            echo '<h3>' . localize('%search.page-nr.title%') . ': ' . $pageNr . '</h3>'; // "Page: 2"
        echo '</div>';
        echo '<div class="images-page-container">';
            foreach ($images as $image) {
                echoImageHTML($image, $autoFetchDetails, $translateNonLatin, $translator, $embed);          
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
