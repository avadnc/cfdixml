<?php

declare(strict_types=1);

namespace Eclipxe\Enum\Exceptions;

use BadMethodCallException as PhpBadMethodCallException;
use Throwable;

class BadMethodCallException extends PhpBadMethodCallException implements EnumExceptionInterface
{
    private const EXCODE = 0;

    public static function create(string $className, string $methodName, Throwable $previous = null): self
    {
        $message = sprintf('Call to undefined method %s::%s', $className, $methodName);
        return new self($message, self::EXCODE, $previous);
    }
}
