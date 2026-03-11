<?php

namespace BlackstonePro\ModelTranslationsSync\Services;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class TranslationSyncPipeline
{
    public function __construct(
        protected ModelExporterService $modelExporter,
        protected ModelImporterService $modelImporter,
        protected FileExporterService $fileExporter,
    ) {
    }

    public function run(Command $command, bool $dryRun = false): int
    {
        $stages = config('model-translations.sync.stages', []);

        if ($stages['export_models'] ?? false) {
            $command->line('Running export_models');
            $this->modelExporter->exportAll(fresh: false, dryRun: $dryRun);
        }

        if (($stages['larex_export'] ?? false) && $this->hasCommand('larex:export')) {
            $command->line('Running larex:export');
            Artisan::call('larex:export');
            $command->output->write(Artisan::output());
        }

        if (($stages['larex_import'] ?? false) && $this->hasCommand('larex:import')) {
            $command->line('Running larex:import');
            Artisan::call('larex:import');
            $command->output->write(Artisan::output());
        }

        if ($stages['import_models'] ?? false) {
            $command->line('Running import_models');
            $this->modelImporter->import(dryRun: $dryRun);
        }

        if ($stages['export_files'] ?? false) {
            $command->line('Running export_files');
            $this->fileExporter->export(dryRun: $dryRun);
        }

        return Command::SUCCESS;
    }

    protected function hasCommand(string $name): bool
    {
        return array_key_exists($name, Artisan::all());
    }
}
