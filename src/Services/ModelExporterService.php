<?php

namespace BlackstonePro\ModelTranslationsSync\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Spatie\TranslationLoader\LanguageLine;
use BlackstonePro\ModelTranslationsSync\Contracts\HasModelTranslations;

class ModelExporterService
{
    public function __construct(
        protected ModelDiscoveryService $discoveryService,
    ) {
    }

    /**
     * @return array{processed:int,written:int}
     */
    public function exportAll(?string $modelClass = null, bool $fresh = false, int $chunk = 500, bool $dryRun = false): array
    {
        if ($fresh && ! $dryRun) {
            LanguageLine::query()->where('group', 'like', 'models.%')->delete();
        }

        $classes = $modelClass ? [$modelClass] : $this->discoveryService->discover();
        $processed = 0;
        $written = 0;

        foreach ($classes as $class) {
            ['processed' => $classProcessed, 'written' => $classWritten] = $this->exportModel($class, $chunk, $dryRun);
            $processed += $classProcessed;
            $written += $classWritten;
        }

        return compact('processed', 'written');
    }

    /**
     * @param  class-string<Model&HasModelTranslations>  $modelClass
     * @return array{processed:int,written:int}
     */
    public function exportModel(string $modelClass, int $chunk = 500, bool $dryRun = false): array
    {
        /** @var Model&HasModelTranslations $prototype */
        $prototype = app($modelClass);
        $group = 'models.'.$prototype->getModelTranslationNamespace();
        $locales = config('model-translations.locales', []);
        $processed = 0;
        $written = 0;

        $prototype->newQuery()->chunk($chunk, function ($models) use (&$processed, &$written, $group, $locales, $dryRun): void {
            foreach ($models as $model) {
                $processed++;

                foreach ($model->getTranslatableAttributesForSync() as $attribute) {
                    $translations = array_filter(
                        $model->getTranslations($attribute),
                        fn (mixed $value, string $locale): bool => in_array($locale, $locales, true) && $value !== null,
                        ARRAY_FILTER_USE_BOTH
                    );

                    if ($translations === []) {
                        continue;
                    }

                    $written++;

                    if ($dryRun) {
                        continue;
                    }

                    LanguageLine::query()->updateOrCreate(
                        [
                            'group' => $group,
                            'key' => $model->getKey().'.'.$attribute,
                        ],
                        [
                            'text' => $translations,
                        ],
                    );
                }
            }
        });

        return compact('processed', 'written');
    }

    public function resolveModelClass(string $value): string
    {
        if (class_exists($value)) {
            return $value;
        }

        $map = $this->discoveryService->getNamespaceMap();

        return Arr::get($map, $value, $value);
    }
}
