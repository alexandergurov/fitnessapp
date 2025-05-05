<?php

namespace App\Models;

use App\Http\Controllers\NotificationsController;
use TCG\Voyager\Facades\Voyager;
use TCG\Voyager\Models\Post;
use TCG\Voyager\Traits\Translatable;

class FinessAppPost extends Post
{
    use Translatable;
    use HasThumbnails {
        save as thumbnailSave;
    }
    protected $translatable = ['title', 'body'];
    protected $table = 'posts';
    protected $thumbnailsSizes = ['375x242' => [375, 242], '750x484' => [750, 484], '1125x726' => [1125, 726],];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\belongsTo
     */
    public function instructor()
    {
        return $this->belongsTo('App\Models\Instructor');
    }

    public function loadTranslated () {
        $locale = getLocale();
        $translatable = $this->translatable;
        foreach ($translatable as $field) {
            $this->{$field} = $this->getTranslatedAttribute($field, $locale, 'en');
        }
    }

    public function save(array $options = []) {
        if (!$this->exists) {
            NotificationsController::scheduleNotification(0, 'new_article', 'morning', [$this->title]);
        }
        $this->thumbnailSave(array_merge($options,['thumbnails_only' => TRUE]));
        parent::save($options);
    }
}
