<?php

namespace BlackstonePro\ModelTranslationsSync\Commands;

use Illuminate\Console\Command;
use BlackstonePro\ModelTranslationsSync\Services\FileExporterService;

class ExportFilesCommand extends Command
{
    protected $signature = 'translations:export-files {--dry-run}';

    protected $description = 'Export translations from language_lines to lang files.';

    public function handle(FileExporterService $service): int
    {
        $result = $service->export((bool) $this->option('dry-run'));

        $this->info("Wrote {$result['written']} lang files.");

        return self::SUCCESS;
    }
}
