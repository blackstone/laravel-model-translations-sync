<?php

namespace BlackstonePro\ModelTranslationsSync\Tests;

use Illuminate\Support\Facades\File;
use Spatie\TranslationLoader\LanguageLine;
use BlackstonePro\ModelTranslationsSync\Tests\Fixtures\App\Models\Product;

class TranslationSyncPipelineTest extends TestCase
{
    public function test_sync_continues_when_missing_or_failing_steps_are_allowed(): void
    {
        config()->set('model-translations.sync.stop_on_error', false);
        config()->set('model-translations.sync.pipeline', [
            ['command' => 'tests:missing', 'enabled' => true],
            ['command' => 'tests:fail', 'enabled' => true],
            ['command' => 'translations:export-models', 'enabled' => true],
            ['command' => 'translations:export-files', 'enabled' => true],
        ]);

        Product::query()->create([
            'title' => ['en' => 'Phone'],
            'description' => ['en' => 'Smartphone'],
        ]);

        $this->artisan('translations:sync')
            ->expectsOutput('Pipeline step [tests:missing] was not found.')
            ->expectsOutput('Running tests:fail')
            ->expectsOutput('tests:fail executed')
            ->expectsOutput('Pipeline step [tests:fail] exited with code [1].')
            ->expectsOutput('Running translations:export-models')
            ->expectsOutput('Running translations:export-files')
            ->assertExitCode(0);

        $this->assertDatabaseHas('language_lines', [
            'group' => 'models.product',
            'key' => '1.title',
        ]);
        $this->assertFileExists(__DIR__.'/tmp/lang/en/models.php');
    }

    public function test_sync_stops_on_error_when_configured(): void
    {
        config()->set('model-translations.sync.stop_on_error', true);
        config()->set('model-translations.sync.pipeline', [
            ['command' => 'tests:fail', 'enabled' => true],
            ['command' => 'translations:export-models', 'enabled' => true],
        ]);

        Product::query()->create([
            'title' => ['en' => 'Phone'],
            'description' => ['en' => 'Smartphone'],
        ]);

        $this->artisan('translations:sync')
            ->expectsOutput('Running tests:fail')
            ->expectsOutput('tests:fail executed')
            ->expectsOutput('Pipeline step [tests:fail] exited with code [1].')
            ->doesntExpectOutput('Running translations:export-models')
            ->assertExitCode(1);

        $this->assertNull(LanguageLine::query()->where('group', 'models.product')->where('key', '1.title')->first());
        $this->assertSame([], File::glob(__DIR__.'/tmp/lang/en/*.php'));
    }
}
