<?php

namespace BlackstonePro\ModelTranslationsSync\Services;

use Illuminate\Filesystem\Filesystem;
use Spatie\TranslationLoader\LanguageLine;
use BlackstonePro\ModelTranslationsSync\Support\LangFileReader;

class FileImporterService
{
    public function __construct(
        protected Filesystem $files,
        protected LangFileReader $reader,
    ) {
    }

    /**
     * @return array{processed:int,written:int}
     */
    public function import(bool $dryRun = false): array
    {
        $basePath = config('model-translations.export_path');
        $locales = config('model-translations.locales', []);
        $processed = 0;
        $written = 0;

        foreach ($locales as $locale) {
            $jsonPath = "{$basePath}/{$locale}.json";

            if ($this->files->exists($jsonPath)) {
                foreach ($this->reader->readJson($jsonPath) as $key => $value) {
                    $processed++;
                    $written += $this->store('*', $key, [$locale => $value], $dryRun);
                }
            }

            $localePath = "{$basePath}/{$locale}";

            if (! $this->files->isDirectory($localePath)) {
                continue;
            }

            foreach ($this->files->files($localePath) as $file) {
                if ($file->getExtension() !== 'php') {
                    continue;
                }

                $group = $file->getBasename('.php');
                $data = $this->reader->readPhp($file->getPathname());

                if ($group === 'models') {
                    foreach ($data as $namespace => $models) {
                        foreach ($models as $id => $attributes) {
                            foreach ($attributes as $attribute => $value) {
                                $processed++;
                                $written += $this->store("models.{$namespace}", "{$id}.{$attribute}", [$locale => $value], $dryRun);
                            }
                        }
                    }

                    continue;
                }

                foreach ($this->flatten($data) as $key => $value) {
                    $processed++;
                    $written += $this->store($group, $key, [$locale => $value], $dryRun);
                }
            }
        }

        return compact('processed', 'written');
    }

    /**
     * @param  array<string, string>  $text
     */
    protected function store(string $group, string $key, array $text, bool $dryRun): int
    {
        if ($dryRun) {
            return 1;
        }

        $line = LanguageLine::query()->firstOrNew([
            'group' => $group,
            'key' => $key,
        ]);

        $line->text = array_merge(is_array($line->text) ? $line->text : [], $text);
        $line->save();

        return 1;
    }

    /**
     * @param  array<mixed>  $data
     * @return array<string, mixed>
     */
    protected function flatten(array $data, string $prefix = ''): array
    {
        $result = [];

        foreach ($data as $key => $value) {
            $composed = $prefix === '' ? (string) $key : "{$prefix}.{$key}";

            if (is_array($value)) {
                $result += $this->flatten($value, $composed);
                continue;
            }

            $result[$composed] = $value;
        }

        return $result;
    }
}
