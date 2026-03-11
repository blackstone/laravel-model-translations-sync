<?php

namespace BlackstonePro\ModelTranslationsSync\Commands;

use Illuminate\Console\Command;
use BlackstonePro\ModelTranslationsSync\Services\FileImporterService;

class ImportFilesCommand extends Command
{
    protected $signature = 'translations:import-files {--dry-run}';

    protected $description = 'Import translations from lang files into language_lines.';

    public function handle(FileImporterService $service): int
    {
        $result = $service->import((bool) $this->option('dry-run'));

        $this->info("Processed {$result['processed']} file entries, wrote {$result['written']} translation rows.");

        return self::SUCCESS;
    }
}
