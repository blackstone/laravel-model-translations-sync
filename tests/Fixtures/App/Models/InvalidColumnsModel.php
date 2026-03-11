<?php

namespace BlackstonePro\ModelTranslationsSync\Tests\Fixtures\App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class InvalidColumnsModel extends Model
{
    use HasTranslations;

    protected $table = 'invalid_columns';

    protected $guarded = [];

    public array $translatable = ['missing_only'];
}
