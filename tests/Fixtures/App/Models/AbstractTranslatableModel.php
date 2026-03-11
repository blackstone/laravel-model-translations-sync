<?php

namespace BlackstonePro\ModelTranslationsSync\Tests\Fixtures\App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

abstract class AbstractTranslatableModel extends Model
{
    use HasTranslations;
}
