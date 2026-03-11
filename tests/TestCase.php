<?php

namespace BlackstonePro\ModelTranslationsSync\Tests;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase as Orchestra;
use Spatie\TranslationLoader\TranslationServiceProvider;
use BlackstonePro\ModelTranslationsSync\ModelTranslationsSyncServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            TranslationServiceProvider::class,
            ModelTranslationsSyncServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
        $app['config']->set('model-translations.locales', ['en', 'fr', 'de', 'es']);
        $app['config']->set('model-translations.export_path', __DIR__.'/tmp/lang');
        $app['config']->set('model-translations.models.paths', [
            __DIR__.'/Fixtures/App/Models',
        ]);
        $app['config']->set('model-translations.models.list', [
            Fixtures\App\Models\Product::class,
        ]);
    }

    protected function setUp(): void
    {
        parent::setUp();

        File::deleteDirectory(__DIR__.'/tmp');

        $this->setUpDatabase();
    }

    protected function setUpDatabase(): void
    {
        Schema::dropAllTables();

        Schema::create('products', function (Blueprint $table): void {
            $table->id();
            $table->json('title')->nullable();
            $table->json('description')->nullable();
            $table->timestamps();
        });

        Schema::create('blog_posts', function (Blueprint $table): void {
            $table->id();
            $table->json('title')->nullable();
            $table->timestamps();
        });

        Schema::create('plain_models', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->nullable();
            $table->timestamps();
        });

        Schema::create('language_lines', function (Blueprint $table): void {
            $table->id();
            $table->string('group');
            $table->string('key');
            $table->json('text')->nullable();
            $table->timestamps();
            $table->unique(['group', 'key']);
        });
    }
}
