<?php
// File containing functions for turning %placeholder% into localized strings based on language lists.

// Here we either provide a function with same signature as PHP "echo" but applies placeholders
// or provide a buffer function that applies placeholders post regular echos.
// The thing to note is "echo" is a language construct and not a function, so we can't get its non-parenthesis input style.

// Placeholders can be either from a folder or directly here as keyed arrays: $placeholders = ["<languageCode>" => ["<placeholder>" => "<localizedString>"]];

// Also provides just a string->string function (used by the echo/buffer function).

$toggleLanguage = isset($_REQUEST['toggleLanguage']);

$translations = [
    "en" => [
        "localstorage.prompt" => "Do you allow this website to store your settings in your browser's local storage?",
        "localstorage.accept" => "Accept",
        "localstorage.decline" => "Decline",

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
        "settings.embed-gmaps" => "Embed Google Maps",
        "settings.embed-gmaps.desc" => "Show google maps links in an embedded map instead of link",
        "settings.theme" => "Theme",
        "settings.theme.light" => "Light",
        "settings.theme.dark" => "Dark",
        "settings.theme.system" => "System",
        "settings.theme.desc" => "Choose theme mode, or follow system preference",
        "settings.highlight-tags" => "Highlight tags",
        "settings.highlight-tags.desc" => "Highlights tags found in previus searches from unsplash",

        "location.country" => "Country",
        "location.city" => "City",
        "location.place" => "Place",
        "location.lat" => "Latitude",
        "location.lon" => "Longitude",
        
        "img.credit.start" => "Photo taken by",
        "img.credit.end" => "from unsplash.",
        "img.translated" => "translated",
        "img.fetch-loc-btn.text" => "Get location data",

        "location.no-data" => "Photo has no geo data, filtered out"
    ],
    "sv" => [
        "localstorage.prompt" => "Tillåter du att denna webbplats lagrar dina inställningar i din webbläsares lokala lagring?",
        "localstorage.accept" => "Tillåt",
        "localstorage.decline" => "Neka",

        "search.title" => "Sök bild",
        "search.button-text" => "Sök",
        "search.sorting.relevance" => "Relevans",
        "search.sorting.latest" => "Senast",
        "search.page-nr.title" => "Sida",
        "search.next-page-btn.next" => "Se mer",

        "settings.button" => "Inställningar",
                
        "settings.autofetch" => "Hämta inte platsdata automatiskt",
        "settings.autofetch.desc" => "Avaktiverar att automatiskt hämta platsdata för bilder, sparar på hämtningar från Unsplah",
        "settings.filter-non-geo" => "Filtrera bort utan platsdata",
        "settings.filter-non-geo.desc" => "Filtrerar bort bilder som inte har platsdata",
        "settings.translate-non-latin" => "Översätt icke-latinskt",
        "settings.translate-non-latin.desc" => "Översätter iccke-latinska tecken i platsnamn",
        "settings.embed-gmaps" => "Bädda in Google Maps",
        "settings.embed-gmaps.desc" => "Visa google maps länkar i en inbäddad karta istället för länk",
        "settings.theme" => "Tema",
        "settings.theme.light" => "Ljust",
        "settings.theme.dark" => "Mörkt",
        "settings.theme.system" => "System",
        "settings.theme.desc" => "Välj temaläge, eller följ systeminställning",
        "settings.highlight-tags" => "Markera taggar",
        "settings.highlight-tags.desc" => "Markerar taggar som hittats i tidigare sökningar från unsplash",

        "location.country" => "Land",
        "location.city" => "Stad",
        "location.place" => "Plats",
        "location.lat" => "Latitud",
        "location.lon" => "Longitud",
        
        "img.credit.start" => "Bild tagen av",
        "img.credit.end" => "från unsplash.",
        "img.translated" => "översatt",
        "img.fetch-loc-btn.text" => "Hämta platsdata",

        "location.no-data" => "Bilden har ingen platsdata, utfiltrerad"
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