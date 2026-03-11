<?php

namespace BlackstonePro\ModelTranslationsSync\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use BlackstonePro\ModelTranslationsSync\Traits\ModelTranslatable;

class ModelDiscoveryService
{
    /**
     * @return array<int, class-string<Model>>
     */
    public function discover(): array
    {
        $models = collect(config('model-translations.models.list', []))
            ->merge($this->discoverFromPaths())
            ->filter(fn (mixed $class): bool => is_string($class) && class_exists($class))
            ->filter(fn (string $class): bool => is_subclass_of($class, Model::class))
            ->filter(fn (string $class): bool => in_array(ModelTranslatable::class, class_uses_recursive($class), true))
            ->unique()
            ->values();

        /** @var array<int, class-string<Model>> $result */
        $result = $models->all();

        return $result;
    }

    /**
     * @return array<string, class-string<Model>>
     */
    public function getNamespaceMap(): array
    {
        $configured = config('model-translations.models.namespace_map', []);
        $discovered = [];

        foreach ($this->discover() as $class) {
            $model = app($class);
            $discovered[$model->getModelTranslationNamespace()] = $class;
        }

        return array_merge($discovered, $configured);
    }

    /**
     * @return array<int, string>
     */
    protected function discoverFromPaths(): array
    {
        if (! config('model-translations.models.auto_discover', true)) {
            return [];
        }

        return collect(Arr::wrap(config('model-translations.models.paths', [])))
            ->filter(fn (mixed $path): bool => is_string($path) && $path !== '' && File::isDirectory($path))
            ->flatMap(function (string $path): Collection {
                return collect(File::allFiles($path))
                    ->filter(fn ($file): bool => $file->getExtension() === 'php')
                    ->map(fn ($file): ?string => $this->classFromFile($file->getPathname()))
                    ->filter();
            })
            ->values()
            ->all();
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
