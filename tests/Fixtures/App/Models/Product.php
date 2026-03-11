<?php

namespace BlackstonePro\ModelTranslationsSync\Tests\Fixtures\App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;
use BlackstonePro\ModelTranslationsSync\Traits\ModelTranslatable;

class Product extends Model
{
    use HasTranslations;
    use ModelTranslatable;

    protected $table = 'products';

    protected $guarded = [];

    public array $translatable = ['title', 'description'];
}
