<?php

namespace BlackstonePro\ModelTranslationsSync\Support;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use RuntimeException;
use BlackstonePro\ModelTranslationsSync\DTO\DiscoveredTranslatableModel;

class TranslatableMigrationGenerator
{
    public function __construct(
        protected Filesystem $files,
    ) {
    }

    /**
     * @param  array<int, DiscoveredTranslatableModel>  $models
     */
    public function generate(
        string $name,
        array $models,
        string $defaultLocale,
        int $chunkSize = 500,
        ?string $outputPath = null,
        bool $force = false,
    ): string {
        $outputPath ??= config('model-translations.translatable_migration.output_path', database_path('migrations'));
        $this->files->ensureDirectoryExists($outputPath);

        $timestamp = date('Y_m_d_His');
        $filename = $timestamp.'_'.Str::snake($name).'.php';
        $path = rtrim($outputPath, '/').'/'.$filename;

        if (! $force && $this->files->exists($path)) {
            throw new RuntimeException("Migration file [{$path}] already exists.");
        }

        $this->files->put($path, $this->generateCode($models, $defaultLocale, $chunkSize));

        return $path;
    }

    /**
     * @param  array<int, DiscoveredTranslatableModel>  $models
     */
    public function generateCode(array $models, string $defaultLocale, int $chunkSize = 500): string
    {
        $snapshot = $this->exportModels($models);

        return <<<PHP
<?php

use Illuminate\\Database\\Migrations\\Migration;
use Illuminate\\Database\\Schema\\Blueprint;
use Illuminate\\Support\\Facades\\DB;
use Illuminate\\Support\\Facades\\Schema;

return new class extends Migration
{
    /**
     * Snapshot generated at migration creation time.
     *
     * @var array<int, array{model:string,table:string,primary_key:string,translatable:array<int,string>}>
     */
    private array \$models = {$snapshot};

    private string \$defaultLocale = '{$defaultLocale}';

    private int \$chunkSize = {$chunkSize};

    public function up(): void
    {
        foreach (\$this->models as \$model) {
            \$table = \$model['table'];
            \$primaryKey = \$model['primary_key'];
            \$columns = \$model['translatable'];

            Schema::table(\$table, function (Blueprint \$table) use (\$columns): void {
                foreach (\$columns as \$column) {
                    \$table->json(\$column.'__json_tmp')->nullable();
                }
            });

            DB::table(\$table)
                ->orderBy(\$primaryKey)
                ->chunkById(\$this->chunkSize, function (\$rows) use (\$table, \$primaryKey, \$columns): void {
                    foreach (\$rows as \$row) {
                        \$updates = [];

                        foreach (\$columns as \$column) {
                            \$value = \$row->{\$column};

                            \$updates[\$column.'__json_tmp'] = \$value === null
                                ? null
                                : json_encode(
                                    [\$this->defaultLocale => \$value],
                                    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                                );
                        }

                        DB::table(\$table)
                            ->where(\$primaryKey, \$row->{\$primaryKey})
                            ->update(\$updates);
                    }
                }, \$primaryKey);

            Schema::table(\$table, function (Blueprint \$table) use (\$columns): void {
                foreach (\$columns as \$column) {
                    \$table->dropColumn(\$column);
                }
            });

            Schema::table(\$table, function (Blueprint \$table) use (\$columns): void {
                foreach (\$columns as \$column) {
                    \$table->renameColumn(\$column.'__json_tmp', \$column);
                }
            });
        }
    }

    public function down(): void
    {
        foreach (\$this->models as \$model) {
            \$table = \$model['table'];
            \$primaryKey = \$model['primary_key'];
            \$columns = \$model['translatable'];

            Schema::table(\$table, function (Blueprint \$table) use (\$columns): void {
                foreach (\$columns as \$column) {
                    \$table->text(\$column.'__text_tmp')->nullable();
                }
            });

            DB::table(\$table)
                ->orderBy(\$primaryKey)
                ->chunkById(\$this->chunkSize, function (\$rows) use (\$table, \$primaryKey, \$columns): void {
                    foreach (\$rows as \$row) {
                        \$updates = [];

                        foreach (\$columns as \$column) {
                            \$decoded = json_decode((string) \$row->{\$column}, true);

                            \$updates[\$column.'__text_tmp'] = is_array(\$decoded)
                                ? (\$decoded[\$this->defaultLocale] ?? null)
                                : null;
                        }

                        DB::table(\$table)
                            ->where(\$primaryKey, \$row->{\$primaryKey})
                            ->update(\$updates);
                    }
                }, \$primaryKey);

            Schema::table(\$table, function (Blueprint \$table) use (\$columns): void {
                foreach (\$columns as \$column) {
                    \$table->dropColumn(\$column);
                }
            });

            Schema::table(\$table, function (Blueprint \$table) use (\$columns): void {
                foreach (\$columns as \$column) {
                    \$table->renameColumn(\$column.'__text_tmp', \$column);
                }
            });
        }
    }
};
PHP;
    }

    /**
     * @param  array<int, DiscoveredTranslatableModel>  $models
     */
    protected function exportModels(array $models): string
    {
        if ($models === []) {
            return '[]';
        }

        $lines = ["["];

        foreach ($models as $model) {
            $attributes = implode(', ', array_map(
                fn (string $attribute): string => var_export($attribute, true),
                $model->attributes
            ));

            $lines[] = '        [';
            $lines[] = "            'model' => \\".ltrim($model->modelClass, '\\')."::class,";
            $lines[] = "            'table' => ".var_export($model->table, true).",";
            $lines[] = "            'primary_key' => ".var_export($model->primaryKey, true).",";
            $lines[] = "            'translatable' => [{$attributes}],";
            $lines[] = '        ],';
        }

        $lines[] = '    ]';

        return implode("\n", $lines);
    }
}
