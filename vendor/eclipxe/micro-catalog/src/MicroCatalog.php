<?php

declare(strict_types=1);

namespace Eclipxe\MicroCatalog;

use Eclipxe\MicroCatalog\Exceptions\BadMethodCallException;
use Eclipxe\MicroCatalog\Exceptions\IndexTypeError;

/**
 * @template TEntry
 */
abstract class MicroCatalog
{
    /** @var string|int */
    private $index;

    /** @var TEntry */
    private $value;

    /**
     * MicroCatalog constructor.
     *
     * @param string|int $index
     */
    public function __construct($index)
    {
        /**
         * @psalm-suppress DocblockTypeContradiction
         * @phpstan-ignore-next-line
         */
        if (! is_string($index) && ! is_int($index)) {
            throw new IndexTypeError(static::class, $index);
        }
        $this->index = $index;
        $this->value = $this->getEntriesArray()[$this->index] ?? $this->getEntryValueOnUndefined();
    }

    /**
     * Override this function to set up the predefined values
     *
     * @return array<TEntry>
     */
    abstract public static function getEntriesArray(): array;

    /**
     * Override this function to set a default value if the index is not found
     * You can even throw an exception to not allow an element without definition
     *
     * @return TEntry
     */
    abstract public function getEntryValueOnUndefined();

    public function isUndefined(): bool
    {
        return ($this->getEntryValue() === $this->getEntryValueOnUndefined());
    }

    /**
     * @param string $name
     * @param array<mixed> $arguments
     * @return mixed
     */
    public function __call(string $name, array $arguments)
    {
        if (strlen($name) > 2 && 0 === strcasecmp('is', substr($name, 0, 2))) {
            return (0 === strcasecmp(substr($name, 2), $this->getEntryId()));
        }

        if (strlen($name) > 3 && 0 === strcasecmp('get', substr($name, 0, 3))) {
            return $this->getEntryValueWithKey(lcfirst(substr($name, 3)));
        }

        throw new BadMethodCallException(static::class, $name);
    }

    /**
     * @return int|string
     */
    public function getEntryIndex()
    {
        return $this->index;
    }

    public function getEntryId(): string
    {
        return strval($this->index);
    }

    /** @return TEntry */
    public function getEntryValue()
    {
        return $this->value;
    }

    /**
     * @param string $key
     * @return TEntry|null
     */
    protected function getEntryValueWithKey(string $key)
    {
        $return = null;
        /** @var mixed $value */
        $value = $this->getEntryValue();
        if (is_array($value)) {
            /** @var TEntry $return */
            $return = $value[$key] ?? null;
        } elseif (is_object($value)) {
            /** @var TEntry $return */
            $return = $value->{$key} ?? null;
        }
        return $return;
    }
}
