# TE4 - Group TSE - ImageSearchGeo


## Project Structure
- `/.dev` is for testing and temp stuff (anything we dont care abt but still want shared in the repo)
    - `/.dev/tests` general tests
- `/public` the actuall website
    - `/public/tests` same as `/.dev/tests` but for PHP stuff
    - `/public/css` contains css
    - `/public/js` contains js
    - `/public/php` contains all helper php
        - `/public/php/libs` contains other peoples PHP code that we use as dependencies
- `/host.bat` a quick script that runs `php` on `localhost:8080` from `/public`


## Code Formatting
- `PHP BuiltIn Functions` are `snake_case` (all lowercase)
- `Classes` are `PascalCase`
    - Public methods are `PascalCase`
    - Private methods are `camelCase` or `_camelCase` 
- `Functions` are `camelCase`
- `Variables` are `camelCase`
- `Constant variables` are `UPPER_SNAKE_CASE`
- `HTML-Element-Ids` are `kebab-case` (all lowercase)
- `HTML-Element-Classes` are `kebab-case`
- `Language Placeholders` are surrounded by `%` and inside is cased as `kebab-case` but for context `.` can be used as delim, eg: `button.search` instead of just `search` or `search-button`

## Code best practise
- HTML property order:
    1. ID
    2. CLASS
    3. (FOR)
    4. (TYPE)
    5. (NAME)
    6. (DATA)
    7. Style
    8. VALUE
- CSS use `var(--)` for colors so they can be styled with light/dark mode.
- CSS/HTML use reusable classes `helpers.css` to make styling easier and more consistent.
- CSS responsive, use `rem` units.