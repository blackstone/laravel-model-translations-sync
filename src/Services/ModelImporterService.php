<?php

namespace BlackstonePro\ModelTranslationsSync\Services;

use Illuminate\Database\Eloquent\Model;
use Spatie\TranslationLoader\LanguageLine;
use BlackstonePro\ModelTranslationsSync\Contracts\HasModelTranslations;
use BlackstonePro\ModelTranslationsSync\Exceptions\ModelTranslationAttributeNotFoundException;
use BlackstonePro\ModelTranslationsSync\Support\ModelKeyParser;

class ModelImporterService
{
    public function __construct(
        protected ModelKeyParser $parser,
    ) {
    }

    /**
     * @return array{processed:int,updated:int,skipped:int}
     */
    public function import(?string $locale = null, ?string $group = null, int $chunk = 500, bool $dryRun = false): array
    {
        $query = LanguageLine::query()->where('group', 'like', 'models.%');

        if ($group) {
            $query->where('group', str_starts_with($group, 'models.') ? $group : 'models.'.$group);
        }

        $processed = 0;
        $updated = 0;
        $skipped = 0;

        $query->chunk($chunk, function ($lines) use (&$processed, &$updated, &$skipped, $locale, $dryRun): void {
            foreach ($lines as $line) {
                $processed++;

                try {
                    $parsed = $this->parser->parse($line);
                    /** @var Model&HasModelTranslations $prototype */
                    $prototype = app($parsed->modelClass);
                    $translatable = $prototype->getTranslatableAttributesForSync();

                    if (! in_array($parsed->attribute, $translatable, true)) {
                        throw new ModelTranslationAttributeNotFoundException("Attribute [{$parsed->attribute}] is not translatable for [{$parsed->modelClass}].");
                    }

                    $model = $prototype->newQuery()->find($parsed->id);

                    if (! $model) {
                        $skipped++;
                        continue;
                    }

                    $incoming = $locale
                        ? array_intersect_key($parsed->translations, array_flip([$locale]))
                        : $parsed->translations;

                    if ($incoming === []) {
                        $skipped++;
                        continue;
                    }

                    $merged = array_merge($model->getTranslations($parsed->attribute), $incoming);

                    $updated++;

                    if ($dryRun) {
                        continue;
                    }

                    $model->setTranslations($parsed->attribute, $merged);
                    $model->save();
                } catch (ModelTranslationAttributeNotFoundException) {
                    $skipped++;
                }
            }
        });

        return compact('processed', 'updated', 'skipped');
    }
}
