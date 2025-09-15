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
    "search.image" => "Search image",
    "search" => "Search",
    "autofetch" => "Search",
    "filter.non.geo" => "Search",
    "translate.non.latin" => "Search",
    "country" => "Country",
    "city" => "City",
    "place" => "Place",
    "lat" => "Latitude",
    "lon" => "Longitude",
    ],
    "sv" => [
        "search.image" => "Sök bild",
        "search" => "Sök",
        "autofetch" => "Search",
        "filter.non.geo" => "Search",
        "translate.non.latin" => "Search",
        "country" => "Land",
        "city" => "Stad",
        "place" => "Plats",
        "lat" => "Latitud",
        "lon" => "Longitud",
    ] 
];

// echo $toggleLanguage;
// var_dump($toggleLanguage);
// var_dump($_POST['toggleLanguage']);


function translateLanguage(string $key): string{
    global $translations, $toggleLanguage;
    var_dump($toggleLanguage);
    $currentLang = $toggleLanguage ? 'en' : 'sv';
    if($key != null){
        return $translations[$currentLang][$key] ?? $translations['sv'][$key] ?? $key;
    }
}

function echoT(string $string): string {
    // Replaces any %placeholder% with their localized strings.
}

?>