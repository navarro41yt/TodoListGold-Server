<?php

namespace TodoListGold\Views\Components;

class MultiSelect extends StaticComponentBase
{
    private array $options;
    private string $idName;

    public function __construct(string $idName, array $options = [], string $placeholder = 'Select...')
    {
        parent::__construct();
        $this->options = $options;

        $this->html = <<<HTML
            <input type="hidden" name="$idName" id="$idName">
            <div class="multiselect" hidden_input="$idName">
                <div class="selected-options" contenteditable="true" placeholder="$placeholder"></div>
                <ul class="dropdown hidden">
        HTML;

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

    public static function getScript(): string
    {
        return <<<JS
            document.addEventListener("DOMContentLoaded", () => {
                const multiselect = document.querySelector(".multiselect");
                const selectedOptions = document.querySelector(".selected-options");
                const dropdown = document.querySelector(".dropdown");
                const hiddenInput = document.querySelector(multiselect.getAttribute("hidden_input"));

                // Show/hide dropdown on click
                selectedOptions.addEventListener("click", () => {
                    dropdown.classList.toggle("hidden");
                });

                // Handle dropdown selection
                dropdown.addEventListener("click", (event) => {
                    if (event.target.tagName === "LI") {
                        const value = event.target.dataset.value;
                        const text = event.target.textContent;

                        // Check if already selected
                        const existing = Array.from(
                            selectedOptions.querySelectorAll("span")
                        ).some((span) => span.dataset.value === value);

                        if (!existing) {
                            // Create selected item
                            const span = document.createElement("span");
                            span.dataset.value = value;
                            span.innerHTML = `\${text} <span class="remove">×</span>`;
                            selectedOptions.appendChild(span);

                            // Optional: Mark item as selected in the dropdown
                            event.target.style.display = "none";
                        }
                    }
                });

                // Remove selected option
                selectedOptions.addEventListener("click", (event) => {
                    if (event.target.classList.contains("remove")) {
                        const span = event.target.parentElement;
                        const value = span.dataset.value;

                        // Unhide the option in the dropdown
                        const correspondingItem = dropdown.querySelector(
                            `[data-value="\${value}"]`
                        );
                        if (correspondingItem) {
                            correspondingItem.style.display = "block";
                        }

                        // Remove the span
                        span.remove();

                        // Update Hidden input
                        updateHiddenInput();
                    }
                });

                // Update the hidden input with selected values
                function updateHiddenInput() {
                    const values = Array.from(selectedOptions.querySelectorAll("span")).map(
                        (span) => span.dataset.value
                    );
                    hiddenInput.value = values.join(","); // Join values as a comma-separated string
                }

                // Filter dropdown based on input
                selectedOptions.addEventListener("input", (event) => {
                    const query = event.target.textContent.toLowerCase().trim();
                    Array.from(dropdown.children).forEach((item) => {
                    const text = item.textContent.toLowerCase();
                    if (text.includes(query)) {
                        item.style.display = "block";
                    } else {
                        item.style.display = "none";
                    }
                    });
                });

                // Close dropdown when clicking outside
                document.addEventListener("click", (event) => {
                    if (!multiselect.contains(event.target)) {
                        dropdown.classList.add("hidden");
                    }
                });
            });
        JS;
    }

    public static function getStyle(): string
    {
        return <<<CSS
            body {
                font-family: Arial, sans-serif;
                margin: 20px;
            }

            .multiselect {
                position: relative;
                width: 300px;
                border: 1px solid #ccc;
                border-radius: 4px;
                padding: 5px;
            }

            .selected-options {
                display: flex;
                flex-wrap: wrap;
                min-height: 30px;
                padding: 5px;
                cursor: text;
                border-radius: 4px;
                outline: none;
            }

            .selected-options span {
                background: #007bff;
                color: #fff;
                border-radius: 3px;
                padding: 2px 6px;
                margin: 2px;
                font-size: 12px;
            }

            .selected-options span .remove {
                margin-left: 5px;
                cursor: pointer;
                font-weight: bold;
            }

            .selected-options span .remove:hover {
                color: #ff0000;
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
