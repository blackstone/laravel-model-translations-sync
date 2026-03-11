<?php

namespace BlackstonePro\ModelTranslationsSync\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use ReflectionClass;
use Spatie\Translatable\HasTranslations;
use Throwable;
use BlackstonePro\ModelTranslationsSync\DTO\DiscoveredTranslatableModel;

class TranslatableModelsDiscovery
{
    /**
     * @param  array<int, string>  $paths
     * @return array<int, DiscoveredTranslatableModel>
     */
    public function discover(array $paths = []): array
    {
        $classes = collect($paths !== [] ? $paths : config('model-translations.translatable_migration.paths', [app_path('Models')]))
            ->filter(fn (mixed $path): bool => is_string($path) && $path !== '' && File::isDirectory($path))
            ->flatMap(fn (string $path): Collection => $this->classesFromPath($path))
            ->filter()
            ->unique()
            ->values();

        $models = $classes
            ->map(fn (string $class): ?DiscoveredTranslatableModel => $this->buildDiscoveredModel($class))
            ->filter()
            ->sortBy(fn (DiscoveredTranslatableModel $model): string => $model->modelClass)
            ->values();

        /** @var array<int, DiscoveredTranslatableModel> $result */
        $result = $models->all();

        return $result;
    }

    /**
     * @return Collection<int, string>
     */
    protected function classesFromPath(string $path): Collection
    {
        return collect(File::allFiles($path))
            ->filter(fn ($file): bool => $file->getExtension() === 'php')
            ->map(fn ($file): ?string => $this->classFromFile($file->getPathname()))
            ->filter();
    }

    protected function buildDiscoveredModel(string $class): ?DiscoveredTranslatableModel
    {
        if (! class_exists($class) || ! is_subclass_of($class, Model::class)) {
            return null;
        }

        $reflection = new ReflectionClass($class);

        if ($reflection->isAbstract()) {
            return null;
        }

        if (! in_array(HasTranslations::class, class_uses_recursive($class), true)) {
            return null;
        }

        try {
            /** @var Model $model */
            $model = app($class);
        } catch (Throwable) {
            return null;
        }

        $attributes = array_values(array_filter(
            array_map('strval', Arr::wrap($model->translatable ?? [])),
            fn (string $attribute): bool => $attribute !== ''
        ));

        if ($attributes === []) {
            return null;
        }

        $table = $model->getTable();

        if (! Schema::hasTable($table)) {
            return null;
        }

        $columns = Schema::getColumnListing($table);
        $attributes = array_values(array_filter($attributes, fn (string $attribute): bool => in_array($attribute, $columns, true)));

        if ($attributes === []) {
            return null;
        }

        sort($attributes);

        return new DiscoveredTranslatableModel(
            modelClass: $class,
            table: $table,
            primaryKey: $model->getKeyName(),
            attributes: $attributes,
        );
    }

    protected function classFromFile(string $path): ?string
    {
        $contents = File::get($path);
        $namespace = null;
        $class = null;
        $tokens = token_get_all($contents);

        for ($i = 0, $count = count($tokens); $i < $count; $i++) {
            $token = $tokens[$i];

            if (! is_array($token)) {
                continue;
            }

            if ($token[0] === T_NAMESPACE) {
                $namespace = $this->collectQualifiedName($tokens, $i + 1);
            }

            if ($token[0] === T_CLASS) {
                $class = $this->collectQualifiedName($tokens, $i + 1, false);
                break;
            }
        }

        if (! $class) {
            return null;
        }

        $fqcn = $namespace ? "{$namespace}\\{$class}" : $class;

        require_once $path;

        return class_exists($fqcn) ? $fqcn : null;
    }

    /**
     * @param  array<int, mixed>  $tokens
     */
    protected function collectQualifiedName(array $tokens, int $start, bool $allowSeparator = true): ?string
    {
        $segments = [];

        for ($i = $start, $count = count($tokens); $i < $count; $i++) {
            $token = $tokens[$i];

            if (! is_array($token)) {
                if ($allowSeparator && $token === '\\') {
                    continue;
                }

                if ($segments !== []) {
                    break;
                }

                continue;
            }

            if (in_array($token[0], [T_STRING, T_NAME_QUALIFIED, T_NS_SEPARATOR], true)) {
                $segments[] = $token[1];
                continue;
            }

            if ($token[0] === T_WHITESPACE && $segments === []) {
                continue;
            }

            if ($segments !== []) {
                break;
            }
        }

        $name = trim(implode('', $segments), '\\');

        return $name !== '' ? $name : null;
    }
}
