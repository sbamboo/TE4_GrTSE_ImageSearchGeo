<?php
// Safe, clear implementation (recommended)
// function containsNonLatinLetters(string $str): bool {
//     // Normalize to composed form if ext-intl is available (helps with decomposed accents)
//     if (class_exists('Normalizer')) {
//         $str = Normalizer::normalize($str, Normalizer::FORM_C);
//     }

//     // Split into UTF-8 characters (code points)
//     $chars = preg_split('//u', $str, -1, PREG_SPLIT_NO_EMPTY);
//     foreach ($chars as $ch) {
//         // if it's a letter AND NOT a Latin-script letter => we found non-Latin text
//         if (preg_match('/\p{L}/u', $ch) && !preg_match('/\p{Latin}/u', $ch)) {
//             return true;
//         }
//     }
//     return false;
// }

// Compact regex-only (may be fine on modern PHP/PCRE)
function containsNonLatinLetters_regex(string $str): bool {
    // Matches a letter that is NOT in the Latin script
    return (bool) preg_match('/(?:(?!\p{Latin})\p{L})/u', $str);
}

// Examples
// var_dump(containsNonLatinLetters("Hello World")); // false
// var_dump(containsNonLatinLetters("Привет"));      // true
// var_dump(containsNonLatinLetters("你好"));         // true
// var_dump(containsNonLatinLetters("Hola! 😀"));    // false
// var_dump(containsNonLatinLetters("Café"));        // false

// // Same expected results with the regex-only variant:
// var_dump(containsNonLatinLetters_regex("Hello World")); // false
// var_dump(containsNonLatinLetters_regex("Привет"));      // true
// var_dump(containsNonLatinLetters_regex("你好"));         // true
// var_dump(containsNonLatinLetters_regex("Hola! 😀"));    // false
// var_dump(containsNonLatinLetters_regex("Café"));        // false

var_dump(containsNonLatinLetters("日本"));
var_dump(containsNonLatinLetters_regex("日本"));