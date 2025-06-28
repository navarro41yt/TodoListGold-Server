<?php

namespace TodoListGold\Views\Components;

class SingleSelect extends StaticComponentBase
{
    private array $options;
    private string $idName;

    public function __construct(string $idName, array $options = [], string $placeholder = 'Select...', bool $required = false)
    {
        parent::__construct();
        $this->options = $options;

        $required = $required ? 'required' : '';
        $this->html = <<<HTML
            <input type="hidden" name="$idName" id="$idName">
            <div class="singleselect" hidden_input="$idName">
                <input type="text" class="selected-option" placeholder="$placeholder" autocomplete="off" $required>
                <ul class="dropdown hidden">
        HTML;

        $this->idName = $idName;

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

        $this->html = str_replace("id=\"$this->idName\"", "id=\"$this->idName\" value=\"$value\"", $this->html);
        $this->html = str_replace('class="selected-option"', 'class="selected-option" value="' . $this->options[$value] . '"', $this->html);
        return true;
    }

    public static function getScript(): string
    {
        return <<<JS
            document.addEventListener("DOMContentLoaded", () => {
                const singleselect = document.querySelector(".singleselect");
                const input = singleselect.querySelector(".selected-option");
                const dropdown = singleselect.querySelector(".dropdown");
                const hiddenInput = document.querySelector("#" + singleselect.getAttribute("hidden_input"));

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

                        const changeEvent = new Event("change", { bubbles: true });
                        hiddenInput.dispatchEvent(changeEvent);
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
        return $this->html;
    }
}
