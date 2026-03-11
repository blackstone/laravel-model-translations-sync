<?php

namespace BlackstonePro\ModelTranslationsSync\Support;

use Spatie\TranslationLoader\LanguageLine;
use BlackstonePro\ModelTranslationsSync\DTO\ParsedModelTranslationDTO;
use BlackstonePro\ModelTranslationsSync\Exceptions\InvalidModelTranslationGroupException;
use BlackstonePro\ModelTranslationsSync\Exceptions\InvalidModelTranslationKeyException;

class ModelKeyParser
{
    public function __construct(
        protected NamespaceResolver $namespaceResolver,
    ) {
    }

    public function parse(LanguageLine $line): ParsedModelTranslationDTO
    {
        if (! str_starts_with($line->group, 'models.')) {
            throw new InvalidModelTranslationGroupException("Invalid model translation group [{$line->group}].");
        }

        $namespace = substr($line->group, 7);

        if ($namespace === '') {
            throw new InvalidModelTranslationGroupException("Empty model translation namespace in group [{$line->group}].");
        }

        if (! preg_match('/^([^.]+)\.(.+)$/', (string) $line->key, $matches)) {
            throw new InvalidModelTranslationKeyException("Invalid model translation key [{$line->key}]. Expected {id}.{attribute}.");
        }

        $id = ctype_digit($matches[1]) ? (int) $matches[1] : $matches[1];
        $attribute = $matches[2];
        $modelClass = $this->namespaceResolver->resolve($namespace);
        $translations = is_array($line->text) ? $line->text : (array) $line->text;

        return new ParsedModelTranslationDTO(
            namespace: $namespace,
            modelClass: $modelClass,
            id: $id,
            attribute: $attribute,
            translations: $translations,
        );
    }
}
