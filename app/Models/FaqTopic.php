<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use TCG\Voyager\Traits\Translatable;

class FaqTopic extends CustomModel
{
    use HasThumbnails, Translatable;
    protected $translatable = ['title'];
    /**
     * @return FaqTopic
     */
    static function faqTopicLoad($id)
    {
        $faqTopic = FaqTopic::where('id', $id)->get()->first();
        $faqItems = $faqTopic->faqItems()->get();
        foreach ($faqItems as &$faqItem) {
            $faqItem->loadTranslated();
        }
        $faqTopic->faq_items = $faqItems;
        $faqTopic->loadTranslated();
        return $faqTopic;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\hasMany
     */
    public function faqItems()
    {
        return $this->hasMany('App\Models\FaqItem');
    }
}
