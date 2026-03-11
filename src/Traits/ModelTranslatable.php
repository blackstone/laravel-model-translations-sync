<?php

namespace BlackstonePro\ModelTranslationsSync\Traits;

use Illuminate\Support\Str;
use BlackstonePro\ModelTranslationsSync\Contracts\HasModelTranslations;

trait ModelTranslatable
{
    public function getModelTranslationNamespace(): string
    {
        return Str::snake(class_basename($this));
    }

    public function getTranslatableAttributesForSync(): array
    {
        return property_exists($this, 'translatable') ? $this->translatable : [];
    }
}
