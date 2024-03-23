<?php

declare(strict_types=1);

namespace Eclipxe\Enum\Exceptions;

use Throwable;

class IndexNotFoundException extends GenericNotFoundException
{
    private const EXCODE = 0;

    public static function create(string $className, string $value, Throwable $previous = null): self
    {
        // StatusEnum index x was not found
        return new self(static::formatGenericMessage($className, 'index', $value), self::EXCODE, $previous);
    }
}
