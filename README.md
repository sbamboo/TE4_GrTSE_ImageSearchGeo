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