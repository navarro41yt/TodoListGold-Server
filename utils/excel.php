<?php

namespace TodoListGold\Utils\Excel;

use TodoListGold\Utils\Dev\ServerUtils;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx as XlsxWriter;

enum Color: string
{
    case RED = '#FF0000';
    case GREEN = '#00FF00';
    case BLUE = '#0000FF';
    case YELLOW = '#FFFF00';
    case CYAN = '#00FFFF';
    case MAGENTA = '#FF00FF';
    case BLACK = '#000000';
    case WHITE = '#FFFFFF';
    case ORANGE = '#FFA500';
    case PURPLE = '#800080';
    case PINK = '#FFC0CB';
    case GRAY = '#808080';
    case LIGHT_GRAY = '#D3D3D3';
    case DARK_GRAY = '#A9A9A9';
    case BROWN = '#A52A2A';
    case LIGHT_RED = '#FF7F7F';
    case LIGHT_BLUE = '#ADD8E6';
    case LIGHT_GREEN = '#90EE90';
    case LIGHT_YELLOW = '#FFFFE0';
    case LIGHT_CYAN = '#E0FFFF';
    case LIGHT_ORANGE = '#FFA07A';
    case LIGHT_PURPLE = '#E6E6FA';
    case LIGHT_PINK = '#FFB6C1';
    case LIGHT_BROWN = '#D2B48C';
    case DARK_RED = '#8B0000';
    case DARK_BLUE = '#00008B';
    case DARK_GREEN = '#006400';
    case DARK_YELLOW = '#FFD700';
    case DARK_CYAN = '#008B8B';
    case DARK_MAGENTA = '#8B008B';
    case DARK_ORANGE = '#FF8C00';
    case DARK_PURPLE = '#4B0082';
    case DARK_PINK = '#FF1493';
    case DARK_BROWN = '#654321';

    public function getHex(): string
    {
        return $this->value;
    }

    public function getPureHex(): string
    {
        return ltrim($this->value, '#');
    }

    /** @return array{int, int, int} */
    public function getRGB(): array
    {
        $r = hexdec(substr($this->value, 1, 2));
        $g = hexdec(substr($this->value, 3, 2));
        $b = hexdec(substr($this->value, 5, 2));

        return [$r, $g, $b];
    }

    public function getExcelFriendly(): array
    {
        $hex = $this->getPureHex();

        return ['rgb' => $hex];
    }
}


enum FillType: string
{
    case NONE = 'none';
    case SOLID = 'solid';
    case GRADIENT_LINEAR = 'linear';
    case GRADIENT_PATH = 'path';
    case PATTERN_DARKDOWN = 'darkDown';
    case PATTERN_DARKGRAY = 'darkGray';
    case PATTERN_DARKGRID = 'darkGrid';
    case PATTERN_DARKHORIZONTAL = 'darkHorizontal';
    case PATTERN_DARKTRELLIS = 'darkTrellis';
    case PATTERN_DARKUP = 'darkUp';
    case PATTERN_DARKVERTICAL = 'darkVertical';
    case PATTERN_GRAY0625 = 'gray0625';
    case PATTERN_GRAY125 = 'gray125';
    case PATTERN_LIGHTDOWN = 'lightDown';
    case PATTERN_LIGHTGRAY = 'lightGray';
    case PATTERN_LIGHTGRID = 'lightGrid';
    case PATTERN_LIGHTHORIZONTAL = 'lightHorizontal';
    case PATTERN_LIGHTTRELLIS = 'lightTrellis';
    case PATTERN_LIGHTUP = 'lightUp';
    case PATTERN_LIGHTVERTICAL = 'lightVertical';
    case PATTERN_MEDIUMGRAY = 'mediumGray';
}


enum BorderType: string
{
    case NONE = 'none';
    case DASHDOT = 'dashDot';
    case DASHDOTDOT = 'dashDotDot';
    case DASHED = 'dashed';
    case DOTTED = 'dotted';
    case DOUBLE = 'double';
    case HAIR = 'hair';
    case MEDIUM = 'medium';
    case MEDIUMDASHDOT = 'mediumDashDot';
    case MEDIUMDASHDOTDOT = 'mediumDashDotDot';
    case MEDIUMDASHED = 'mediumDashed';
    case SLANTDASHDOT = 'slantDashDot';
    case THICK = 'thick';
    case THIN = 'thin';
    case OMIT = 'omit';
}


enum BorderPosition: string
{
    case TOP = 'top';
    case BOTTOM = 'bottom';
    case LEFT = 'left';
    case RIGHT = 'right';
    case ALL = 'all';
}


class Excel
{
    public const XLSX_EXT = '.xlsx';
    public const XLSX_EXT_REGEX = '/\.xlsx$/';

    public const DEFAULT_SHEETNAME = '__DEFAULT__';

    public string $path;

    /** @var string[] */
    public array $sheetNames = [];

    protected Spreadsheet $spreadsheet;
    protected XlsxWriter $xslxWriter;

    public function __construct(string $path = '', string $sheetName = self::DEFAULT_SHEETNAME)
    {
        $this->path = $path;

        $spreadsheet = new Spreadsheet();
        $spreadsheet->getActiveSheet()->setTitle($sheetName);
        $this->sheetNames[] = $sheetName;
        $this->spreadsheet = $spreadsheet;

        $this->xslxWriter = new XlsxWriter($spreadsheet);
    }

    public function save(): void
    {
        ServerUtils::createPathIfNotExists($this->path);
        $this->xslxWriter->save($this->path);
    }

    public function saveAs(string $path): void
    {
        $this->path = $path;
        $this->save();
    }
}


class ExcelWriter extends Excel
{
    public function addSheet(string $sheetName): void
    {
        $this->spreadsheet->createSheet();
        $this->spreadsheet->setActiveSheetIndex($this->spreadsheet->getSheetCount() - 1);
        $this->spreadsheet->getActiveSheet()->setTitle($sheetName);

        $this->sheetNames[] = $sheetName;
    }

    public function removeSheet(string $sheetName): bool
    {
        $sheetIndex = $this->spreadsheet->getIndex($this->spreadsheet->getSheetByName($sheetName));
        if ($sheetIndex === null) {
            return false;
        }

        $this->spreadsheet->removeSheetByIndex($sheetIndex);
        unset($this->sheetNames[$sheetIndex]);

        return true;
    }

    public function removeDefaultSheet(): bool
    {
        return $this->removeSheet(self::DEFAULT_SHEETNAME);
    }

    public function setActiveSheet(string $sheetName): void
    {
        $sheetIndex = $this->spreadsheet->getIndex($this->spreadsheet->getSheetByName($sheetName));
        if ($sheetIndex === null) {
            throw new \Exception("Sheet $sheetName not found");
        }

        $this->spreadsheet->setActiveSheetIndex($sheetIndex);
    }

    public function renameActiveSheet(string $sheetName): string
    {
        $sheet = $this->spreadsheet->getActiveSheet();
        $sheet->setTitle($sheetName);

        $this->sheetNames[$this->spreadsheet->getIndex($sheet)] = $sheetName;

        return $sheetName;
    }

    /** @param ExcelCell[] $row */
    public function addRow(array $row): void
    {
        array_unshift($row, new ExcelCell('', borderType: BorderType::NONE));

        $sheet = $this->spreadsheet->getActiveSheet();
        $rowIndex = $sheet->getHighestRow() + 1;

        $colIndex = 0;

        foreach ($row as $cell) {
            if ($cell === null) {
                continue;
            }

            $columnLetter = Coordinate::stringFromColumnIndex($colIndex + 1);
            $cellCoordinate = $columnLetter . $rowIndex;

            $sheet->setCellValue($cellCoordinate, $cell->value);

            $sheet->getStyle($cellCoordinate)->applyFromArray([
                'font' => [
                    'bold' => $cell->bold,
                    'color' => $cell->color->getExcelFriendly(),
                ],
                'fill' => [
                    'fillType' => $cell->fill->value,
                    'startColor' => $cell->bgColor->getExcelFriendly(),
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => $cell->borderType->value,
                        'color' => Color::BLACK->getExcelFriendly(),
                    ],
                ],
            ]);

            $colIndex++;
        }

        $this->autoResizeColumns($sheet, $colIndex);
    }

    private function autoResizeColumns(Worksheet $sheet, int $colIndex): void
    {
        for ($i = 1; $i <= $colIndex; $i++) {
            $columnLetter = Coordinate::stringFromColumnIndex($i);
            $sheet->getColumnDimension($columnLetter)->setAutoSize(true);
        }
    }
}


class ExcelCell
{
    public string $value;
    public Color $color;
    public bool $bold;
    public Color $bgColor;
    public FillType $fill;
    public BorderType $borderType;

    public function __construct(
        string $value,
        Color $color = Color::BLACK,
        bool $bold = false,
        Color $bgColor = Color::WHITE,
        FillType $fill = FillType::NONE,
        BorderType $borderType = BorderType::THIN
    ) {
        $this->value = $value;
        $this->color = $color;
        $this->bold = $bold;
        $this->bgColor = $bgColor;
        $this->fill = $fill;
        $this->borderType = $borderType;
    }
}


class ExcelRow
{
    /** @var ExcelCell[] */
    private array $row = [];

    public static function builder(): static
    {
        return new static();
    }

    public function add(string $value): static
    {
        $this->row[] = new ExcelCell($value);
        return $this;
    }

    public function addCell(ExcelCell $cell): static
    {
        $this->row[] = $cell;
        return $this;
    }

    /** @param ExcelCell[] $cells */
    public function addCells(array $cells): static
    {
        foreach ($cells as $cell) {
            $this->addCell($cell);
        }
        return $this;
    }

    /** @return ExcelCell[] */
    public function get(): array
    {
        return $this->row;
    }
}
