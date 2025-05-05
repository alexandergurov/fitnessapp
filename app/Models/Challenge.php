<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use TCG\Voyager\Traits\Translatable;

class Challenge extends CustomModel
{
    use HasThumbnails, Translatable;
    protected $translatable = ['title', 'description'];
    protected $thumbnailsSizes = ['375x228' => [375, 228], '750x456' => [750, 456], '1125x684' => [1125, 684],];

    protected $guarded = ['id'];
}
