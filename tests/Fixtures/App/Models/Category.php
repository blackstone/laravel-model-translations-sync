<?php

namespace BlackstonePro\ModelTranslationsSync\Tests\Fixtures\App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class Category extends Model
{
    use HasTranslations;

    protected $table = 'categories';

    protected $primaryKey = 'uuid';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    public array $translatable = ['title', 'missing_column'];
}
