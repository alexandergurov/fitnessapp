<?php

namespace App\Models;

use App\Http\Controllers\NotificationsController;
use Illuminate\Database\Eloquent\Model;
use TCG\Voyager\Traits\Translatable;

class Instructor extends CustomModel
{
    use Translatable;
    use HasThumbnails {
        save as thumbnailSave;
    }
    protected $translatable = ['bio'];
    protected $thumbnailsSizes = ['375x242' => [375, 242], '750x484' => [674, 484], '1125x726' => [1125, 726],];

    protected $guarded = ['id'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\hasMany
     */
    public function posts() {
        return $this->hasMany('App\Models\FinessAppPost');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\hasMany
     */
    public function workouts() {
        return $this->hasMany('App\Models\Workout');
    }


    /**
     * @return Instructor
     */
    static function instructorLoad($id) {
        $instructor = Instructor::where('id', $id)->get()->first();
        $posts = $instructor->posts()->get()->all();
        foreach ($posts as &$post) {
            $post->loadTranslated();
        }
        $instructor->posts = $posts;
        $workouts = $instructor->workouts()->get()->all();
        foreach ($workouts as &$workout) {
            $workout->loadTranslated();
        }
        $instructor->workouts = $workouts;
        $instructor->loadTranslated();
        return $instructor;
    }

    public function save(array $options = []) {
        if (!$this->exists) {
            NotificationsController::scheduleNotification(0, 'new_trainer', 'morning');
        }
        $this->thumbnailSave(array_merge($options,['thumbnails_only' => TRUE]));
        parent::save($options);
    }
}
