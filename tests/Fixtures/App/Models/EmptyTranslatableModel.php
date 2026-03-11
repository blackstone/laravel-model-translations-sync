<?php

namespace BlackstonePro\ModelTranslationsSync\Tests\Fixtures\App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class EmptyTranslatableModel extends Model
{
    use HasTranslations;

    protected $table = 'plain_models';

    protected $guarded = [];

    public array $translatable = [];
}
