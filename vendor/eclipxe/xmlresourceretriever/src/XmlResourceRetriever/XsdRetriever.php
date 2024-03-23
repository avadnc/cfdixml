<?php

declare(strict_types=1);

namespace XmlResourceRetriever;

class XsdRetriever extends AbstractXmlRetriever
{
    protected function searchNamespace(): string
    {
        return 'http://www.w3.org/2001/XMLSchema';
    }

    protected function searchElements(): array
    {
        return [
            ['element' => 'import', 'attribute' => 'schemaLocation'],
            ['element' => 'include', 'attribute' => 'schemaLocation'],
        ];
    }
}
