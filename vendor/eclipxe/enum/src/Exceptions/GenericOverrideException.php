<?php

declare(strict_types=1);

namespace Eclipxe\Enum\Exceptions;

use OutOfRangeException;

abstract class GenericOverrideException extends OutOfRangeException implements EnumExceptionInterface
{
    protected static function formatGenericMessage(string $className, string $typeName, string $value): string
    {
        return sprintf('%s cannot override %s to %s', $className, $typeName, $value);
    }
}
