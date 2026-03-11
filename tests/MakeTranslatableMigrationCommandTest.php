<?php

namespace BlackstonePro\ModelTranslationsSync\Tests;

use Illuminate\Support\Facades\File;

class MakeTranslatableMigrationCommandTest extends TestCase
{
    public function test_command_creates_snapshot_migration_and_outputs_models(): void
    {
        $this->artisan('translations:make-translatable-migration', [
            'name' => 'convert_translatable_fields_to_json',
            '--locale' => 'fr',
            '--paths' => [__DIR__.'/Fixtures/App/Models'],
        ])
            ->expectsOutput('Discovered 3 translatable models:')
            ->expectsOutput('- BlackstonePro\ModelTranslationsSync\Tests\Fixtures\App\Models\BlogPost [table: blog_posts] fields: title')
            ->expectsOutput('- BlackstonePro\ModelTranslationsSync\Tests\Fixtures\App\Models\Category [table: categories] fields: title')
            ->expectsOutput('- BlackstonePro\ModelTranslationsSync\Tests\Fixtures\App\Models\Product [table: products] fields: description, title')
            ->expectsOutput('Migration created:')
            ->assertExitCode(0);

        $files = File::glob(__DIR__.'/tmp/migrations/*.php');

        $this->assertCount(1, $files);

        $contents = File::get($files[0]);

        $this->assertStringContainsString("'primary_key' => 'uuid'", $contents);
        $this->assertStringContainsString("private string \$defaultLocale = 'fr';", $contents);
        $this->assertStringContainsString("'translatable' => ['description', 'title']", $contents);
    }

    public function test_command_does_not_create_file_when_no_models_are_found(): void
    {
        File::deleteDirectory(__DIR__.'/tmp/migrations');
        File::ensureDirectoryExists(__DIR__.'/Fixtures/Empty');

        $this->artisan('translations:make-translatable-migration', [
            '--paths' => [__DIR__.'/Fixtures/Empty'],
        ])
            ->expectsOutput('No models using HasTranslations with valid translatable columns were found.')
            ->assertExitCode(0);

        $files = File::glob(__DIR__.'/tmp/migrations/*.php');

        $this->assertSame([], $files);
    }
}
