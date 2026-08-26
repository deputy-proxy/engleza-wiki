<?php
// Laravel router for PHP built-in server
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
if ($uri !== '/' && file_exists(__DIR__.$uri)) {
    return false;
}
require_once __DIR__.'/index.php';
