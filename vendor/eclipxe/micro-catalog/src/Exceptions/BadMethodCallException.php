<?php

declare(strict_types=1);

namespace Eclipxe\MicroCatalog\Exceptions;

use BadMethodCallException as PhpBadMethodCallException;

class BadMethodCallException extends PhpBadMethodCallException implements MicroCatalogException
{
    /** @var string */
    private $className;

    /** @var string */
    private $methodName;

    public function __construct(string $className, string $methodName)
    {
        parent::__construct(sprintf('Call to undefined method %s::%s', $className, $methodName));
        $this->className = $className;
        $this->methodName = $methodName;
    }

    public function getClassName(): string
    {
        return $this->className;
    }

    public function getMethodName(): string
    {
        return $this->methodName;
    }
}
