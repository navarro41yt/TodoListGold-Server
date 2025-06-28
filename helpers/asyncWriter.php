<?php

require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

use TodoListGold\Utils\Dev\ServerUtils;

if ($argc < 4) {
    ServerUtils::死ね(1, "Too few arguments. Usage: php asyncWriter.php <path> <message> <append>");
} elseif ($argc > 4) {
    ServerUtils::死ね(2, "Too many arguments. Usage: php asyncWriter.php <path> <message> <append>");
}

$path = $argv[1];
$message = $argv[2];
$append = ($argv[3] === 'true') ? FILE_APPEND : 0;

ServerUtils::createPathIfNotExists($path);
file_put_contents($path, "$message\n", $append);
