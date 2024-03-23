<?php

declare(strict_types=1);

namespace Eclipxe\Enum\Internal;

use Eclipxe\Enum\Exceptions\IndexOverrideException;
use Eclipxe\Enum\Exceptions\ValueOverrideException;
use ReflectionClass;

/**
 * This is a helper that perform discovery
 *
 * This is an internal class, do not use it by your own. Changes on this class are not library breaking changes.
 * @internal
 */
class EntriesPopulator
{
    /** @var class-string */
    private $className;

    /** @var array<string, mixed> */
    private $overrideValues;

    /** @var array<string, mixed> */
    private $overrideIndices;

    /** @var Entries */
    private $parentEntries;

    /**
     * EntriesPopulator constructor.
     *
     * @param class-string $className
     * @param array<string, mixed> $overrideValues
     * @param array<string, mixed> $overrideIndices
     * @param Entries $parentEntries
     */
    public function __construct(
        string $className,
        array $overrideValues,
        array $overrideIndices,
        Entries $parentEntries
    ) {
        $this->className = $className;
        $this->overrideValues = $overrideValues;
        $this->overrideIndices = $overrideIndices;
        $this->parentEntries = $parentEntries;
    }

    /** @return class-string */
    public function getClassName(): string
    {
        return $this->className;
    }

    public function populate(Entries $entries): void
    {
        // populate with parents first
        $entries->append($this->parentEntries);

        // populate with discovered
        $names = array_filter(
            $this->resolveNamesFromDocBlocks(),
            function (string $name) use ($entries): bool {
                return ! $entries->hasName($name);
            }
        );
        foreach ($names as $name) {
            $newValue = $this->overrideValue($name) ?? $name;
            if (null !== $entries->findEntryByValue($newValue)) {
                throw ValueOverrideException::create($this->getClassName(), $newValue);
            }

            $newIndex = $this->overrideIndex($name) ?? $entries->nextIndex();
            if (null !== $entries->findEntryByIndex($newIndex)) {
                throw IndexOverrideException::create($this->getClassName(), strval($newIndex));
            }

            $entries->put($name, new Entry($newValue, $newIndex));
        }
    }

    public function overrideValue(string $name): ?string
    {
        $value = $this->overrideValues[$name] ?? null;
        if (! is_string($value)) {
            return null;
        }
        return $value;
    }

    public function overrideIndex(string $name): ?int
    {
        $index = $this->overrideIndices[$name] ?? null;
        if (! is_int($index)) {
            return null;
        }
        return $index;
    }

    /**
     * @return array<string>
     * @throws \ReflectionException
     */
    public function resolveNamesFromDocBlocks(): array
    {
        // get comments
        $className = $this->getClassName();
        $reflectionClass = new ReflectionClass($className);
        $docComment = strval($reflectionClass->getDocComment());
        return $this->resolveNamesFromDocComment($docComment);
    }

    /**
     * @param string $docComment
     * @return array<string>
     */
    public function resolveNamesFromDocComment(string $docComment): array
    {
        // read declarations @method static self WORD()
        //  [*\t ]*: any asterisk, space or tab
        //  [\w]+: any word letters, numbers and underscore
        //  /m: ^ match beginning of the line
        preg_match_all('/^[*\t ]*@method static (self|static) ([\w]+)\(\)/m', $docComment, $matches);
        return $matches[2] ?? [];
    }
}
