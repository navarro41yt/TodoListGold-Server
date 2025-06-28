<?php

use TodoListGold\Model\Entity;
use TodoListGold\Model\Repository;
use TodoListGold\Security\EnvManager;
use TodoListGold\Utils\Dev\ServerUtils;

require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
date_default_timezone_set('Europe/Berlin');
EnvManager::_init();

$filename = basename(__FILE__);
$expectedArgc = 3;

if ($argc != $expectedArgc) {
    ServerUtils::死ね(1, "Incorrect arguments. Usage: php {$filename} <repository-className> <serializedEntity>");
}

/** @var Entity */
$entity = ServerUtils::unserialize($argv[2]);

/** @var Repository */
$repo = new $argv[1]();

$repo->save($entity);
