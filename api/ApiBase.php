<?php

require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

use TodoListGold\Api\HttpCode;
use TodoListGold\Security\EnvManager;
use TodoListGold\Utils\Dev\ServerUtils;

EnvManager::_init();

$phpErrorLogPath = dirname(__DIR__) . DS . 'php-error.log';

ini_set('log_errors', '1');
ini_set('error_log', $phpErrorLogPath);
ini_set('display_errors', '0');

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json; charset=utf-8');

header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

set_error_handler(callback: 'handleError');
set_exception_handler(callback: 'handleException');
register_shutdown_function(callback: 'handleShutdown');
date_default_timezone_set('Europe/Berlin');


function handleError($errno, $errstr, $errfile, $errline): never
{
    http_response_code(response_code: HttpCode::INTERNAL_SERVER_ERROR->value);
    $message = json_encode([
        'success' => false,
        'error' => 'Internal Server Error',
        'message' => "Error: [$errno] $errstr on line $errline in file $errfile"
    ]);

    ServerUtils::死ね(1, $message);
}


function handleException($exception): never
{
    http_response_code(response_code: HttpCode::INTERNAL_SERVER_ERROR->value);
    $message = json_encode([
        'success' => false,
        'error' => 'Internal Server Error',
        'message' => "Exception: {$exception->getMessage()} on line {$exception->getLine()} in file {$exception->getFile()}"
    ]);

    ServerUtils::死ね(1, $message);
}


function handleShutdown(): void
{
    $error = error_get_last();
    if ($error !== null) {
        http_response_code(response_code: HttpCode::INTERNAL_SERVER_ERROR->value);
        $message = json_encode(value: [
            'success' => false,
            'error' => 'Internal Server Error',
            'message' => "Fatal error: {$error['message']} on line {$error['line']} in file {$error['file']}"
        ]);

        ServerUtils::死ね(1, $message);
    }
}
