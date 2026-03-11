<?php

namespace BlackstonePro\ModelTranslationsSync\Tests;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Spatie\TranslationLoader\LanguageLine;
use BlackstonePro\ModelTranslationsSync\Services\ModelDiscoveryService;
use BlackstonePro\ModelTranslationsSync\Services\ModelExporterService;
use BlackstonePro\ModelTranslationsSync\Services\ModelImporterService;
use BlackstonePro\ModelTranslationsSync\Tests\Fixtures\App\Models\BlogPost;
use BlackstonePro\ModelTranslationsSync\Tests\Fixtures\App\Models\Product;

class PackageTest extends TestCase
{
    public function test_it_discovers_models_from_list_and_paths_and_prioritizes_namespace_map(): void
    {
        config()->set('model-translations.models.namespace_map', [
            'product' => BlogPost::class,
        ]);

        $service = app(ModelDiscoveryService::class);

        $discovered = $service->discover();
        $map = $service->getNamespaceMap();

        $this->assertContains(Product::class, $discovered);
        $this->assertContains(BlogPost::class, $discovered);
        $this->assertNotContains(\BlackstonePro\ModelTranslationsSync\Tests\Fixtures\App\Models\PlainModel::class, $discovered);
        $this->assertSame(BlogPost::class, $map['product']);
        $this->assertSame(BlogPost::class, $map['blog_post']);
    }

    public function test_it_exports_a_single_model_and_updates_existing_rows(): void
    {
        $product = Product::query()->create([
            'title' => ['en' => 'Phone', 'fr' => 'Telephone'],
            'description' => ['en' => 'Smartphone'],
        ]);

        LanguageLine::query()->create([
            'group' => 'models.product',
            'key' => $product->id.'.title',
            'text' => ['en' => 'Old title'],
        ]);

        app(ModelExporterService::class)->exportAll(Product::class);

        $titleLine = LanguageLine::query()->where('group', 'models.product')->where('key', $product->id.'.title')->firstOrFail();
        $descriptionLine = LanguageLine::query()->where('group', 'models.product')->where('key', $product->id.'.description')->firstOrFail();

        $this->assertSame(['en' => 'Phone', 'fr' => 'Telephone'], $titleLine->text);
        $this->assertSame(['en' => 'Smartphone'], $descriptionLine->text);
    }

    public function test_fresh_export_removes_stale_model_rows_and_exports_all_models(): void
    {
        Product::query()->create([
            'title' => ['en' => 'Phone'],
            'description' => ['en' => 'Smartphone', 'de' => 'Smartphone'],
        ]);

        BlogPost::query()->create([
            'title' => ['en' => 'Hello', 'fr' => 'Bonjour'],
        ]);

        LanguageLine::query()->create([
            'group' => 'models.legacy',
            'key' => '999.title',
            'text' => ['en' => 'Legacy'],
        ]);

        $result = app(ModelExporterService::class)->exportAll(fresh: true);

        $this->assertSame(2, $result['processed']);
        $this->assertNull(LanguageLine::query()->where('group', 'models.legacy')->first());
        $this->assertDatabaseHas('language_lines', [
            'group' => 'models.product',
            'key' => '1.description',
        ]);
        $this->assertDatabaseHas('language_lines', [
            'group' => 'models.blog_post',
            'key' => '1.title',
        ]);
    }

    public function test_it_imports_all_locales_and_merges_without_overwriting_existing_values(): void
    {
        $product = Product::query()->create([
            'title' => ['en' => 'Phone', 'de' => 'Telefon'],
        ]);

        LanguageLine::query()->create([
            'group' => 'models.product',
            'key' => $product->id.'.title',
            'text' => ['fr' => 'Telephone', 'es' => 'Telefono'],
        ]);

        $result = app(ModelImporterService::class)->import();

        $product->refresh();

        $this->assertSame(1, $result['updated']);
        $this->assertSame([
            'en' => 'Phone',
            'de' => 'Telefon',
            'fr' => 'Telephone',
            'es' => 'Telefono',
        ], $product->getTranslations('title'));
    }

    public function test_it_imports_only_one_locale_when_requested(): void
    {
        $product = Product::query()->create([
            'title' => ['en' => 'Phone', 'de' => 'Telefon'],
        ]);

        LanguageLine::query()->create([
            'group' => 'models.product',
            'key' => $product->id.'.title',
            'text' => ['fr' => 'Telephone', 'es' => 'Telefono'],
        ]);

        app(ModelImporterService::class)->import(locale: 'fr');

        $product->refresh();

        $this->assertSame([
            'en' => 'Phone',
            'de' => 'Telefon',
            'fr' => 'Telephone',
        ], $product->getTranslations('title'));
    }

    public function test_import_skips_missing_models_and_non_translatable_attributes(): void
    {
        Product::query()->create([
            'title' => ['en' => 'Phone'],
        ]);

        LanguageLine::query()->create([
            'group' => 'models.product',
            'key' => '999.title',
            'text' => ['fr' => 'Ghost'],
        ]);

        LanguageLine::query()->create([
            'group' => 'models.product',
            'key' => '1.non_existing',
            'text' => ['fr' => 'Ignored'],
        ]);

        $result = app(ModelImporterService::class)->import();

        $this->assertSame(2, $result['skipped']);
    }

    public function test_it_exports_database_translations_to_php_json_and_models_files(): void
    {
        LanguageLine::query()->create([
            'group' => 'messages',
            'key' => 'welcome.title',
            'text' => ['en' => 'Welcome', 'fr' => 'Bienvenue'],
        ]);

        LanguageLine::query()->create([
            'group' => '*',
            'key' => 'Save',
            'text' => ['en' => 'Save', 'fr' => 'Sauvegarder'],
        ]);

        LanguageLine::query()->create([
            'group' => 'models.product',
            'key' => '12.title',
            'text' => ['en' => 'iPhone', 'fr' => 'iPhone'],
        ]);

        $this->artisan('translations:export-files')->assertExitCode(0);

        $messages = require __DIR__.'/tmp/lang/en/messages.php';
        $models = require __DIR__.'/tmp/lang/en/models.php';
        $json = json_decode(File::get(__DIR__.'/tmp/lang/fr.json'), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame(['welcome' => ['title' => 'Welcome']], $messages);
        $this->assertSame(['product' => [12 => ['title' => 'iPhone']]], $models);
        $this->assertSame(['Save' => 'Sauvegarder'], $json);
    }

    public function test_it_imports_php_json_and_models_files_back_to_language_lines(): void
    {
        File::ensureDirectoryExists(__DIR__.'/tmp/lang/en');
        File::put(__DIR__.'/tmp/lang/en/messages.php', <<<'PHP'
<?php

return [
    'welcome' => [
        'title' => 'Welcome',
    ],
];
PHP);
        File::put(__DIR__.'/tmp/lang/en/models.php', <<<'PHP'
<?php

return [
    'product' => [
        12 => [
            'title' => 'iPhone',
        ],
    ],
];
PHP);
        File::put(__DIR__.'/tmp/lang/en.json', json_encode(['Save' => 'Save'], JSON_PRETTY_PRINT));

        LanguageLine::query()->create([
            'group' => 'messages',
            'key' => 'welcome.title',
            'text' => ['fr' => 'Bienvenue'],
        ]);

        $this->artisan('translations:import-files')->assertExitCode(0);

        $this->assertSame(['fr' => 'Bienvenue', 'en' => 'Welcome'], LanguageLine::query()->where('group', 'messages')->where('key', 'welcome.title')->firstOrFail()->text);
        $this->assertSame(['en' => 'Save'], LanguageLine::query()->where('group', '*')->where('key', 'Save')->firstOrFail()->text);
        $this->assertSame(['en' => 'iPhone'], LanguageLine::query()->where('group', 'models.product')->where('key', '12.title')->firstOrFail()->text);
    }

    public function test_sync_command_runs_pipeline(): void
    {
        Product::query()->create([
            'title' => ['en' => 'Phone'],
            'description' => ['en' => 'Smartphone'],
        ]);

        $this->artisan('translations:sync')->assertExitCode(0);

        $this->assertDatabaseHas('language_lines', [
            'group' => 'models.product',
            'key' => '1.title',
        ]);
        $this->assertFileExists(__DIR__.'/tmp/lang/en/models.php');
    }

    public function test_export_models_command_accepts_namespace_argument(): void
    {
        Product::query()->create([
            'title' => ['en' => 'Phone'],
            'description' => ['en' => 'Smartphone'],
        ]);

        $this->artisan('translations:export-models', ['model' => 'product'])->assertExitCode(0);

        $this->assertDatabaseHas('language_lines', [
            'group' => 'models.product',
            'key' => '1.title',
        ]);
    }
}
