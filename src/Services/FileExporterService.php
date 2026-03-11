<?php

namespace BlackstonePro\ModelTranslationsSync\Services;

use Spatie\TranslationLoader\LanguageLine;
use BlackstonePro\ModelTranslationsSync\Support\LangFileWriter;

class FileExporterService
{
    public function __construct(
        protected LangFileWriter $writer,
    ) {
    }

    /**
     * @return array{written:int}
     */
    public function export(bool $dryRun = false): array
    {
        $exportPath = config('model-translations.export_path');
        $overwrite = config('model-translations.export.overwrite', true);
        $prettyPrint = config('model-translations.export.pretty_print', true);
        $written = 0;

        $phpGroups = [];
        $jsonGroups = [];
        $modelGroups = [];

        foreach (LanguageLine::query()->orderBy('group')->orderBy('key')->get() as $line) {
            $translations = is_array($line->text) ? $line->text : [];

            foreach ($translations as $locale => $value) {
                if (! in_array($locale, config('model-translations.locales', []), true)) {
                    continue;
                }

                if ($line->group === '*') {
                    $jsonGroups[$locale][$line->key] = $value;
                    continue;
                }

                if (str_starts_with($line->group, 'models.')) {
                    $namespace = substr($line->group, 7);
                    [$id, $attribute] = explode('.', $line->key, 2);
                    $modelGroups[$locale][$namespace][$id][$attribute] = $value;
                    continue;
                }

                if (in_array($line->group, config('model-translations.ignore_groups', []), true)) {
                    continue;
                }

                $this->setNestedValue($phpGroups[$locale][$line->group], $line->key, $value);
            }
        }

        foreach ($phpGroups as $locale => $groups) {
            foreach ($groups as $group => $data) {
                $data = $this->normalize($data);
                $written++;

                if ($dryRun) {
                    continue;
                }

                $this->writer->writePhp("{$exportPath}/{$locale}/{$group}.php", $data, $overwrite);
            }
        }

        foreach ($jsonGroups as $locale => $data) {
            $data = $this->normalize($data);
            $written++;

            if ($dryRun) {
                continue;
            }

            $this->writer->writeJson("{$exportPath}/{$locale}.json", $data, $overwrite, $prettyPrint);
        }

        foreach ($modelGroups as $locale => $data) {
            $data = $this->normalize($data);
            $written++;

            if ($dryRun) {
                continue;
            }

            $this->writer->writePhp("{$exportPath}/{$locale}/models.php", $data, $overwrite);
        }

        return compact('written');
    }

    /**
     * @param  array<mixed>|null  $target
     */
    protected function setNestedValue(?array &$target, string $key, mixed $value): void
    {
        $target ??= [];
        $segments = explode('.', $key);
        $current = &$target;

        foreach ($segments as $segment) {
            if (! isset($current[$segment]) || ! is_array($current[$segment])) {
                $current[$segment] = [];
            }

            $current = &$current[$segment];
        }

        $current = $value;
    }

    /**
     * @param  array<mixed>  $data
     * @return array<mixed>
     */
    protected function normalize(array $data): array
    {
        if (! config('model-translations.export.sort_keys', true)) {
            return $data;
        }

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->normalize($value);
            }
        }

        ksort($data);

        return $data;
    }
}
