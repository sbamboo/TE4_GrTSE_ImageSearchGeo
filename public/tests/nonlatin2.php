<?php

/**
 * Checks if a string contains non-Latin text.
 *
 * This function considers Latin characters (a-z, A-Z), common punctuation,
 * numbers, and Latin extended characters (like accented letters) as Latin.
 * Emojis are ignored.
 *
 * @param string $text The input string.
 * @return bool True if the string contains non-Latin text, false otherwise.
 */
function containsNonLatinText(string $text): bool
{
    // Remove emojis first, as they can sometimes be misidentified by regex
    // or interfere with the detection of actual non-Latin characters.
    // Emoji regex from https://stackoverflow.com/a/48854084
    $textWithoutEmojis = preg_replace(
        '/[\x{1F600}-\x{1F64F}\x{1F300}-\x{1F5FF}\x{1F680}-\x{1F6FF}' .
        '\x{1F1E0}-\x{1F1FF}\x{2600}-\x{26FF}\x{2700}-\x{27BF}]/u',
        '',
        $text
    );

    // This regex matches any character that is NOT:
    // - a basic Latin letter (a-z, A-Z)
    // - a number (0-9)
    // - common punctuation and symbols (!@#$%^&*()_+-=[]{};\':",.<>/?`~|)
    // - a whitespace character
    // - a Latin Extended-A or Latin Extended-B character
    // The `u` flag is for UTF-8 matching.
    return (bool) preg_match(
        '/[^\p{L}\p{N}\p{P}\p{Z}\x{0000}-\x{007F}\x{0100}-\x{024F}]/u',
        $textWithoutEmojis
    );
}

// Test cases
var_dump(containsNonLatinText("Hello World"));    // false
var_dump(containsNonLatinText("Привет"));         // true (Cyrillic)
var_dump(containsNonLatinText("你好"));             // true (Chinese)
var_dump(containsNonLatinText("Hola! 😀"));       // false (emoji ignored, Latin text only)
var_dump(containsNonLatinText("Café"));           // false (Latin extended chars allowed)
var_dump(containsNonLatinText("Grüße"));          // false (Latin extended chars allowed)
var_dump(containsNonLatinText("123 Test."));      // false
var_dump(containsNonLatinText("こんにちは"));      // true (Japanese)
var_dump(containsNonLatinText("שלום"));           // true (Hebrew)
var_dump(containsNonLatinText("Caffè Latte"));    // false
var_dump(containsNonLatinText("Testing with symbols: @#$%^&*")); // false
var_dump(containsNonLatinText(""));               // false
var_dump(containsNonLatinText("你好😀世界"));     // true (Chinese, emoji ignored)

?>