<?php

namespace TodoListGold\Exceptions;

use Exception;
use Throwable;

class NotImplementedException extends Exception
{
    public function __construct($message = "Method Not Implemented", $code = 501, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}


class NotFoundException extends Exception
{
    public function __construct($message = "Not Found", $code = 404, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}


class RecursionException extends Exception
{
    public function __construct($message = "Recursion Detected", $code = 500, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}


class InvalidStateException extends Exception
{
    public function __construct($message = "Invalid State", $code = 500, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
