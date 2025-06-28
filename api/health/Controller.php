<?php

namespace TodoListGold\Api;

use DateTime;
use TodoListGold\Api\ControllerBase;
use TodoListGold\Utils\Date\DateTimeFormat;
use TodoListGold\Utils\Utils;

class HealthController extends ControllerBase
{
    public function health(): string
    {
        $version = Utils::VERSION_NAME;
        $now = new DateTime();
        return $this->fttResponse(HttpCode::OK, "{$version} {$now->format(DateTimeFormat::ES)}");
    }
}
