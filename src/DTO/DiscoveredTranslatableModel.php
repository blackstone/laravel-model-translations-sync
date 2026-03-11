<?php

namespace BlackstonePro\ModelTranslationsSync\DTO;

class DiscoveredTranslatableModel
{
    /**
     * @param  array<int, string>  $attributes
     */
    public function __construct(
        public string $modelClass,
        public string $table,
        public string $primaryKey,
        public array $attributes,
    ) {
    }

    /**
     * @return array{model:string,table:string,primary_key:string,translatable:array<int,string>}
     */
    public function toArray(): array
    {
        return [
            'model' => $this->modelClass,
            'table' => $this->table,
            'primary_key' => $this->primaryKey,
            'translatable' => $this->attributes,
        ];
    }
}
