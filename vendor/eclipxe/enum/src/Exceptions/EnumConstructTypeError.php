<?php

declare(strict_types=1);

namespace Eclipxe\Enum\Exceptions;

use Throwable;
use TypeError;

class EnumConstructTypeError extends TypeError implements EnumExceptionInterface
{
    private const EXCODE = 0;

    public static function create(string $className, Throwable $previous = null): self
    {
        $message = sprintf('Argument passed to %s must be integer for index or string for value', $className);
        return new self($message, self::EXCODE, $previous);
    }
}
