<?php

namespace App\Models;

use App\Http\Controllers\NotificationsController;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;
use TCG\Voyager\Facades\Voyager;
use App\Models\Routine;
use TCG\Voyager\Traits\Translatable;


class Workout extends CustomModel
{
    use Translatable;
    use HasThumbnails {
        save as thumbnailSave;
    }
    protected $translatable = ['title', 'description'];
    protected $thumbnailsSizes = ['345x181' => [345, 181], '690x362' => [690, 362], '1035x543' => [1035, 543],
        '146x96' => [146, 96], '292x192' => [292, 192], '438x288' => [438, 288],
        '237x161' => [237, 161], '474x322' => [474, 322], '711x483' => [711, 483],
        '233x140' => [233, 140], '466x280' => [466, 280], '699x420' => [699, 420], ];

    protected $guarded = ['id'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function instructor()
    {
        return $this->belongsTo(Voyager::modelClass('Instructor'));
    }
    /**
     * @return \Illuminate\Database\Eloquent\Relations\belongsToMany
     */
    public function routines()
    {
        return $this->belongsToMany('App\Models\Routine');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\hasMany
     */
    public function favorites()
    {
        return $this->hasMany('App\Models\Favorite');
    }

    public function getStatusObject($userId, $workoutId) {
        $usersWorkout = UsersWorkout::where([['user_id', $userId],['workout_id', $workoutId],['status', '<>', 'completed']])->get()->first();
        if (!empty($usersWorkout)) {
            return $usersWorkout;
        } else {
            $usersWorkout = UsersWorkout::where([['user_id', $userId],['workout_id', $workoutId],['status', 'completed']])->get()->last();
            return $usersWorkout;
        }
    }

    /**
     * @return array
     */
    static function workoutList($condition = NULL, $value = NULL) {
        $user = auth()->guard('api')->user();
        $language = $user->language ?? 'enGB';
        if ($condition) {
            $workouts = Workout::where([[$condition, $value],['language', 'like' , '%' . $language . '%']])
                               ->orderByDesc('created_at')->get();
        } else {
            $workouts = Workout::where('language', 'like' , '%' . $language . '%')
                               ->orderByDesc('created_at')
                               ->get();
        }
        if (isset($workouts[0])) {
            foreach ($workouts as &$workout) {
                $workout = Workout::workoutLoad($workout);
            }
        }
        return $workouts;
    }

    /**
     * @return Workout
     */
    static function workoutLoad($workout)
    {
        if (!is_object($workout)) {
            $workout = Workout::where('id', $workout)->get()->first();
        }
        if ($workout) {
            if (!empty($workout->icon)) {
                $workout->generateThumbnails('icon');
            }
            if (!empty($workout->class_image)) {
                $workout->generateThumbnails('class_image');
            }
            //$routines = $workout->routines()->get();
            if ($routines = json_decode($workout->routines)) {
                foreach ($routines as &$routine) {
                    $object = Routine::where('id',$routine->routineId)->get()->first();
                    if ($object) {
                        $object->loadTranslated();
                        $object->generateThumbnails('icon');
                        $timeParts = array_reverse(explode(':', $routine->start));
                        $start = 0;
                        $i = 0;
                        foreach ($timeParts as $timePart) {
                            $start += $timePart * pow(60, $i);
                            $i++;
                        }
                        $routine->start = $start;
                        if ($object->icon) {
                            $routine->icon = $object->icon;
                        }
                        $routine->title = $object->title;
                    }
                }
                $workout->routines = $routines;
            }
            $video_data = json_decode($workout->video_data);
            if (isset($video_data->files)) {
                $workout->videos = $video_data->files;
            }
            unset($workout->video_data);
            unset($workout->thumbnails);
            if ($usersWorkout = $workout->getStatusObject(auth()->guard('api')->id(),$workout->id)) {
                $workout->status = $usersWorkout->status;
                $workout->timestamp = $usersWorkout->timestamp;
                $workout->status_updated = $usersWorkout->created_at;
            }
            $workout->favorite = $workout->favorites()->where('user_id',auth()->guard('api')->id())->get();
        }
        $workout->loadTranslated();
        return $workout;
    }

    public function save(array $options = []) {
        if (!$this->exists) {
            NotificationsController::scheduleNotification(0, 'new_workout', 'morning', [$this->title]);
        }
        $this->thumbnailSave(array_merge($options,['thumbnails_only' => TRUE]));
        parent::save($options);
    }

}
