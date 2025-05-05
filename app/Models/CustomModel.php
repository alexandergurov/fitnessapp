<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;

class CustomModel extends Model {
    public function loadTranslated () {
        $locale = getLocale();
        $translatable = $this->translatable;
        foreach ($translatable as $field) {
            $this->{$field} = $this->getTranslatedAttribute($field, $locale, 'en');
        }
    }
}
