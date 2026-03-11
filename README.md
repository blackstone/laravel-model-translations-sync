# blackstone/laravel-model-translations-sync

Laravel package for synchronizing translations between:

- translatable Eloquent model JSON attributes via `spatie/laravel-translatable`
- `language_lines` via `spatie/laravel-translation-loader`
- local Laravel `lang` files
- external translation flows such as Crowdin / larex

Pipeline target:

`Models -> DB (language_lines) -> Crowdin/larex -> DB -> Models -> Files`

## Requirements

- PHP 8.3, 8.4+
- Laravel 11 or 12
- `spatie/laravel-translatable`
- `spatie/laravel-translation-loader`

## Installation

```bash
composer require blackstone/laravel-model-translations-sync
```

Publish config:

```bash
php artisan vendor:publish --tag=model-translations-config
```

## Model setup

Add `HasTranslations` from Spatie and the package trait:

```php
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;
use BlackstonePro\ModelTranslationsSync\Traits\ModelTranslatable;

class Product extends Model
{
    use HasTranslations;
    use ModelTranslatable;

    protected $fillable = ['title', 'description'];

    public array $translatable = ['title', 'description'];
}
```

Default behavior:

- `getModelTranslationNamespace()` returns snake_case model basename
- `getTranslatableAttributesForSync()` returns `$this->translatable`

You can override both methods in the model if needed.

## Storage format in `language_lines`

Model translations are stored as:

- `group = models.{namespace}`
- `key = {id}.{attribute}`
- `text = {"en":"...", "fr":"..."}`

Example:

```text
group = models.product
key = 12.title
text = {"en":"iPhone","fr":"iPhone"}
```

## Configuration

Published config file: [config/model-translations.php](/Users/rarkhipov/www/laravel/laravel-model-translations-sync/config/model-translations.php)

Main options:

- `models.auto_discover`: discover models from configured paths
- `models.paths`: directories for model discovery
- `models.list`: explicit model class list
- `models.namespace_map`: manual namespace to model mapping
- `locales`: supported locales for sync
- `default_locale`: fallback locale
- `ignore_groups`: groups ignored during file export
- `export_path`: destination for generated lang files
- `export.overwrite`, `export.pretty_print`, `export.sort_keys`
- `sync.stages.*`: enable or disable sync pipeline stages

## Commands

Export models to `language_lines`:

```bash
php artisan translations:export-models {model?} {--fresh} {--chunk=500} {--dry-run}
```

Import model translations from `language_lines`:

```bash
php artisan translations:import-models {--locale=} {--group=} {--chunk=500} {--dry-run}
```

Export DB translations to local files:

```bash
php artisan translations:export-files {--dry-run}
```

Import local files back into DB:

```bash
php artisan translations:import-files {--dry-run}
```

Run the full pipeline:

```bash
php artisan translations:sync {--dry-run}
```

Pipeline stages:

1. `translations:export-models`
2. `larex:export` if command exists and stage is enabled
3. `larex:import` if command exists and stage is enabled
4. `translations:import-models`
5. `translations:export-files`

## Source of truth

- `translations:export-models`: models are source of truth
- `translations:import-models`: `language_lines` is source of truth
- `translations:export-files`: `language_lines` is source of truth
- `translations:import-files`: lang files are source of truth

## File formats

Supported export/import targets:

- `resources/lang/{locale}/*.php`
- `resources/lang/{locale}.json`
- `resources/lang/{locale}/models.php`

Model translations in `models.php` are structured as:

```php
return [
    'product' => [
        12 => [
            'title' => 'iPhone',
            'description' => 'Smartphone',
        ],
    ],
];
```

## Testing

```bash
composer test
```
