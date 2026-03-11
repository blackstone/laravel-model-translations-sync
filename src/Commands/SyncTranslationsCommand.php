<?php

namespace BlackstonePro\ModelTranslationsSync\Commands;

use Illuminate\Console\Command;
use BlackstonePro\ModelTranslationsSync\Services\TranslationSyncPipeline;

class SyncTranslationsCommand extends Command
{
    protected $signature = 'translations:sync {--dry-run}';

    protected $description = 'Run the full model/database/file translation synchronization pipeline.';

    public function handle(TranslationSyncPipeline $pipeline): int
    {
        return $pipeline->run($this, (bool) $this->option('dry-run'));
    }
}
