<?php

declare(strict_types=1);

namespace Eclipxe\Enum;

use Eclipxe\Enum\Exceptions\BadMethodCallException;
use Eclipxe\Enum\Exceptions\EnumConstructTypeError;
use Eclipxe\Enum\Exceptions\IndexNotFoundException;
use Eclipxe\Enum\Exceptions\ValueNotFoundException;
use Eclipxe\Enum\Internal\Entries;
use Eclipxe\Enum\Internal\EntriesPopulator;
use Eclipxe\Enum\Internal\Entry;
use Throwable;

abstract class Enum
{
    /** @var Entry */
    private $content;

    final public function value(): string
    {
        return $this->content->value();
    }

    final public function index(): int
    {
        return $this->content->index();
    }

    final public function __toString(): string
    {
        return $this->content->value();
    }

    /**
     * Enum constructor.
     *
     * @param string|int|mixed $valueOrIndex
     *
     * @throws IndexNotFoundException if received argument is an integer and it was not found in indices
     * @throws ValueNotFoundException if received argument is a string and it was not found in values
     * @throws EnumConstructTypeError if received argument is not an integer, string, scalar or object
     */
    final public function __construct($valueOrIndex)
    {
        // convert to string if object
        if (is_object($valueOrIndex)) {
            try {
                $valueOrIndex = strval($valueOrIndex);
            } catch (Throwable $exception) {
                throw EnumConstructTypeError::create(static::class, $exception);
            }
        }

        if (is_int($valueOrIndex)) { // is index
            $entry = static::currentEntries()->findEntryByIndex($valueOrIndex);
            if (null === $entry) {
                throw IndexNotFoundException::create(static::class, strval($valueOrIndex));
            }
        } elseif (is_string($valueOrIndex)) { // is value
            $entry = static::currentEntries()->findEntryByValue($valueOrIndex);
            if (null === $entry) {
                throw ValueNotFoundException::create(static::class, $valueOrIndex);
            }
        } else {
            throw EnumConstructTypeError::create(static::class);
        }

        $this->content = $entry;
    }

    /**
     * @param string $name
     * @param mixed[] $arguments
     * @return mixed
     */
    public function __call(string $name, array $arguments)
    {
        if (strlen($name) > 2 && 'is' === substr($name, 0, 2)) {
            $entry = static::currentEntries()->findEntryByName(substr($name, 2));
            return (null !== $entry && $this->content->equals($entry));
        }

        throw BadMethodCallException::create(static::class, $name);
    }

    /**
     * @param string $name
     * @param mixed[] $arguments
     * @return static
     */
    public static function __callStatic(string $name, array $arguments)
    {
        $entry = static::currentEntries()->findEntryByName($name);
        if (null !== $entry) {
            return new static($entry->index());
        }

        throw BadMethodCallException::create(static::class, $name);
    }

    /**
     * Obtain the list of registered possible values as an array of indices and values
     *
     * @return array<int, string>
     */
    final public static function toArray(): array
    {
        return static::currentEntries()->toIndexValueArray();
    }

    /**
     * Method to override values
     *
     * It must return an array with key as the name declared in docblock
     * and value as the string value to use.
     *
     * Example:
     * method self extraSmall()
     * return ['extraSmall' => 'xs', ...];
     *
     * @return array<string, string>
     */
    protected static function overrideValues(): array
    {
        return [];
    }

    /**
     * Method to override indices
     *
     * It must return an array with key as the name declared in docblock
     * and value as the integer index to use.
     *
     * Example:
     * method self second()
     * return ['second' => 2, ...];
     *
     * @return array<string, int>
     */
    protected static function overrideIndices(): array
    {
        return [];
    }

    final protected static function currentEntries(): Entries
    {
        /*
         * $cache contains the list of current entries as [<class> => <entries>]
         * Store the information in this way because to prevent collision with parent classes
         */
        /** @var array<string, Entries> $cache */
        static $cache = [];
        if (! isset($cache[static::class])) {
            $cache[static::class] = self::createEntries();
        }
        return $cache[static::class];
    }

    final protected static function createEntries(): Entries
    {
        $populator = new EntriesPopulator(
            static::class,
            static::overrideValues(),
            static::overrideIndices(),
            static::parentEntries()
        );
        $entries = new Entries();
        $populator->populate($entries);
        return $entries;
    }

    final protected static function parentEntries(): Entries
    {
        $parentClass = strval(get_parent_class(static::class));
        // if does not have a parent class or is the base template (Enum class)
        if ('' === $parentClass || self::class === $parentClass) {
            return new Entries();
        }
        /** @var Enum $parentClass */
        return $parentClass::currentEntries();
    }
}
