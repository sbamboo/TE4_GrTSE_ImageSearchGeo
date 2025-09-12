<?php

function containsNonLatinLetters_regex(string $str): bool {
    return (bool) preg_match('/(?:(?!\p{Latin})\p{L})/u', $str);
}


function translateNonLatin(){

}
?>