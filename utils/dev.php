<?php

namespace TodoListGold\Utils\Dev;

use TodoListGold\IO\Path;
use TodoListGold\Utils\Utils;
use TodoListGold\Utils\WebUtils;

use const TodoListGold\Constants\H_ASYNC_CURL;
use const TodoListGold\Constants\H_ASYNC_MAILER;

class EmptyClass
{
}


abstract class BaseClass
{
    protected function _s($s): string
    {
        return (string) $s;
    }

    protected function git(bool $condition, mixed $v1, mixed $v2 = null): mixed
    {
        return $condition ? $v1 : $v2;
    }
}


enum OS: string
{
    case WINDOWS = 'Windows';
    case BSD = 'BSD';
    case DARWIN = 'Darwin';
    case SOLARIS = 'Solaris';
    case LINUX = 'Linux';
    case UNKNOWN = 'Unknown';

    public static function getOS(): self
    {
        $os = PHP_OS_FAMILY;
        return self::from($os);
    }
}


class ServerUtils
{
    public const FOLDER_UPLOAD_NAME = 'folder';
    public const FILE_UPLOAD_NAME = 'file';
    public const FILES_UPLOAD_NAME = 'files';

    public const TEMP_DIR = ROOT_DIR . 'temp' . DIRECTORY_SEPARATOR;

    public static function getTemporalDir(): string
    {
        return self::TEMP_DIR . Utils::generateRandomName(32) . DIRECTORY_SEPARATOR;
    }

    public static function saveSingleFolderIntoServer(string $uploadName = self::FOLDER_UPLOAD_NAME): string
    {
        $_FOLDER = $_FILES[$uploadName];
        $tempDir = self::getTemporalDir();

        if (empty($_FOLDER['name'][0])) {
            throw new \RuntimeException('No files were uploaded.');
        }

        $firstFilePath = $_FOLDER['full_path'][0];
        $firstFolder = explode('/', $firstFilePath)[0];
        $uploadDir = "{$tempDir}{$firstFolder}";

        foreach ($_FOLDER['full_path'] as $key => $relPath) {
            $dirname = dirname($relPath);
            $filename = basename($relPath);

            $path = Path::join($tempDir, $dirname, $filename);
            $dir = dirname($path);
            if (!is_dir($dir)) {
                mkdir($dir, 0777, true);
            }

            $tmpPath = $_FOLDER['tmp_name'][$key];
            move_uploaded_file($tmpPath, $path);
        }

        return $uploadDir;
    }

    public static function saveSingleFileIntoServer(string $uploadName = self::FILE_UPLOAD_NAME): ?string
    {
        $_FILE = $_FILES[$uploadName];
        if (empty($_FILE['tmp_name'])) {
            return null;
        }

        $tmpFolder = self::getTemporalDir();
        $tmpFile = $_FILE['tmp_name'];
        $tmpFilename = $_FILE['name'];
        $finalPath = Path::join($tmpFolder, $tmpFilename);

        self::createPathIfNotExists($finalPath);
        return move_uploaded_file($tmpFile, $finalPath) ? $finalPath : null;
    }

    public static function saveFilesIntoServer(string $uploadName = self::FILES_UPLOAD_NAME): ?string
    {
        $__FILES = $_FILES[$uploadName];
        if (empty($__FILES['tmp_name'])) {
            return null;
        }

        $tempDir = self::getTemporalDir();

        foreach ($__FILES['tmp_name'] as $key => $tmpFile) {
            $filename = $__FILES['name'][$key];
            $absPath = Path::join($tempDir, $filename);
            
            self::createPathIfNotExists($absPath);
            move_uploaded_file($tmpFile, $absPath);
        }

        return $tempDir;
    }

    /** Alias of unlink() */
    public static function deleteFile(string $path): bool
    {
        return unlink($path);
    }

    public static function deleteDirectory(string $dir): bool
    {
        if (!is_dir($dir)) {
            return false;
        }

        $entries = array_diff(scandir($dir), ['.', '..']);
        foreach ($entries as $entry) {
            $ds = DS;
            $path = "{$dir}{$ds}{$entry}";
            (is_dir($path)) ? self::deleteDirectory($path) : self::deleteFile($path);
        }

        return rmdir($dir);
    }

    /** Alias of file_exists() */
    public static function pathExists(string $path): bool
    {
        return file_exists($path);
    }

    public static function createPathIfNotExists(string $path): void
    {
        $isFile = is_file($path);
        $dir = dirname($path);

        if (!self::pathExists($dir)) {
            mkdir($dir, 0777, true);
        }

        if ($isFile && !self::pathExists($path)) {
            touch($path);
        }
    }

    public static function firstRegex(string $dir, string $regex): ?string
    {
        $ds = DIRECTORY_SEPARATOR;

        $pathes = glob("{$dir}{$ds}*");
        foreach ($pathes as $path) {
            $filename = basename($path);

            if (preg_match($regex, $filename)) {
                return $path;
            }
        }

        return null;
    }

    /** @return string[] If it's empty == no matches */
    public static function regex(string $dir, string $regex): array
    {
        $ds = DS;
        $matches = [];

        $pathes = glob("{$dir}{$ds}*");
        foreach ($pathes as $path) {
            $filename = basename($path);

            if (preg_match($regex, $filename)) {
                $matches[] = $path;
            }
        }

        return $matches;
    }

    public static function appendFile(string $pathIn, string $pathOut): void
    {
        self::createPathIfNotExists($pathOut);
        
        $content = file_get_contents($pathIn);
        file_put_contents($pathOut, $content, FILE_APPEND);
    }

    public static function sendFile(string $path): never
    {
        $filename = basename($path);
        $filesize = filesize($path);

        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header("Content-Disposition: attachment; filename=\"$filename\"");
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header("Content-Length: $filesize");

        readfile($path);
        self::死ね();
    }

    public static function redirect(
        string $page = 'inicio',
        int|string $id = '',
        int $accion = -1,
        string $extra = ''
    ): never {
        @header("location: /?page=$page&id=$id&accion=$accion$extra");
        self::死ね();
    }

    public static function redirectP(
        string $page = 'inicio',
        int|string $id = '',
        int $accion = -1,
        ...$extra
    ): never {
        for ($index = 0; $index < count($extra); $index += 2) {
            $paramName = $extra[$index];
            $paramValue = $extra[$index + 1];

            $extra[$index] = "$paramName=$paramValue";
        }

        $extra = implode('&', $extra);

        self::redirect($page, $id, $accion, $extra);
    }

    public static function serialize(mixed $data): string
    {
        return base64_encode(serialize($data));
    }

    public static function unserialize(string $data): mixed
    {
        return unserialize(base64_decode($data));
    }

    public static function curl(string $url): ?string
    {
        $ch = curl_init($url);
        if ($ch === false) {
            return null;
        }

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        if ($response === false) {
            return null;
        }

        curl_close($ch);

        return $response;
    }

    public static function asyncExec(string $helperPath, ...$params): void
    {
        $os = OS::getOS();

        $tempFiles = [];
        foreach ($params as $param) {
            $tempFile = tempnam(sys_get_temp_dir(), 'param_');
            $serializedParam = self::serialize($param);
            file_put_contents($tempFile, $serializedParam);
            $tempFiles[] = $tempFile;
        }

        $escapedFilePaths = array_map('escapeshellarg', $tempFiles);
        $filePathsStr = implode(' ', $escapedFilePaths);

        switch ($os) {
            case OS::WINDOWS:
                $command = sprintf(
                    'start /b php "%s" %s > NUL 2>&1',
                    $helperPath,
                    $filePathsStr
                );
                break;
            case OS::LINUX:
                $command = sprintf(
                    'php "%s" %s > /dev/null 2>&1 &',
                    $helperPath,
                    $filePathsStr
                );
                break;
            default:
                throw new \RuntimeException("Unsupported OS: {$os->value}");
        }

        pclose(popen($command, 'r'));
    }

    public static function asyncWriter(string $path, string $content, bool $append = false): void
    {
        self::createPathIfNotExists($path);
        file_put_contents($path, "$content\n", $append ? FILE_APPEND : 0);

        return;  # NOTE (Antonio): Temporarily disabled asyncWriter
        self::asyncExec(H_ASYNC_WRITER, $path, $content, $append ? 'true' : 'false');
    }

    public static function asyncMailer($mail): void
    {
        self::asyncExec(H_ASYNC_MAILER, $mail);
    }

    public static function asyncCurl(string $url): void
    {
        self::asyncExec(H_ASYNC_CURL, $url);
    }

    public static function 死ね(int $status = 0, string $message = ''): never
    {
        if (!empty($message)) {
            print_r($message);
            error_log($message);
        }

        die($status);
    }
}


class SessionPrinter
{
    public static function logSessionValues(array $session): void
    {
        echo WebUtils::consoleInfo("Session Values:");
        foreach ($session as $key => $value) {
            echo WebUtils::consoleInfo("$key: $value");
        }
    }

    public static function getEncodedValues(): string
    {
        $encodedArrays = [];

        if (isset($_POST)) {
            $encodedArrays['POST'] = $_POST;
        }
        if (isset($_GET)) {
            $encodedArrays['GET'] = $_GET;
        }
        if (isset($_SESSION)) {
            $encodedArrays['SESSION'] = $_SESSION;
        }

        return json_encode($encodedArrays);
    }
}
