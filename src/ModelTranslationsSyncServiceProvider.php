<?php

namespace BlackstonePro\ModelTranslationsSync;

use Illuminate\Support\ServiceProvider;
use BlackstonePro\ModelTranslationsSync\Commands\ExportFilesCommand;
use BlackstonePro\ModelTranslationsSync\Commands\ExportModelsCommand;
use BlackstonePro\ModelTranslationsSync\Commands\ImportFilesCommand;
use BlackstonePro\ModelTranslationsSync\Commands\ImportModelsCommand;
use BlackstonePro\ModelTranslationsSync\Commands\MakeTranslatableMigrationCommand;
use BlackstonePro\ModelTranslationsSync\Commands\SyncTranslationsCommand;
use BlackstonePro\ModelTranslationsSync\Services\FileExporterService;
use BlackstonePro\ModelTranslationsSync\Services\FileImporterService;
use BlackstonePro\ModelTranslationsSync\Services\ModelDiscoveryService;
use BlackstonePro\ModelTranslationsSync\Services\ModelExporterService;
use BlackstonePro\ModelTranslationsSync\Services\ModelImporterService;
use BlackstonePro\ModelTranslationsSync\Services\TranslatableModelsDiscovery;
use BlackstonePro\ModelTranslationsSync\Services\TranslationSyncPipeline;
use BlackstonePro\ModelTranslationsSync\Support\LangFileReader;
use BlackstonePro\ModelTranslationsSync\Support\LangFileWriter;
use BlackstonePro\ModelTranslationsSync\Support\ModelKeyParser;
use BlackstonePro\ModelTranslationsSync\Support\NamespaceResolver;
use BlackstonePro\ModelTranslationsSync\Support\TranslatableMigrationGenerator;

class ModelTranslationsSyncServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/model-translations.php', 'model-translations');

        $this->app->singleton(ModelDiscoveryService::class);
        $this->app->singleton(NamespaceResolver::class);
        $this->app->singleton(ModelKeyParser::class);
        $this->app->singleton(LangFileWriter::class);
        $this->app->singleton(LangFileReader::class);
        $this->app->singleton(ModelExporterService::class);
        $this->app->singleton(ModelImporterService::class);
        $this->app->singleton(FileExporterService::class);
        $this->app->singleton(FileImporterService::class);
        $this->app->singleton(TranslatableModelsDiscovery::class);
        $this->app->singleton(TranslatableMigrationGenerator::class);
        $this->app->singleton(TranslationSyncPipeline::class);
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/model-translations.php' => config_path('model-translations.php'),
        ], 'model-translations-config');

        if ($this->app->runningInConsole()) {
            $this->commands([
                ExportModelsCommand::class,
                ImportModelsCommand::class,
                ExportFilesCommand::class,
                ImportFilesCommand::class,
                SyncTranslationsCommand::class,
                MakeTranslatableMigrationCommand::class,
            ]);
        }
    }
}
