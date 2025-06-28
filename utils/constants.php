<?php

namespace TodoListGold\Constants;

use TodoListGold\Utils\Date\DateUtils;

const S_ID = "id";
const S_USER = "user";
const S_PASSWORD = "passwd";
const S_ROLE = "rol";
const S_PILOT_ID = "idPiloto";

const UNDEFINED = "todolistgold_undefined";

define('ROOT_DIR', dirname(__DIR__) . DIRECTORY_SEPARATOR);
define('DS', DIRECTORY_SEPARATOR);
define('CURRENT_YEAR', DateUtils::getActualYear());

const F_HELPERS = 'helpers' . DIRECTORY_SEPARATOR;
const F_TEMP = 'temp' . DIRECTORY_SEPARATOR;
const F_LOGS = 'logs' . DIRECTORY_SEPARATOR;

const H_ASYNC_MAILER = ROOT_DIR . F_HELPERS . 'asyncMailer.php';
const H_ASYNC_WRITER = ROOT_DIR . F_HELPERS . 'asyncWriter.php';
const H_ASYNC_SAVER = ROOT_DIR . F_HELPERS . 'asyncSaver.php';
const H_ASYNC_CURL = ROOT_DIR . F_HELPERS . 'asyncCurl.php';

const L_CRONTAB = 'crontab';
const L_AUTO_SEND_LOGS = 'autoSendLogs';

const L_API_HEALTH = 'api_health';
