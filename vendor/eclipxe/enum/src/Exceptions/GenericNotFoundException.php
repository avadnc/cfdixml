<?php

declare(strict_types=1);

namespace Eclipxe\Enum\Exceptions;

use OutOfRangeException;

abstract class GenericNotFoundException extends OutOfRangeException implements EnumExceptionInterface
{
    protected static function formatGenericMessage(string $className, string $typeName, string $value): string
    {
        return sprintf('%s %s %s was not found', $className, $typeName, $value);
    }
}
