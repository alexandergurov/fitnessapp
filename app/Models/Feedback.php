<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class Feedback extends CustomModel
{
    protected $guarded = ['id'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\hasOne
     */
    public function user()
    {
        return $this->hasOne('App\Models\User');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\hasOne
     */
    public function workout()
    {
        return $this->hasOne('App\Models\Workout');
    }

    static function postFeedback($userId, $workoutId) {
        $favorited = Favorite::where([
            ['user_id', $userId],
            ['workout_id',$workoutId],
        ])->get()->first();
        if ($favorited) {
            $favorited->delete();
            return 'Removed from favorites';
        }
        else {
            $favoriteEntry = Favorite::create([
                'user_id' => $userId,
                'workout_id' => $workoutId,
            ]);
            return $favoriteEntry;
        }
    }

    static function createDelete($userId, $workoutId) {
        $favorited = Favorite::where([
            ['user_id', $userId],
            ['workout_id',$workoutId],
        ])->get()->first();
        if ($favorited) {
            $favorited->delete();
            return 'Removed from favorites';
        }
        else {
            $favoriteEntry = Favorite::create([
                'user_id' => $userId,
                'workout_id' => $workoutId,
            ]);
            return $favoriteEntry;
        }
    }

    /**
     * @return array
     */
    static function favoritedList($condition = NULL, $value = NULL) {
        if ($condition) {
            $favorited = Favorite::where($condition, $value)->get();
        } else {
            $favorited = Favorite::all();
        }
        $workouts = [];
        if (isset($favorited[0])) {
            foreach ($favorited as &$favorite) {
                $workouts[] = Workout::workoutLoad($favorite->workout_id);
            }
        }
        return $workouts;
    }
}
