<?php

declare(strict_types=1);

namespace XmlResourceRetriever;

/**
 * Contract that defines a resource retriever
 */
interface RetrieverInterface
{
    /**
     * Must return the base path where the elements will be downloaded
     *
     * @return string
     */
    public function getBasePath(): string;

    /**
     * Return the path where a url would be located
     *
     * @param string $url
     * @return string
     */
    public function buildPath(string $url): string;

    /**
     * Retrieve an url and all its related resources
     * Return the path where the resource is located (as in buildPath)
     *
     * @param string $url
     * @return string
     */
    public function retrieve(string $url): string;

    /**
     * Returns the history of the last retrive operation
     * The return is an array of key value pairs where the key is the url retrieved and the value is the path
     *
     * @return array<string, string>
     */
    public function retrieveHistory(): array;

    /**
     * Download an url without its related resources
     * Return the path where the resource is located (as in buildPath)
     *
     * @param string $url
     * @return string
     */
    public function download(string $url): string;
}
