<?php

$SECRETS = parse_ini_file(__DIR__ . '/../php_secrets.ini', false, INI_SCANNER_TYPED);

function containsNonLatinLetters_regex(string $str): bool {
    return (bool) preg_match('/(?:(?!\p{Latin})\p{L})/u', $str);
}


function translateNonLatin(){
    $text = "Һаумыһығыҙ, минең исемем Элиас";
    
    $url = "https://translation.googleapis.com/language/translate/v2?key=" 
           . $SECRETS['GTRANSLATE_API_KEY']
           . "&q=" . urlencode($text) 
           . "&target=en";
    
    // Example with file_get_contents
    $response = file_get_contents($url);
    $result = json_decode($response, true);
    
    // Access first translation
    $translated = $result['data']['translations'][0]['translatedText'] ?? null;
    
    echo $translated;
}
?>