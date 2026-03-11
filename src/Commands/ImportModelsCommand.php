<?php

namespace BlackstonePro\ModelTranslationsSync\Commands;

use Illuminate\Console\Command;
use BlackstonePro\ModelTranslationsSync\Services\ModelImporterService;

class ImportModelsCommand extends Command
{
    protected $signature = 'translations:import-models {--locale=} {--group=} {--chunk=500} {--dry-run}';

    protected $description = 'Import model translations from language_lines back into models.';

    public function handle(ModelImporterService $service): int
    {
        $result = $service->import(
            locale: $this->option('locale') ?: null,
            group: $this->option('group') ?: null,
            chunk: (int) $this->option('chunk'),
            dryRun: (bool) $this->option('dry-run'),
        );

        $this->info("Processed {$result['processed']} rows, updated {$result['updated']} models, skipped {$result['skipped']} rows.");

        return self::SUCCESS;
    }
}
