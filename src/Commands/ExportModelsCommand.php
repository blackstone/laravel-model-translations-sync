<?php

namespace BlackstonePro\ModelTranslationsSync\Commands;

use Illuminate\Console\Command;
use BlackstonePro\ModelTranslationsSync\Services\ModelExporterService;

class ExportModelsCommand extends Command
{
    protected $signature = 'translations:export-models {model?} {--fresh} {--chunk=500} {--dry-run}';

    protected $description = 'Export translatable model values to language_lines.';

    public function handle(ModelExporterService $service): int
    {
        $model = $this->argument('model');
        $modelClass = $model ? $service->resolveModelClass($model) : null;
        $result = $service->exportAll(
            modelClass: $modelClass,
            fresh: (bool) $this->option('fresh'),
            chunk: (int) $this->option('chunk'),
            dryRun: (bool) $this->option('dry-run'),
        );

        $this->info("Processed {$result['processed']} models, wrote {$result['written']} translation rows.");

        return self::SUCCESS;
    }
}
