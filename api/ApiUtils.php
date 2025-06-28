<?php

namespace TodoListGold\Api;

enum HttpCode: int
{
    case OK = 200;
    case CREATED = 201;
    case ACCEPTED = 202;
    case NON_AUTHORITATIVE_INFORMATION = 203;
    case NO_CONTENT = 204;
    case MULTIPLE_CHOICES = 300;
    case BAD_REQUEST = 400;
    case UNAUTHORIZED = 401;
    case FORBIDDEN = 403;
    case NOT_FOUND = 404;
    case INTERNAL_SERVER_ERROR = 500;
    case NOT_IMPLEMENTED = 501;

    public function getStatus(): bool
    {
        $value = $this->value;
        return $value >= HttpCode::OK->value && $value < HttpCode::MULTIPLE_CHOICES->value;
    }
}


enum RequestMethod: string
{
    case GET = 'GET';
    case POST = 'POST';
    case PUT = 'PUT';
    case DELETE = 'DELETE';
    case PATCH = 'PATCH';
    case OPTIONS = 'OPTIONS';
}


enum ContentType
{
    case JSON;
    case XML;
    case HTML;
}


enum ServerKeys
{
    case REQUEST_METHOD;
}


class ApiUtils
{
    public static function getRequestMethod(): RequestMethod
    {
        $requestMethod = $_SERVER[ServerKeys::REQUEST_METHOD->name];
        return RequestMethod::from($requestMethod);
    }

    public static function getBody(): string
    {
        return file_get_contents('php://input');
    }

    public static function getJsonBody(): array|null
    {
        return json_decode(self::getBody(), associative: true) ?? [];
    }

    public static function getParam(string $paramName, mixed $backingValue = null): mixed
    {
        return $_GET[$paramName] ?? $backingValue;
    }

    public static function getAction(): string
    {
        return self::getParam('action', '');
    }

    public static function returnNotImplementedPoint(): string
    {
        http_response_code(HttpCode::NOT_IMPLEMENTED->value);
        return json_encode(['status' => false, 'message' => 'Endpoint Not implemented Yet']);
    }

    public static function returnBadRequestPoint(string $cause = 'Cause not Specified'): string
    {
        http_response_code(HttpCode::BAD_REQUEST->value);
        return json_encode(['status' => false, 'message' => 'Bad Request', 'cause' => $cause]);
    }
}


class Response
{
    public const KEY_STATUS = 'status';
    public const KEY_MESSAGE = 'message';

    public bool $status = false;
    public string $message = '';

    public function __construct(bool $status, string $message)
    {
        $this->status = $status;
        $this->message = $message;
    }

    public static function constructFromArray(array $data): self
    {
        $status = $data[Response::KEY_STATUS] ?? throw new \InvalidArgumentException('Status key is required');
        $message = $data[Response::KEY_MESSAGE] ?? throw new \InvalidArgumentException('Message key is required');

        return new self($status, $message);
    }
}


class ControllerBase
{
    public function badRequest(string $message): never
    {
        echo $this->fttResponse(HttpCode::BAD_REQUEST, $message);
        ServerUtils::死ね();
    }

    public function response(HttpCode $responseCode, string $response): string
    {
        http_response_code($responseCode->value);
        return $response;
    }


    public function fttResponse(HttpCode $responseCode, string $message): string
    {
        http_response_code($responseCode->value);
        $status = $responseCode->getStatus();
        $response = new Response($status, $message);
        return json_encode($response);
    }


    public function ifNullDie(mixed $value): mixed
    {
        if ($value !== null) {
            return $value;
        }

        $response = $this->fttResponse(HttpCode::NOT_FOUND, 'Resource not found');
        ServerUtils::死ね(1, $response);
    }
}
