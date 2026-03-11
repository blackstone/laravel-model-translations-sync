<?php

namespace BlackstonePro\ModelTranslationsSync\Support;

use Illuminate\Filesystem\Filesystem;

class LangFileWriter
{
    public function __construct(
        protected Filesystem $files,
    ) {
    }

    /**
     * @param  array<mixed>  $data
     */
    public function writePhp(string $path, array $data, bool $overwrite = true): bool
    {
        if (! $overwrite && $this->files->exists($path)) {
            return false;
        }

        $this->files->ensureDirectoryExists(dirname($path));

        $contents = "<?php\n\nreturn ".$this->exportArray($data).";\n";
        $this->files->put($path, $contents);

        return true;
    }

    /**
     * @param  array<mixed>  $data
     */
    public function writeJson(string $path, array $data, bool $overwrite = true, bool $prettyPrint = true): bool
    {
        if (! $overwrite && $this->files->exists($path)) {
            return false;
        }

        $this->files->ensureDirectoryExists(dirname($path));

        $flags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;

        if ($prettyPrint) {
            $flags |= JSON_PRETTY_PRINT;
        }

        $this->files->put($path, json_encode($data, $flags).PHP_EOL);

        return true;
    }

    /**
     * @param  array<mixed>  $data
     */
    protected function exportArray(array $data, int $indent = 0): string
    {
        if ($data === []) {
            return '[]';
        }

        $padding = str_repeat('    ', $indent);
        $childPadding = str_repeat('    ', $indent + 1);
        $lines = ['['];

        foreach ($data as $key => $value) {
            $encodedKey = is_int($key) ? $key : var_export((string) $key, true);
            $encodedValue = is_array($value)
                ? $this->exportArray($value, $indent + 1)
                : var_export($value, true);

            $lines[] = "{$childPadding}{$encodedKey} => {$encodedValue},";
        }

        $lines[] = "{$padding}]";

        return implode("\n", $lines);
    }
}
