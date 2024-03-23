<?php

declare(strict_types=1);

namespace Eclipxe\Enum\Exceptions;

use Throwable;

class ValueNotFoundException extends GenericNotFoundException
{
    private const EXCODE = 0;

    public static function create(string $className, string $value, Throwable $previous = null): self
    {
        // StatusEnum value x was not found
        return new self(static::formatGenericMessage($className, 'value', $value), self::EXCODE, $previous);
    }
}
