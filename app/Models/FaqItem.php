<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use TCG\Voyager\Traits\Translatable;

class FaqItem extends CustomModel
{
    use Translatable;
    protected $translatable = ['question', 'answer'];
    /**
     * @return \Illuminate\Database\Eloquent\Relations\belongsTo
     */
    public function faqTopic()
    {
        return $this->belongsTo('App\Models\FaqTopic');
    }
}
