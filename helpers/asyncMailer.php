<?php

use TodoListGold\Utils\Dev\ServerUtils;
use PHPMailer\PHPMailer\PHPMailer;

require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

if ($argc < 2) {
    ServerUtils::死ね(1, "Too few arguments. Usage: php asyncMailer.php <mailDataFile>");
} elseif ($argc > 2) {
    ServerUtils::死ね(2, "Too many arguments. Usage: php asyncMailer.php <mailDataFile>");
}

date_default_timezone_set('Europe/Berlin');

$mailDataFile = $argv[1];
if (!file_exists($mailDataFile)) {
    ServerUtils::死ね(4, "Mail data file does not exist: $mailDataFile");
}

$serializedMail = file_get_contents($mailDataFile);
$mail = unserialize(base64_decode($serializedMail));

if (!$mail instanceof PHPMailer) {
    ServerUtils::死ね(3, 'Data must be a PHPMailer instance.');
}

@unlink($mailDataFile);

$mail->send();
