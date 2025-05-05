<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use TCG\Voyager\Facades\Voyager;
use TCG\Voyager\Traits\Translatable;

class Routine extends CustomModel
{
    use HasThumbnails, Translatable;
    protected $translatable = ['title'];
    protected $thumbnailsSizes = ['86x60' => [86, 60], '172x120' => [172, 120], '258x180' => [258, 180]];

    protected $guarded = ['id'];
    /**
     * @return \Illuminate\Database\Eloquent\Relations\hasMany
     */
    public function workout()
    {
        return $this->hasMany('App\Models\Workout');
    }
}
