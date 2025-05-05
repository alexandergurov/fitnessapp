<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use TCG\Voyager\Traits\Translatable;

class Category extends CustomModel
{
    use Translatable;
    protected $translatable = ['name'];
    protected $guarded = ['id'];
}
