<?php
// File containing functions for turning %placeholder% into localized strings based on language lists.

// Here we either provide a function with same signature as PHP "echo" but applies placeholders
// or provide a buffer function that applies placeholders post regular echos.
// The thing to note is "echo" is a language construct and not a function, so we can't get its non-parenthesis input style.

// Placeholders can be either from a folder or directly here as keyed arrays: $placeholders = ["<languageCode>" => ["<placeholder>" => "<localizedString>"]];

// Also provides just a string->string function (used by the echo/buffer function).
?>