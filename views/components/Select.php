<?php

namespace TodoListGold\Views\Components;

use TodoListGold\Exceptions\NotImplementedException;
use TodoListGold\Utils\Utils;

class Select extends ComponentBase
{
    private array $options;
    private string $idName;
    private string $idHidden;
    private string $idInput;
    private string $idUl;
    private string $idDiv;

    public function __construct(string $name, array $options = [], string $placeholder = 'Select...', bool $required = false)
    {
        throw new NotImplementedException('Select component is not implemented yet.');
        parent::__construct();
        $this->options = $options;

        $randomString = Utils::generateRandomName(10);
        $id = "{$name}_{$randomString}";
        $idDiv = "{$id}_div";
        $idHidden = "{$id}_hidden";
        $idInput = "{$id}_input";
        $idUl = "{$id}_ul";

        $this->idHidden = $idHidden;
        $this->idInput = $idInput;
        $this->idUl = $idUl;
        $this->idDiv = $idDiv;
        $this->idName = $name;
        $required = $required ? 'required' : '';
        $this->html = <<<HTML
            <input type="hidden" name="$name" id="$idHidden">
            <div class="singleselect" hidden_input="$name" id="$idDiv">
                <input type="text" class="selected-option" placeholder="$placeholder" autocomplete="off" $required id="$idInput">
                <ul class="dropdown hidden" id="$idUl">
        HTML;

        if (!$required) {
            $this->html .= "<li data-value=\"\" class=\"placeholder\">$placeholder</li>";
        }

        foreach ($options as $value => $text) {
            $this->html .= "<li data-value=\"$value\">$text</li>";
        }
    }

    public function addOption(string $value, string $text): bool
    {
        if (isset($this->options[$value])) {
            return false;
        }

        $this->options[$value] = $text;
        $this->html .= "<li data-value=\"$value\">$text</li>";
        return true;
    }

    public function addOptionKV(array $kv): bool
    {
        $k = array_key_first($kv);
        $v = $kv[$k];
        return $this->addOption($k, $v);
    }

    public function setSelected(mixed $value): bool
    {
        if (!isset($this->options[$value])) {
            return false;
        }

        $this->html = str_replace('selected-option', 'selected-option selected', $this->html);
        $this->html = str_replace('placeholder="Select..."', 'value="' . $this->options[$value] . '"', $this->html);
        return true;
    }

    public function getScript(): string
    {
        return <<<JS
            document.addEventListener("DOMContentLoaded", () => {
                const singleselect = document.getElementById("{$this->idDiv}");
                const input = singleselect.querySelector(".selected-option");
                const hiddenInput = singleselect.querySelector("input[type='hidden']");
                const dropdown = singleselect.querySelector(".dropdown");

                // Show dropdown on focus
                input.addEventListener("focus", () => {
                    dropdown.classList.remove("hidden");
                });

                // Handle dropdown selection
                dropdown.addEventListener("click", (event) => {
                    if (event.target.tagName === "LI") {
                        const value = event.target.dataset.value;
                        const text = event.target.textContent;

                        input.value = text;
                        hiddenInput.value = value;
                        dropdown.classList.add("hidden");
                    }
                });

                // Filter dropdown based on input
                input.addEventListener("input", () => {
                    const query = input.value.toLowerCase().trim();
                    Array.from(dropdown.children).forEach((item) => {
                        const text = item.textContent.toLowerCase();
                        item.style.display = text.includes(query) ? "block" : "none";
                    });
                });

                // Close dropdown when clicking outside
                document.addEventListener("click", (event) => {
                    if (!singleselect.contains(event.target)) {
                        dropdown.classList.add("hidden");
                    }
                });
            });
        JS;
    }

    public static function getStyle(): string
    {
        return <<<CSS
            .singleselect {
                position: relative;
                width: 300px;
                border: 1px solid #ccc;
                border-radius: 4px;
                padding: 5px;
            }

            .selected-option {
                width: 100%;
                border: none;
                padding: 5px;
                outline: none;
                font-size: 14px;
            }

            .dropdown {
                position: absolute;
                top: 100%;
                left: 0;
                right: 0;
                background: #fff;
                border: 1px solid #ccc;
                border-radius: 4px;
                z-index: 1000;
                max-height: 150px;
                overflow-y: auto;
                list-style: none;
                padding: 0;
                margin: 0;
            }

            .dropdown li {
                padding: 10px;
                cursor: pointer;
            }

            .dropdown li:hover {
                background: #f0f0f0;
            }

            .hidden {
                display: none;
            }
        CSS;
    }

    #[\Override]
    public function getHtml(): string
    {
        $this->html .= '</ul></div>';
        $script = $this->getScript();
        return "{$this->html}<script>$script</script>";
    }
}
