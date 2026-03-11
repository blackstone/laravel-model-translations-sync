<?php

namespace BlackstonePro\ModelTranslationsSync\Contracts;

interface HasModelTranslations
{
    public function getModelTranslationNamespace(): string;

    /**
     * @return array<int, string>
     */
    public function getTranslatableAttributesForSync(): array;
}
