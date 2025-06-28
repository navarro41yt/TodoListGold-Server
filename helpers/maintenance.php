<?php

namespace TodoListGold\Helpers\Auto\AutoSendLogs;

use DateTime;
use TodoListGold\Security\EnvManager;
use TodoListGold\Utils\Date\DateTimeFormat;

require_once dirname(__DIR__, 1) . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
session_start();
EnvManager::_init();

$password = EnvManager::getStrFromPath(EnvManager::KEY_CRITICAL_OPERATIONS_PASS);
$sentPassword = $_POST['p'] ?? null;

if ($password !== $sentPassword) {
    echo "Incorrect Usage";
    die(1);
}

date_default_timezone_set('Europe/Berlin');
if (!isset($_GET['n'], $_GET['c'], $_GET['e'])) {
    echo "Incorrect Usage";
    die(1);
}

$newValue = (int) $_GET['n'];
$newValueBool = $newValue === 1;
$newCause = $_GET['c'];
$newStartDate = new DateTime();
$newStartDateFtt = $newStartDate->format(DateTimeFormat::ES);
$newEndDate = $_GET['e'];

$newValueStr = $newValue ? 'ENABLED' : 'DISABLED';

print("New value is ($newValueStr), new cause is ($newCause), new start date is ($newStartDateFtt), new end date is ($newEndDate)<br>");

EnvManager::set(EnvManager::MAINTENANCE, $newValueBool);
EnvManager::set(EnvManager::MAINTENANCE_CAUSE, $newCause);
EnvManager::set(EnvManager::MAINTENANCE_START, $newStartDateFtt);
EnvManager::set(EnvManager::MAINTENANCE_END, $newEndDate);
