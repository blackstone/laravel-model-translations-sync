<?php

namespace BlackstonePro\ModelTranslationsSync\DTO;

class ParsedModelTranslationDTO
{
    /**
     * @param  array<string, string>  $translations
     */
    public function __construct(
        public string $namespace,
        public string $modelClass,
        public int|string $id,
        public string $attribute,
        public array $translations,
    ) {
    }
}
