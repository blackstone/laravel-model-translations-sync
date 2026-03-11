<?php

namespace BlackstonePro\ModelTranslationsSync\Tests;

use Illuminate\Database\Migrations\Migration;
use BlackstonePro\ModelTranslationsSync\DTO\DiscoveredTranslatableModel;
use BlackstonePro\ModelTranslationsSync\Support\TranslatableMigrationGenerator;
use BlackstonePro\ModelTranslationsSync\Tests\Fixtures\App\Models\Category;
use BlackstonePro\ModelTranslationsSync\Tests\Fixtures\App\Models\Product;

class TranslatableMigrationGeneratorTest extends TestCase
{
    public function test_it_generates_valid_php_code_with_snapshot_and_tmp_columns(): void
    {
        $generator = app(TranslatableMigrationGenerator::class);
        $path = $generator->generate(
            name: 'convert_translatable_fields_to_json',
            models: [
                new DiscoveredTranslatableModel(Product::class, 'products', 'id', ['name', 'caption']),
                new DiscoveredTranslatableModel(Category::class, 'categories', 'uuid', ['title']),
            ],
            defaultLocale: 'en',
            chunkSize: 250,
            outputPath: __DIR__.'/tmp/migrations',
            force: true,
        );

        $contents = file_get_contents($path);
        $migration = require $path;

        $this->assertIsString($contents);
        $this->assertStringContainsString("\\BlackstonePro\\ModelTranslationsSync\\Tests\\Fixtures\\App\\Models\\Product::class", $contents);
        $this->assertStringContainsString("private string \$defaultLocale = 'en';", $contents);
        $this->assertStringContainsString('__json_tmp', $contents);
        $this->assertStringContainsString('__text_tmp', $contents);
        $this->assertStringContainsString('public function up(): void', $contents);
        $this->assertStringContainsString('public function down(): void', $contents);
        $this->assertInstanceOf(Migration::class, $migration);
    }
}
