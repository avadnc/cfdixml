<?php

declare(strict_types=1);

namespace Eclipxe\Enum\Internal;

/**
 * This is where name, value and index is stored
 *
 * This is an internal class, do not use it by your own. Changes on this class are not library breaking changes.
 * @internal
 */
class Entry
{
    /** @var string */
    private $value;

    /** @var int */
    private $index;

    public function __construct(string $value, int $index)
    {
        $this->value = $value;
        $this->index = $index;
    }

    public function value(): string
    {
        return $this->value;
    }

    public function index(): int
    {
        return $this->index;
    }

    public function equals(self $other): bool
    {
        return ($this->equalValue($other->value()) && $this->equalIndex($other->index()));
    }

    public function equalValue(string $value): bool
    {
        return (0 === strcmp($this->value, $value));
    }

    public function equalIndex(int $index): bool
    {
        return ($this->index === $index);
    }
}
