<?php

$SECRETS = parse_ini_file(__DIR__ . '/../php_secrets.ini', false, INI_SCANNER_TYPED);

//determines if a string contains nonlatin characters
function containsNonLatinLetters_regex(string $str): bool {
    return (bool) preg_match('/(?:(?!\p{Latin})\p{L})/u', $str);
}

// translates strings with non latin charcters
function translateNonLatin(string $foreignText){
    
    $url = "https://translation.googleapis.com/language/translate/v2?key=" 
           . $SECRETS['GTRANSLATE_API_KEY']
           . "&q=" . urlencode($foreignText) 
           . "&target=en";

    $response = file_get_contents($url);
    $result = json_decode($response, true);

    $translated = $result['data']['translations'][0]['translatedText'] ?? null;
    
    echo $translated;
}
?>