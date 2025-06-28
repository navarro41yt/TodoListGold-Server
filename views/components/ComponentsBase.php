<?php

namespace TodoListGold\Views\Components;

use TodoListGold\Utils\Dev\BaseClass;

interface IRawStaticComponent
{
    public static function getScript(): string;
    public static function getStyle(): string;
    public function getHtml(): string;
}


abstract class StaticComponentBase extends BaseClass implements IRawStaticComponent
{
    protected string $html = '';

    public function __construct()
    {
    }

    public function getHtml(): string
    {
        return $this->html;
    }
}


interface IRawComponent
{
    public function getScript(): string;
    public function getHtml(): string;
    public static function getStyle(): string;
}


abstract class ComponentBase extends BaseClass implements IRawComponent
{
    protected string $html = '';

    public function __construct()
    {
    }

    public function getHtml(): string
    {
        return <<<HTML
            {$this->html}
            <style>{$this->getScript()}</style>
        HTML;
    }
}
