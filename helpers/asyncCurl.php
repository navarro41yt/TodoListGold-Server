<?php

use TodoListGold\Utils\Dev\ServerUtils;

require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

$urlDataFile = $argv[1] ?? null;
if ($urlDataFile === null) {
    die("Usage: php asyncCurl.php <url>\n");
}

$unserializedUrl = file_get_contents($urlDataFile);
$url = ServerUtils::unserialize($unserializedUrl);

$response = ServerUtils::curl($url);

print("Response {$response}:\n");

@unlink($urlDataFile);
