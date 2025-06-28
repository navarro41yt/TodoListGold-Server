<?php

namespace Megacapa\Helpers\Auto\AutoSendLogs;

use DateTime;
use Megacapa\IO\Path;
use Megacapa\Logging\LoggerV2;
use Megacapa\Mail\DevMailer;
use Megacapa\Security\EnvManager;
use Megacapa\Utils\Date\DateFormat;
use Megacapa\Utils\Dev\ServerUtils;

use const Megacapa\Constants\F_LOGS;
use const Megacapa\Constants\L_AUTO_SEND_LOGS;

require_once dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

$now = new DateTime();
$date = $now->format(DateFormat::ISO_8601);

$logger = new LoggerV2(L_AUTO_SEND_LOGS);
$logger->info("Enviando logs del $date");

EnvManager::_init();

#region PHP Standard Log
$phpErrorLogPath = Path::joinRoot('php-error.log');
$phpErrorLogNewPath = Path::joinRoot(F_LOGS, "phpError_$date.log");
ServerUtils::appendFile($phpErrorLogPath, $phpErrorLogNewPath);
unlink($phpErrorLogPath);

#region Personalized Logs
$logsFolder = Path::joinRoot(F_LOGS);
$files = scandir($logsFolder);
$logsFiles = array_filter($files, fn($file) => strpos($file, $date) !== false);
$logsFilesFiltered = array_filter($logsFiles, fn($file) => strpos($file, L_AUTO_SEND_LOGS) === false);

$logger->info("Archivos encontrados: " . count($logsFilesFiltered));
$attachments = [];
foreach ($logsFilesFiltered as $logFile) {
    $path = Path::joinRoot(F_LOGS, $logFile);
    $logger->info("Adjuntando $path");
    $attachments[] = $path;
}

#region Mailer
DevMailer::sendLogs($now, $attachments);
$logger->info("Logs enviados");

echo "Logs enviados";
