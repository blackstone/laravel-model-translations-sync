<?php

namespace BlackstonePro\ModelTranslationsSync\Tests;

use BlackstonePro\ModelTranslationsSync\Services\TranslatableModelsDiscovery;
use BlackstonePro\ModelTranslationsSync\Tests\Fixtures\App\Models\BlogPost;
use BlackstonePro\ModelTranslationsSync\Tests\Fixtures\App\Models\Category;
use BlackstonePro\ModelTranslationsSync\Tests\Fixtures\App\Models\Product;

class TranslatableModelsDiscoveryTest extends TestCase
{
    public function test_it_discovers_only_valid_translatable_models_with_existing_columns(): void
    {
        $models = app(TranslatableModelsDiscovery::class)->discover([
            __DIR__.'/Fixtures/App/Models',
            __DIR__.'/Fixtures/DoesNotExist',
        ]);

        $classes = array_map(fn ($model) => $model->modelClass, $models);

        $this->assertSame([
            BlogPost::class,
            Category::class,
            Product::class,
        ], $classes);
    }

    public function test_it_reads_table_primary_key_and_filters_missing_columns(): void
    {
        $models = app(TranslatableModelsDiscovery::class)->discover([
            __DIR__.'/Fixtures/App/Models',
        ]);

        $category = collect($models)->firstWhere('modelClass', Category::class);
        $product = collect($models)->firstWhere('modelClass', Product::class);

        $this->assertNotNull($category);
        $this->assertSame('categories', $category->table);
        $this->assertSame('uuid', $category->primaryKey);
        $this->assertSame(['title'], $category->attributes);

        $this->assertNotNull($product);
        $this->assertSame('products', $product->table);
        $this->assertSame('id', $product->primaryKey);
        $this->assertSame(['description', 'title'], $product->attributes);
    }
}
