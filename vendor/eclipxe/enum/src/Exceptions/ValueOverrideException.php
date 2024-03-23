<?php

declare(strict_types=1);

namespace Eclipxe\Enum\Exceptions;

use Throwable;

class ValueOverrideException extends GenericOverrideException
{
    private const EXCODE = 0;

    public static function create(string $className, string $value, Throwable $previous = null): self
    {
        // StatusEnum cannot override value to x
        return new self(static::formatGenericMessage($className, 'value', $value), self::EXCODE, $previous);
    }
}
