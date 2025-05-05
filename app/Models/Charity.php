<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use TCG\Voyager\Traits\Translatable;

class Charity extends CustomModel
{
    use HasThumbnails, Translatable;
    protected $translatable = ['title', 'description'];
    protected $guarded = ['id'];
    protected $thumbnailsSizes = ['337x110' => [337, 110], '674x220' => [674, 220], '1011x330' => [1011, 330], ];
}
