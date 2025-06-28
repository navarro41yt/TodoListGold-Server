<?php

namespace TodoListGold\Helpers\Auto\AutoSendLogs;

use TodoListGold\DB\Role;
use TodoListGold\Security\EnvManager;

require_once dirname(__DIR__, 1) . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
session_start();
EnvManager::_init();

Role::checkRoleAndRedirect(Role::ADMINISTRADOR);

$oldBool = EnvManager::isProduction();
$oldValue = $oldBool ? 'PRODUCTION' : 'DEVELOPMENT';

EnvManager::set(EnvManager::KEY_PRODUCTION, !$oldBool);

$newBool = EnvManager::isProduction();
$newValue = $newBool ? 'PRODUCTION' : 'DEVELOPMENT';

echo "Old value was ($oldValue), new value is ($newValue)";
