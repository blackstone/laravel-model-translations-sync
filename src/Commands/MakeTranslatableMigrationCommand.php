<?php

namespace BlackstonePro\ModelTranslationsSync\Commands;

use Illuminate\Console\Command;
use BlackstonePro\ModelTranslationsSync\Services\TranslatableModelsDiscovery;
use BlackstonePro\ModelTranslationsSync\Support\TranslatableMigrationGenerator;

class MakeTranslatableMigrationCommand extends Command
{
    protected $signature = 'translations:make-translatable-migration
        {name? : Migration name}
        {--paths=* : Paths to scan for models}
        {--locale= : Locale used to wrap existing scalar values}
        {--chunk= : Chunk size for generated migration data transfer}
        {--force : Overwrite if generated file path already exists}';

    protected $description = 'Generate a snapshot-based migration that converts translatable scalar columns to JSON columns.';

    public function handle(
        TranslatableModelsDiscovery $discovery,
        TranslatableMigrationGenerator $generator,
    ): int {
        $paths = array_values(array_filter($this->option('paths') ?: []));
        $locale = (string) ($this->option('locale') ?: config('model-translations.translatable_migration.default_locale', config('app.fallback_locale', 'en')));
        $chunk = (int) ($this->option('chunk') ?: config('model-translations.translatable_migration.chunk', 500));
        $name = (string) ($this->argument('name') ?: 'convert_translatable_fields_to_json');

        $models = $discovery->discover($paths);

        if ($models === []) {
            $this->warn('No models using HasTranslations with valid translatable columns were found.');

            return self::SUCCESS;
        }

        $this->line('Discovered '.count($models).' translatable models:');

        foreach ($models as $model) {
            $this->line("- {$model->modelClass} [table: {$model->table}] fields: ".implode(', ', $model->attributes));
        }

        $path = $generator->generate(
            name: $name,
            models: $models,
            defaultLocale: $locale,
            chunkSize: $chunk,
            force: (bool) $this->option('force'),
        );

        $this->newLine();
        $this->info('Migration created:');
        $this->line($path);

        return self::SUCCESS;
    }
}
