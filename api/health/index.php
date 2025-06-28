<?php

use TodoListGold\Api\ApiUtils;
use TodoListGold\Api\HealthController;
use TodoListGold\Api\RequestMethod;
use TodoListGold\Logging\LoggerApi;

use const TodoListGold\Constants\L_API_HEALTH;

require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'ApiBase.php';

$campaignController = new HealthController();
$method = ApiUtils::getRequestMethod();

$body = ApiUtils::getJsonBody();

$loggerCampaign = new LoggerApi(L_API_HEALTH);

$loggerCampaign->logAPIStart();

switch ($method) {
    case RequestMethod::GET:
        $message = $campaignController->health();
        break;
    default:
        $message = ApiUtils::returnNotImplementedPoint();
        break;
}

$loggerCampaign->logAPIEnd($message);

echo $message;
