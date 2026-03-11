<?php

namespace BlackstonePro\ModelTranslationsSync\Support;

use BlackstonePro\ModelTranslationsSync\Exceptions\UnknownModelTranslationNamespaceException;
use BlackstonePro\ModelTranslationsSync\Services\ModelDiscoveryService;

class NamespaceResolver
{
    public function __construct(
        protected ModelDiscoveryService $discoveryService,
    ) {
    }

    public function resolve(string $namespace): string
    {
        $map = $this->discoveryService->getNamespaceMap();

        if (! isset($map[$namespace])) {
            throw new UnknownModelTranslationNamespaceException("Unknown model translation namespace [{$namespace}].");
        }

        return $map[$namespace];
    }
}
