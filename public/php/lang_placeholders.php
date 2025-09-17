<?php
// File containing functions for turning %placeholder% into localized strings based on language lists.

// Here we either provide a function with same signature as PHP "echo" but applies placeholders
// or provide a buffer function that applies placeholders post regular echos.
// The thing to note is "echo" is a language construct and not a function, so we can't get its non-parenthesis input style.

// Placeholders can be either from a folder or directly here as keyed arrays: $placeholders = ["<languageCode>" => ["<placeholder>" => "<localizedString>"]];

// Also provides just a string->string function (used by the echo/buffer function).

$toggleLanguage = isset($_POST['toggleLanguage']);

$translations = [
    "en" => [
        "search.title" => "Search image",
        "search.button-text" => "Search",
        "search.sorting.relevance" => "Relevance",
        "search.sorting.latest" => "Latest",
        "search.page-nr.title" => "Page",
        "search.next-page-btn.next" => "See more",

        "settings.button" => "Settings",

                
        "settings.autofetch" => "Don't auto fetch location",
        "settings.autofetch.desc" => "Disables automatically fetching location data for images, saves on requests to Unsplash",
        "settings.filter-non-geo" => "Filter without location data",
        "settings.filter-non-geo.desc" => "Filters out images that don't have location data",
        "settings.translate-non-latin" => "Translate non latin",
        "settings.translate-non-latin.desc" => "Translates non-latin characters in place names",
        
        "location.country" => "Country",
        "location.city" => "City",
        "location.place" => "Place",
        "location.lat" => "Latitude",
        "location.lon" => "Longitude",
        
        "img.credit.start" => "Photo taken by",
        "img.credit.end" => "from unsplash.",
        "img.translated" => "translated",
        "img.fetch-loc-btn.text" => "Get location data"
        // "autofetch" => "Get location data instantly",
        // "filter.non.geo" => "Filter without location data",
        // "translate.non.latin" => "Translate non latin characters",
        // "country" => "Country",
        // "city" => "City",
        // "place" => "Place",
        // "lat" => "Latitude",
        // "lon" => "Longitude",
        // "img.credit.start" => "Photo taken by",
        // "img.credit.end" => "from unsplash.",
        // "translated.place" => "translated",
        // "fetch.geo.data" => "Get location data"
    ],
    "sv" => [
        "search.image" => "Sök bild",
        "search.button" => "Sök",
        "autofetch" => "Hamta platsdata direkt",
        "filter.non.geo" => "Filtrera bort utan platsdata",
        "translate.non.latin" => "Översätt icke latinska tecken",
        "country" => "Land",
        "city" => "Stad",
        "place" => "Plats",
        "lat" => "Latitud",
        "lon" => "Longitud",
        "img.credit.start" => "Bild tagen av",
        "img.credit.end" => "från unsplash.",
        "translated.place" => "översatt",
        "fetch.geo.data" => "Hämta platsdata",
        "relevance" => "Relevans",
        "latest" => "Senast",
        "get.more.images" => "Se mer",
        "settings" => "Inställningar",
        "search.page" => "Sida"
    ] 
];

// replaces every %string% that is echoed with its corresponding value from the translations dictionary based on current language
function localize(string $string): string {
    global $translations, $toggleLanguage;
    $currentLang = $toggleLanguage ? 'sv' : 'en';

    return preg_replace_callback(
        '/%([^%]+)%/',
        function($matches) use ($translations, $currentLang){
            $key = $matches[1];
            return $translations[$currentLang][$key] ?? $matches[0]; // keep %...% if not found
        },
        $string
    );
}

?>