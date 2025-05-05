<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use TCG\Voyager\Traits\Translatable;

class WorkoutCategory extends CustomModel
{
    use HasThumbnails, Translatable;
    protected $translatable = ['title'];
    protected $thumbnailsSizes = ['141x90' => [141, 90], '282x180' => [282, 180], '423x270' => [423, 270],];

    protected $guarded = ['id'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\hasMany
     */
    public function workouts()
    {
        return $this->hasMany('App\Models\Workout');
    }


    /**
     * @return Workout
     */
    static function workoutCategoryLoad($workoutCategory)
    {
        if (!is_object($workoutCategory)) {
            $workoutCategory =  WorkoutCategory::where('id', $workoutCategory)->get()->first();
        }
        if (!empty($workoutCategory->icon)) {
            $workoutCategory->generateThumbnails('icon');
        }
        $workouts = $workoutCategory->workouts()->get();
        foreach ($workouts as &$workout) {
            $workout = Workout::workoutLoad($workout);
        }
        $workoutCategory->workouts = $workouts;
        $types = json_decode($workoutCategory->type,TRUE);
        $workoutCategory->type = array_values($types);
        $count = [];
        foreach ($types as $type) {
            $count[$type] = Workout::where([['workout_category_id',$workoutCategory->id],['type',$type,'%LIKE%']])->get()->count();
        }
        $workoutCategory->classes_count = $count;
        $workoutCategory->loadTranslated();
        return $workoutCategory;
    }

    /**
     * @return array
     */
    static function workoutCategoryList() {
        $user = auth()->guard('api')->user();
        $language = $user->language ?? 'enGB';
        $workoutCategories = WorkoutCategory::where('language', 'like' , '%' . $language . '%')
                                            ->orderByDesc('created_at')
                                            ->get();
        if (isset($workoutCategories[0])) {
            foreach ($workoutCategories as &$workoutCategory) {
                if (!empty($workoutCategory->icon)) {
                    $workoutCategory->generateThumbnails('icon');
                }
                $types = json_decode($workoutCategory->type,TRUE);
                $workoutCategory->type = array_values($types);
                $count = [];
                foreach ($types as $type) {
                    $count[$type] = Workout::where([['workout_category_id',$workoutCategory->id],['type',$type,'%LIKE%']])->get()->count();
                }
                $workoutCategory->classes_count = $count;
            }
        }
        return $workoutCategories;
    }
}
