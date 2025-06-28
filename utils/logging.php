<?php

namespace TodoListGold\Logging;

use DateTime;
use RuntimeException;
use Throwable;
use TodoListGold\Api\ApiUtils;
use TodoListGold\Api\HttpCode;
use TodoListGold\Utils\Date\DateFormat;
use TodoListGold\Utils\Date\DateTimeFormat;
use TodoListGold\Utils\DevUtils;
use TodoListGold\Utils\Dev\ServerUtils;

const LOGS_DIRNAME = 'logs';
const LOGS_DIR = ROOT_DIR . DIRECTORY_SEPARATOR . LOGS_DIRNAME;

enum Level: int
{
    case DEBUG = 0;
    case INFO = 1;
    case WARNING = 2;
    case ERROR = 3;
    case CRITICAL = 4;
    case FATAL = 5;
}


class Logger
{
    protected static function getTimestamp(): string
    {
        $microtime = microtime(true);
        $ms = sprintf('%03d', ($microtime - floor($microtime)) * 1000);

        $dateTime = new DateTime();
        return $dateTime->format(DateTimeFormat::ISO_8601) . '.' . $ms;
    }

    protected static function formatValue($value, Level $level): string
    {
        $m = print_r($value, true);
        $timestamp = self::getTimestamp();
        return "[{$timestamp}] ({$level->name}) $m";
    }

    protected static function appendToFile(string $path, string $content): void
    {
        if (!file_put_contents($path, $content, FILE_APPEND)) {
            throw new RuntimeException("Failed to append to log file: $path");
        }
    }

    public static function log(mixed $value, Level $level = Level::DEBUG, string $logName = 'log'): void
    {
        $date = date(DateFormat::ISO_8601);
        $path = LOGS_DIR . DS . "{$logName}_{$date}.log";

        $formattedValue = self::formatValue($value, $level);
        ServerUtils::asyncWriter($path, $formattedValue, append: true);
    }
}


class LoggerV2
{
    public readonly string $logName;

    public function __construct(string $logName)
    {
        $this->logName = $logName;
    }

    protected function getTimestamp(): string
    {
        $microtime = microtime(true);
        $ms = sprintf('%03d', ($microtime - floor($microtime)) * 1000);

        $dateTime = new DateTime();

        return $dateTime->format(DateTimeFormat::ISO_8601) . '.' . $ms;
    }

    protected function formatValue(mixed $value, Level $level): string
    {
        $m = print_r($value, true);
        $timestamp = $this->getTimestamp();
        return "[{$timestamp}] ({$level->name}) {$m}";
    }

    protected function appendToFile(string $path, string $content): void
    {
        file_put_contents($path, $content, FILE_APPEND);
    }

    private function getPath(): string
    {
        $date = date(DateFormat::ISO_8601);

        return LOGS_DIR . DS . "{$this->logName}_{$date}.log";
    }

    public function log(mixed $value, Level $level): void
    {
        $formattedValue = self::formatValue($value, $level);
        ServerUtils::asyncWriter($this->getPath(), $formattedValue, append: true);
    }

    public function debug(mixed $value): void
    {
        $this->log($value, Level::DEBUG);
    }

    public function info(mixed $value): void
    {
        $this->log($value, Level::INFO);
    }

    public function warning(mixed $value): void
    {
        $this->log($value, Level::WARNING);
    }

    public function error(mixed $value): void
    {
        $this->log($value, Level::ERROR);
    }

    public function critical(mixed $value): void
    {
        $this->log($value, Level::CRITICAL);
    }

    public function fatal(mixed $value): void
    {
        $this->log($value, Level::FATAL);
    }

    public function end(): void
    {
        ServerUtils::asyncWriter($this->getPath(), '', append: true);
    }
}


class LoggerApi extends LoggerV2
{
    public readonly string $logName;

    public function __construct(string $logName)
    {
        $this->logName = $logName;
    }

    public function logAPIStart(): void
    {
        $method = ApiUtils::getRequestMethod();
        $body = ApiUtils::getJsonBody();

        $this->debug("----------START_OF_REQUEST----------");
        $this->debug("Method: {$method->name}");
        $this->debug("Incoming JSON: " . json_encode($body));
    }

    public function logAPIEnd(string $message): void
    {
        $returnCode = http_response_code();
        $httpReturn = HttpCode::from($returnCode);

        $this->debug("Response: {$message}");
        $this->debug("HTTP Response: {$httpReturn->name} ({$returnCode})");
        $this->debug("----------END_OF_REQUEST----------");
    }
}


class DBLogger extends Logger
{
    public const LOGS_DB_FILENAME_BASE = '_DB.log';

    protected static function formatException(Throwable $e): string
    {
        $currentTime = self::getTimestamp();
        $fttMessage = "[$currentTime]\n{$e->getMessage()} in {$e->getFile()} on line {$e->getLine()}\nStack trace:\n{$e->getTraceAsString()}";
        return $fttMessage;
    }

    public static function logDB(Throwable $exception): void
    {
        $currentDate = date(DateFormat::ISO_8601);
        $filename = self::LOGS_DB_FILENAME_BASE;
        $path = LOGS_DIR . DIRECTORY_SEPARATOR . "{$currentDate}{$filename}";

        $formattedException = self::formatException($exception) . PHP_EOL;
        ServerUtils::asyncWriter($path, $formattedException, append: true);
    }
}
