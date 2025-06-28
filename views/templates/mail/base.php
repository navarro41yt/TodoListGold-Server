<?php

namespace TodoListGold\Templates\Mail;

use TodoListGold\Security\EnvManager;
use TodoListGold\Utils\Date\DateUtils;
use TodoListGold\Utils\Utils;

interface IMail
{
    public static function getHead(): string;
    public static function getBody(...$params): string;
    public static function getFoot(): string;
    public static function getHtml(...$params): string;
    public static function getSubject(...$params): string;
}


abstract class MailBase implements IMail
{
    public static function getHead(): string
    {
        $baseUrl = self::getLink();

        $stamp = '<h1 style="color: #f00;">LOCAL!!!</h1>';
        if (str_contains($baseUrl, '40.89.187.83')) {
            $stamp = '<h1 style="color: #ff0;">TESTING!!!</h1>';
        } elseif (str_contains($baseUrl, 'megacapa.fuvex.com')) {
            $stamp = '';
        }

        return <<<HTML
            <!DOCTYPE html>
            <html lang="es">
            <head>
                <meta charset="UTF-8">
                <meta http-equiv="X-UA-Compatible" content="IE=edge">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
            </head>
            <body>
                {$stamp}
        HTML;
    }

    public static function getFoot(): string
    {
        $version = Utils::VERSION_NAME;
        $currentYear = DateUtils::getActualYear();
        $baseUrl = self::getLink();
    
        return <<<HTML
            <aside></aside>
            <br><br>
            <footer>
                <a href="{$baseUrl}" target="_blank">
                    <img src="https://megacapa.fuvex.com/img/banner.png" alt="Megacapa Banner" height="64">
                </a>
                <p>
                    <i>
                        Esto es un correo electrónico automático.<br>
                        {$currentYear} | Fuvex - {$version}
                    </i>
                </p>
            </footer>
            </body>
            </html>
        HTML;
    }

    public static function getHtml(...$params): string
    {
        return static::getHead() . static::getBody(...$params) . static::getFoot();
    }

    public static function getLink(string $page = '', string|int $id = 0, int $accion = -1): string
    {
        $baseUrl = EnvManager::getStr(EnvManager::KEY_BASE_URL);
        $basePage = empty($page) ? 'index.php' : "page={$page}";
        $baseId = $id > 0 ? "id={$id}" : '';
        $baseAccion = $accion !== 1 ? "accion={$accion}" : '';

        return "{$baseUrl}/?{$basePage}&{$baseId}&{$baseAccion}";
    }
}
