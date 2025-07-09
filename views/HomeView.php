<?php

namespace TodoListGold\Views;

use DateTime;
use TodoListGold\Utils\Date\DateTimeFormat;
use TodoListGold\Utils\Utils;

class HomeView extends BaseView
{
    protected function getHead(): string
    {
        return <<<HTML
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>TodoListGold - Home</title>
            <style>
                body {
                    font-family: Arial, sans-serif;
                    margin: 0;
                    padding: 20px;
                    background-color: #f5f5f5;
                }
                .container {
                    max-width: 800px;
                    margin: 0 auto;
                    background-color: white;
                    padding: 30px;
                    border-radius: 10px;
                    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
                }
                h1 {
                    color: #333;
                    text-align: center;
                    margin-bottom: 30px;
                }
                .status {
                    background-color: #d4edda;
                    color: #155724;
                    padding: 15px;
                    border-radius: 5px;
                    margin: 20px 0;
                    border-left: 4px solid #28a745;
                }
                .info-box {
                    background-color: #f8f9fa;
                    padding: 15px;
                    border-radius: 5px;
                    margin: 20px 0;
                    border-left: 4px solid #007bff;
                }
                .time {
                    font-size: 1.2em;
                    font-weight: bold;
                    color: #007bff;
                }
                .version {
                    color: #6c757d;
                    font-style: italic;
                }
            </style>
        HTML;
    }

    protected function getContent(): string
    {
        $version = Utils::VERSION_NAME;
        $currentTime = new DateTime();
        $formattedTime = $currentTime->format(DateTimeFormat::ES);
        
        return <<<HTML
            <div class="container">
                <h1>Welcome to TodoListGold</h1>
                
                <div class="status">
                    <strong>✓ System Status:</strong> Everything is working perfectly!
                </div>
                
                <div class="info-box">
                    <strong>Current Time:</strong> <span class="time">{$formattedTime}</span>
                </div>
                
                <div class="info-box">
                    <strong>Version:</strong> <span class="version">{$version}</span>
                </div>
                
                <div class="info-box">
                    <strong>Server:</strong> TodoListGold Server API is running and ready to handle requests.
                </div>
            </div>
        HTML;
    }

    protected function getEnd(): string
    {
        return '';
    }

    public function printFullHTML(int $action = -1): void
    {
        $html = <<<HTML
        <!DOCTYPE html>
        <html lang="es">
        <head>
            {$this->getHead()}
        </head>
        <body>
            {$this->getContent()}
            {$this->getEnd()}
            {$this->getAction($action)}
        </body>
        </html>
        HTML;
        
        echo $html;
    }
}