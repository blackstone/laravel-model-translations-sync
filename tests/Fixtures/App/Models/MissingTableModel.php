<?php

namespace BlackstonePro\ModelTranslationsSync\Tests\Fixtures\App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class MissingTableModel extends Model
{
    use HasTranslations;

    protected $table = 'missing_table_models';

    protected $guarded = [];

    public array $translatable = ['title'];
}
