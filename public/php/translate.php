<?php
//determines if a string contains nonlatin characters
function containsNonLatinLetters(string $str): bool {
    return (bool) preg_match('/(?:(?!\p{Latin})\p{L})/u', $str);
}

// Wrapper class for Google Translate API
class GTranslate {
    private string $apiKey;
    private ?string $targetLanguage;
    public function __construct(string $apiKey, ?string $targetLanguage = null) {
        $this->apiKey = $apiKey;
        $this->targetLanguage = $targetLanguage;
    }

    public function GetTargetLang(): ?string{
        return $this->targetLanguage;
    }

    public function translate(string $text): ?string {
        if ($this->targetLanguage === null) {
            $this->targetLanguage = 'en';
        }
        
        $url = "https://translation.googleapis.com/language/translate/v2?key=" 
               . $this->apiKey
               . "&q=" . urlencode($text) 
               . "&target=" . $this->targetLanguage;

        $response = file_get_contents($url);
        $result = json_decode($response, true);

        return $result['data']['translations'][0]['translatedText'] ?? null;
    }

    public function translateKeepOrg(string $text): string {
        $translated = $this->translate($text);
        if ($translated && $translated !== $text) {
            return $text . " (" . $translated . ")";
        }
        return $text;
    }
}

// translates strings with non latin charcters
// function translateNonLatin(string $foreignText): string {
//     $SECRETS = parse_ini_file(__DIR__ . '/../../php_secrets.ini', false, INI_SCANNER_TYPED);
//     $url = "https://translation.googleapis.com/language/translate/v2?key=" 
//            . $SECRETS['GTRANSLATE_API_KEY']
//            . "&q=" . urlencode($foreignText) 
//            . "&target=en";

//     $response = file_get_contents($url);
//     $result = json_decode($response, true);

//     $translated = $result['data']['translations'][0]['translatedText'] ?? null;
    
//     //return $translated;
//     if($translated && $translated !== $foreignText){
//         return $foreignText . "(" . $translated . ")";
//     } 
// }
?>