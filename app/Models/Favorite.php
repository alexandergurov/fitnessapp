<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class Favorite extends CustomModel
{
    protected $guarded = ['id'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\hasMany
     */
    public function users()
    {
        return $this->hasMany('App\Models\User');
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
            $favorited = Favorite::where($condition, $value)->orderByDesc('created_at')->get();
        } else {
            $favorited = Favorite::orderByDesc('created_at')->get();
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
