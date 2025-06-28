<?php

namespace TodoListGold\Views;

use TodoListGold\Utils\Dev\BaseClass;
use TodoListGold\Utils\WebUtils;

interface IView
{
    public function printFullHTML(int $action = -1): void;
}


abstract class BaseView extends BaseClass implements IView
{
    abstract protected function getHead(): string;
    abstract protected function getContent(): string;
    abstract protected function getEnd(): string;

    /**
     * Esta función debe ser sobrescrita en las clases hijas. Se deberá usar un switch con las acciones correspondientes.
     * (Para sobreescribir se usa la anotación #[\Override] en PHP 8).
     *
     * @param int $action Acción a realizar (-1 por defecto para que no se ejecute).
     * @return string El contenido que se va a ejecutar.
     */
    protected function getAction(int $action = -1): string
    {
        return WebUtils::consoleLog("Acción no definida. ($action)");
    }

    protected function getChecked(mixed $v1, mixed $v2, bool $strict = true, bool $inverse = false): string
    {
        $checkedProperty = 'checked';

        return $inverse
            ? ($strict ? ($v1 !== $v2 ? $checkedProperty : '') : ($v1 != $v2 ? $checkedProperty : ''))
            : ($strict ? ($v1 === $v2 ? $checkedProperty : '') : ($v1 == $v2 ? $checkedProperty : ''))
        ;
    }

    protected function getCheckedStr(string $s1, string $s2): string
    {
        return strcasecmp($s1, $s2) === 0 ? 'checked' : '';
    }

    protected function getSelected(mixed $v1, mixed $v2, bool $strict = true, bool $inverse = false): string
    {
        $selectedProperty = 'selected';

        return $inverse
            ? ($strict ? ($v1 !== $v2 ? $selectedProperty : '') : ($v1 != $v2 ? $selectedProperty : ''))
            : ($strict ? ($v1 === $v2 ? $selectedProperty : '') : ($v1 == $v2 ? $selectedProperty : ''))
        ;
    }

    protected function getSelectedStr(string $s1, string $s2): string
    {
        return strcasecmp($s1, $s2) === 0 ? 'selected' : '';
    }

    protected function getDisabled(mixed $v1, mixed $v2, bool $strict = true, bool $inverse = false): string
    {
        $disabledProperty = 'disabled';

        return $inverse
            ? ($strict ? ($v1 !== $v2 ? $disabledProperty : '') : ($v1 != $v2 ? $disabledProperty : ''))
            : ($strict ? ($v1 === $v2 ? $disabledProperty : '') : ($v1 == $v2 ? $disabledProperty : ''))
        ;
    }

    protected function getActivoStr(bool $activo): string
    {
        return $activo ? 'Activo' : 'Inactivo';
    }

    public function getQM(string $message): string
    {
        return <<<HTML
            <div class="qm_hover" title="$message">
                ?
            </div>
        HTML;
    }

    public function getLink(string $page = '', mixed $id = '', string $action = '', string $extra = ''): string
    {
        return "?page=$page&id=$id&accion=$action$extra";
    }

    public function getGoBackButton(): string
    {
        return <<<HTML
            <div class="d-flex justify-content-center">
                <a href="javascript:history.back()" class="btn btn-primary">Volver</a>
            </div>
        HTML;
    }
}
