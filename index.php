<?php

namespace TodoListGold;

use TodoListGold\Utils\Utils;
use TodoListGold\Views\HomeView;

require_once 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

// Get the page parameter from URL
$page = $_GET['page'] ?? 'home';

// Simple routing
switch ($page) {
    case 'home':
    default:
        $view = new HomeView();
        $view->printFullHTML();
        break;
}
