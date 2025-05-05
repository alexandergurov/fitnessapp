<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Http\Controllers\BadgeController;


class UsersWorkout extends CustomModel
{
    protected $guarded = ['id'];


    static function currentStreak($length = 0) {
        $count = UsersWorkout::where([
            [
                'user_id',
                auth()->guard('api')->id()
            ],
            [
                'created_at',
                '>=',
                date('Y-m-d', strtotime('-' . $length . ' day')) . ' 00:00:00'
            ],
            [
                'created_at',
                '<=',
                date('Y-m-d', strtotime('-' . $length . ' day')) . ' 23:59:59'
            ]
        ])->get()->count();
        if ($count > 0) {
            $length++;
            return UsersWorkout::currentStreak($length);
        } else {
            return $length;
        }
    }

    static function overallCompletedAmount() {
        return UsersWorkout::where(
            [['user_id', auth()->guard('api')->id()],
                ['status', 'completed']]
        )->get()->count();
    }

    static function overallLength($startDate,$endDate,$uid = NULL) {
        if (!$uid) {
            $uid = auth()->guard('api')->id();
        }
        $usersWorkouts = UsersWorkout::where([
            [
                'user_id',
                $uid
            ],
            [
                'created_at',
                '>=',
                $startDate
            ],
            [
                'created_at',
                '<=',
                $endDate
            ]
        ])->get()->all();
        $length = 0;
        foreach ($usersWorkouts as $usersWorkout) {
            if ($workout = Workout::where('id',$usersWorkout->workout_id)->get()->first()) {
                $length += $workout->length;
            }
        }
        return $length;
    }

    static function mostTimeWorkouting($startDate, $endDate) {
        $usersWorkouts = UsersWorkout::where([
            ['created_at', '>=', $startDate],
            ['created_at', '<=', $endDate]
        ])->selectRaw('user_id')->groupBy('user_id')->get()->all();
        $bestUid = NULL;
        $best_length = 0;
        foreach ($usersWorkouts as $uid => $usersWorkout) {
            $length = UsersWorkout::overallLength($startDate, $endDate, $usersWorkout->user_id);
            if ($best_length < $length) {
                $best_length = $length;
                $bestUid = $usersWorkout->user_id;
            }
        }
        return $bestUid;
    }

    public function save(array $options = []) {
        parent::save($options);
        BadgeController::processTriggers('workout');
    }

}
