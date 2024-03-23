<?php

declare(strict_types=1);

namespace Eclipxe\Enum\Internal;

use ArrayObject;

/**
 * This is a collection of Entry elements
 *
 * This is an internal class, do not use it by your own. Changes on this class are not library breaking changes.
 * @internal
 */
class Entries
{
    /** @var ArrayObject<string, Entry> */
    private $entries;

    public function __construct()
    {
        $this->entries = new ArrayObject([]);
    }

    /**
     * Obtain the list of registered possible values as an array of indices and values
     *
     * @return array<int, string>
     */
    public function toIndexValueArray(): array
    {
        $mixed = [];
        foreach ($this->entries as $entry) {
            $mixed[$entry->index()] = $entry->value();
        }
        return $mixed;
    }

    public function hasName(string $name): bool
    {
        return isset($this->entries[$this->normalizeName($name)]);
    }

    public function put(string $name, Entry $entry): void
    {
        $this->entries[$this->normalizeName($name)] = $entry;
    }

    public function append(self $entries): void
    {
        foreach ($entries->entries as $name => $entry) {
            $this->entries[$name] = $entry;
        }
    }

    public function findEntryByName(string $name): ?Entry
    {
        return $this->entries[$this->normalizeName($name)] ?? null;
    }

    public function findEntryByValue(string $value): ?Entry
    {
        foreach ($this->entries as $entry) {
            if ($entry->equalValue($value)) {
                return $entry;
            }
        }
        return null;
    }

    public function findEntryByIndex(int $index): ?Entry
    {
        foreach ($this->entries as $entry) {
            if ($entry->equalIndex($index)) {
                return $entry;
            }
        }
        return null;
    }

    /**
     * @return array<int, int>
     */
    public function indices(): array
    {
        $indices = [];
        foreach ($this->entries as $entry) {
            $indices[] = $entry->index();
        }
        return $indices;
    }

    public function nextIndex(): int
    {
        $indices = $this->indices();
        if ([] === $indices) {
            return 0;
        }
        return max($indices) + 1;
    }

    protected function normalizeName(string $name): string
    {
        return strtolower($name);
    }
}
