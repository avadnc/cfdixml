<?php

declare(strict_types=1);

namespace Eclipxe\MicroCatalog\Exceptions;

use TypeError;

class IndexTypeError extends TypeError implements MicroCatalogException
{
    /** @var string */
    private $className;

    /** @var mixed */
    private $argument;

    /**
     * IndexTypeError constructor.
     *
     * @param string $className
     * @param mixed $argument
     */
    public function __construct(string $className, $argument)
    {
        parent::__construct(sprintf('Argument passed to %s must be integer or string', $className));
        $this->argument = $argument;
        $this->className = $className;
    }

    public function getClassName(): string
    {
        return $this->className;
    }

    /** @return mixed */
    public function getArgument()
    {
        return $this->argument;
    }
}
