<?php

namespace BlackstonePro\ModelTranslationsSync\Support;

use Illuminate\Filesystem\Filesystem;

class LangFileReader
{
    public function __construct(
        protected Filesystem $files,
    ) {
    }

    /**
     * @return array<mixed>
     */
    public function readPhp(string $path): array
    {
        $data = require $path;

        return is_array($data) ? $data : [];
    }

    /**
     * @return array<string, string>
     */
    public function readJson(string $path): array
    {
        if (! $this->files->exists($path)) {
            return [];
        }

        return json_decode($this->files->get($path), true, 512, JSON_THROW_ON_ERROR) ?? [];
    }
}
