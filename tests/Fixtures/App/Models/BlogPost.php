<?php

namespace BlackstonePro\ModelTranslationsSync\Tests\Fixtures\App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;
use BlackstonePro\ModelTranslationsSync\Traits\ModelTranslatable;

class BlogPost extends Model
{
    use HasTranslations;
    use ModelTranslatable;

    protected $table = 'blog_posts';

    protected $guarded = [];

    public array $translatable = ['title'];
}
