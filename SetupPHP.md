# XAMPP
Install XAMPP

# Manual
1. Download PHP 8.4 https://windows.php.net/downloads/releases/php-8.4.12-Win32-vs17-x64.zip
2. Extract in `C:\installs\php`
3. Add `C:\installs\php` to PATH
4. Rename `C:\installs\php\php.ini-development` to `C:\installs\php\php.ini`
    1. Uncomment `extension_dir` for windows
    2. Uncomment `extension=openssl` and `extension=curl`
    3. If you get errors needing TLS: Install `openssl` with `winget install ShiningLight.OpenSSL.Light`

# FiveServer
For five server follow Manuall or XAMPP and then:
1. Install `Live Server (Five Server)` extension