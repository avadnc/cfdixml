<?php

declare(strict_types=1);

namespace XmlResourceRetriever;

class Utils
{
    /**
     * Return the relative path from one location to other
     *
     * @param string $sourceFile
     * @param string $destinationFile
     * @return string
     */
    public static function relativePath(string $sourceFile, string $destinationFile): string
    {
        $source = static::simplifyPath($sourceFile);
        $destination = static::simplifyPath($destinationFile);
        if ('' !== $source[0] && '' === $destination[0]) {
            return implode('/', $destination);
        }
        // remove the common path
        foreach ($source as $depth => $dir) {
            if (isset($destination[$depth])) {
                if ($dir === $destination[$depth]) {
                    unset($destination[$depth]);
                    unset($source[$depth]);
                } else {
                    break;
                }
            }
        }
        // add '..' to the beginning of the source as required by the count of from
        $fromCount = count($source);
        for ($i = 0; $i < $fromCount - 1; $i++) {
            array_unshift($destination, '..');
        }
        return implode('/', $destination);
    }

    /**
     * Simplify a path and return its parts as an array
     *
     * @param string $path
     * @return string[]
     */
    public static function simplifyPath(string $path): array
    {
        $parts = explode('/', str_replace('//', '/./', $path));
        $count = count($parts);
        for ($i = 0; $i < $count; $i = $i + 1) {
            // is .. and previous is not ..
            if ($i > 0 && '..' === $parts[$i] && '..' !== $parts[$i - 1]) {
                unset($parts[$i - 1]);
                unset($parts[$i]);
                return static::simplifyPath(implode('/', $parts));
            }
            // is inner '.'
            if ('.' == $parts[$i]) {
                unset($parts[$i]);
                return static::simplifyPath(implode('/', $parts));
            }
        }
        return array_values($parts);
    }
}
