<?php

namespace TodoListGold\IO;

use DOMDocument;
use DOMElement;
use DOMNameSpaceNode;
use DOMNode;
use Exception;
use Throwable;
use ZipArchive;
use Shapefile\Geometry\Geometry;
use Shapefile\ShapefileException;
use Shapefile\ShapefileReader;
use TodoListGold\Utils\Dev\ServerUtils;

class Path
{
    private string $path;

    public function __construct(string $path)
    {
        $this->path = $path;
    }

    public static function joinObj(string ...$paths): static
    {
        $paths = array_map(fn($path) => self::trimSlashes($path), $paths);
        return new static(implode(DS, $paths));
    }

    public static function join(string ...$paths): string
    {
        return self::joinObj(...$paths)->get();
    }

    public static function joinRootObj(string ...$paths): static
    {
        return self::joinObj(ROOT_DIR, ...$paths);
    }

    public static function joinRoot(string ...$paths): string
    {
        return self::joinRootObj(...$paths)->get();
    }

    public function up(int $times = 1): void
    {
        $this->path = dirname($this->path, $times);
    }

    public function down(string $part): void
    {
        $this->path = ($this->isDir())
            ? $this->path . DS . $part
            : dirname($this->path) . DS . $part;
    }

    public function downAny(): void
    {
        # Enter in the first directory, list all dirs
        $dirs = glob($this->path . DS . '*', GLOB_ONLYDIR);
        if (empty($dirs)) {
            return;
        }

        $this->path = $dirs[0];
    }

    public function downRegex(string $regex): void
    {
        $match = ServerUtils::firstRegex($this->path, $regex);
        if ($match === null) {
            return;
        }

        $this->path = $match;
    }

    public function isFile(): bool
    {
        return is_file($this->path);
    }

    public function isDir(): bool
    {
        return is_dir($this->path);
    }

    public function exists(): bool
    {
        return file_exists($this->path);
    }

    public function getExtension(): string
    {
        return pathinfo($this->path, PATHINFO_EXTENSION);
    }

    public function getFilename(): string
    {
        return pathinfo($this->path, PATHINFO_FILENAME);
    }

    public function getBasename(): string
    {
        return pathinfo($this->path, PATHINFO_BASENAME);
    }

    public function getDirname(): string
    {
        return pathinfo($this->path, PATHINFO_DIRNAME);
    }

    public function getRealPath(): string
    {
        return realpath($this->path);
    }

    public function getRelativePath(string $relative = ROOT_DIR): string
    {
        return str_replace($relative, '', $this->path);
    }

    public function get(): string
    {
        return $this->path;
    }

    public function copy(): static
    {
        return self::joinObj($this->path);
    }

    public static function trimSlashes(string $path): string
    {
        $path = rtrim($path, '/\\');

        return $path;
    }
}


class FileReader
{
    public readonly string $path;

    public function __construct(string $path)
    {
        $this->path = $path;
    }

    public function read(): string
    {
        return file_get_contents($this->path);
    }
}


class ImageConverter
{
    private ?string $path = null;
    private ?string $base64 = null;

    public static function constructFromPath(string $path): static
    {
        $ent = new static();
        $ent->path = $path;

        return $ent;
    }

    public static function constructFromBase64(string $base64): static
    {
        $ent = new static();
        $ent->base64 = $base64;

        return $ent;
    }

    public function getBase64(): string
    {
        if ($this->base64 === null) {
            $this->base64 = base64_encode(file_get_contents($this->path));
        }

        return $this->base64;
    }

    public function saveAs(string $path): bool
    {
        ServerUtils::createPathIfNotExists($path);

        $result = file_put_contents($path, base64_decode($this->base64));
        if ($result) {
            $this->path = $path;
        }

        return $result;
    }
}


class XMLReader
{
    public readonly string $pathOrContent;
    protected DOMDocument $dom;

    public static function fromPath(string $pathOrContent): static
    {
        $ent = new static();

        $ent->pathOrContent = $pathOrContent;
        $ent->dom = new DOMDocument();
        $ent->dom->load($pathOrContent);

        return $ent;
    }

    public static function fromContent(string $pathOrContent): static
    {
        $ent = new static();

        $ent->pathOrContent = $pathOrContent;
        $ent->dom = new DOMDocument();
        $ent->dom->loadXML($pathOrContent);

        return $ent;
    }

    # NOTE (Antonio): Assumes the tag is unique and exists
    public function getTagValue(string $tagName, string|null $default = null): string|null
    {
        $tag = $this->getTag($tagName);
        return $tag->nodeValue ?? $default;
    }

    public function getTag(string $tagName): DOMNode|DOMElement|DOMNameSpaceNode|null
    {
        return $this->dom->getElementsByTagName($tagName)->item(0);
    }

    public function docToString(): string
    {
        return $this->dom->saveXML();
    }
}


class CSV
{
    public const CSV_EXT = '.csv';
    public const CSV_EXT_REGEX = '/\.csv$/';

    public string $path;
    private string $delimiter;
    private string $enclosure;
    private string $escape;
    private string $content;
    private array $headers;

    public const SEP_STR = 'sep=';

    protected function __construct(string $path, string $delimiter = ',', string $enclosure = '"', string $escape = '\\')
    {
        $this->path = $path;
        $this->delimiter = $delimiter;
        $this->enclosure = $enclosure;
        $this->escape = $escape;
    }

    public static function load(string $path, string $delimiter = ',', string $enclosure = '"', string $escape = '\\'): static
    {
        $ent = new static($path, $delimiter, $enclosure, $escape);

        $ent->content = file_get_contents($path);
        $ent->getDelimiterFromDoc();
        $ent->getHeaders();

        return $ent;
    }

    public static function create(CSVData $csvData, string $path = '', string $delimiter = ';', string $enclosure = '"', string $escape = '\\'): static
    {
        $ent = new static($path, $delimiter, $enclosure, $escape);

        $ent->content = self::arrayToCsvString($csvData);
        $ent->getHeaders();

        return $ent;
    }

    private static function arrayToCsvString(CSVData $csvData, string $delimiter = ';', string $enclosure = '"', string $escape = '\\'): string
    {
        $handle = fopen('php://temp', 'r+');
        fputcsv($handle, $csvData->headers, $delimiter, $enclosure, $escape);
        foreach ($csvData->values as $row) {
            fputcsv($handle, $row, $delimiter, $enclosure, $escape);
        }
        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return $csv;
    }

    private function writeWithBOM(string $content): bool
    {
        $content = "\xEF\xBB\xBF$content";
        return file_put_contents($this->path, $content) !== false;
    }

    public function save(): bool
    {
        ServerUtils::createPathIfNotExists($this->path);
        return $this->writeWithBOM($this->content);
    }

    public function saveAs(string $path): bool
    {
        $this->path = $path;
        return self::save();
    }

    public function getDelimiterFromDoc(): ?string
    {
        # Search the line with sep=
        $lines = explode("\n", $this->content, 4);
        foreach ($lines as $line) {
            if (str_starts_with($line, self::SEP_STR)) {
                $docDelimiter = trim(substr($line, strlen(self::SEP_STR)));
                $this->delimiter = $docDelimiter;
                return $docDelimiter;
            }
        }

        return null;
    }

    /**
     * @return string[]
     */
    public function getHeaders(): array
    {
        $lines = explode("\n", $this->content, 4);
        foreach ($lines as $line) {
            if (str_starts_with($line, self::SEP_STR)) {
                continue;
            }

            $headers = str_getcsv($line, $this->delimiter, $this->enclosure, $this->escape);
            break;
        }

        $this->headers = $headers;
        return $headers;
    }

    public function getAsAssociativeArray(string|int|null $where = null, mixed $filter = null): array
    {
        $data = [];
        
        if (!file_exists($this->path) || !is_readable($this->path)) {
            return $data;
        }

        $handle = fopen($this->path, 'r');
        if (!$handle) {
            return $data;
        }

        while (($values = fgetcsv($handle, 0, $this->delimiter, $this->enclosure, $this->escape)) !== false) {
            if (empty($values) || str_starts_with($values[0], self::SEP_STR)) {
                continue;
            }

            $row = array_combine($this->headers, array_slice($values, 0, count($this->headers)));

            if ($row === false) {
                continue;
            }

            if ($where === null || ($row[$where] ?? null) == $filter) {
                $data[] = $row;
            }
        }

        fclose($handle);
        return $data;
    }
}


class CSVData
{
    /** @var string[] */
    public array $headers;
    public array $values;

    public function __construct(array $headers, array $rows)
    {
        $columns = count($headers);
        foreach ($rows as $row) {
            if (count($row) !== $columns) {
                throw new Exception("Headers and Values must have the same length");
            }
        }

        $this->headers = $headers;
        $this->values = $rows;
    }

    /** @return string[] */
    public static function constructHeaders(string ...$headers): array
    {
        return $headers;
    }
}


class ShpReader
{
    public const SHAPE_EXT = '.shp';
    public const SHAPE_EXT_REGEX = '/\.shp$/';

    public readonly string $path;
    protected ShapefileReader $reader;

    public function __construct(string $path)
    {
        $this->path = $path;
        $this->reader = new ShapefileReader($path);
    }

    /**
     * This Function Assumes incoming Shape just contains Points
     * @return array<array<string, mixed>>
     * @throws ShpPointsException
     */
    public function getRows(): ?array
    {
        $records = [];
        $ex = null;

        while ($record = $this->reader->fetchRecord()) {
            $coords = $record->getArray();
            if (count($coords) !== 2) {
                $ex = ShpPointsException::fromGeometry($record, $ex);
            }
            $attributes = $record->getDataArray();
            $data = array_merge($coords, $attributes);

            $records[] = $data;
        }

        $this->reader->rewind();

        if ($ex !== null) {
            throw $ex;
        }

        return $records;
    }
}


class ShpPointsException extends Exception
{
    public function __construct($message = "Shapefile must only contain Points", $code = 500, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    public static function fromGeometry(Geometry $geom, Throwable $previous = null): self
    {
        try {
            $possibleCodigo0 = $geom->getData('codigo0');
        } catch (ShapefileException $e) {
            $possibleCodigo0 = 'codigo0NotFound';
        }

        return new self("Geometry must be a Point | $possibleCodigo0 ({$geom->getArray()})", previous: $previous);
    }
}


class StaticZipReader
{
    public const SUFFIX  = '.zip';
    public static $path;
    protected static ZipArchive $zip;

    public static function loadFile(string $path): void
    {
        if (!isset(self::$zip)) {
            self::$zip = new ZipArchive();
        }
        self::$zip->open($path);
        self::$path = $path;
    }
    
    public static function extraxtHere(string $path = ''): ?string
    {
        if (!empty($path)) {
            self::loadFile($path);
        }

        $parentFolder = dirname(self::$path);
        $result = self::$zip->extractTo($parentFolder);

        return $result ? $parentFolder : null;
    }

    public static function extractIn(string $path = ''): ?string
    {
        if (!empty($path)) {
            self::loadFile($path);
        }

        $fileNameWoExt = basename(self::$path, self::SUFFIX);
        $parentFolder = dirname(self::$path);
        $dstFolder = Path::join($parentFolder, $fileNameWoExt);

        $result = self::$zip->extractTo($dstFolder);

        return $result ? $dstFolder : null;
    }
}
