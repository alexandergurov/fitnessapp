<?php

namespace App\Models;

use App\Http\Controllers\BadgeController;
use Illuminate\Database\Eloquent\Model;

class ChallengeCompletion extends CustomModel {
    protected $guarded = ['id'];

    static function monthlyCompletedAmount() {
        return ChallengeCompletion::where([
            [
                'user_id',
                auth()->guard('api')->id()
            ],
            [
                'created_at',
                '>=',
                date('Y-m-d', strtotime('first day of previous month')) . ' 00:00:00'
            ],
            [
                'created_at',
                '<=',
                date('Y-m-d', strtotime('last day of this month')) . ' 00:00:00'
            ]
        ])->get()->count();
    }

    static function uniqueCompletedAmount() {
        return ChallengeCompletion::where([
            [
                'user_id',
                auth()->guard('api')->id()
            ],
        ])->selectRaw('challenge_id')->groupBy('challenge_id')->get()->count();
    }

    static function overallCompletedAmount() {
        return ChallengeCompletion::where([
            [
                'user_id',
                auth()->guard('api')->id()
            ],
        ])->selectRaw('challenge_id')->get()->count();
    }

    static function bestResultInChallenge($startDate, $endDate, $challengeId) {
        $challengeCompletions = ChallengeCompletion::where([
            ['challenge_id', $challengeId],
            ['created_at', '>=', $startDate],
            ['created_at', '<=', $endDate]
        ])->get()->all();
        $bestResult = 0;
        $bestUid = NULL;
        foreach ($challengeCompletions as $challengeCompletion) {
            if ($challengeCompletion->result_amount) {
                $result = $challengeCompletion->result_amount;
                if ($bestResult < $result) {
                    $bestResult = $result;
                    $bestUid = $challengeCompletion->user_id;
                }
            } elseif ($challengeCompletion->result_timer) {
                $result = $challengeCompletion->result_timer;
                if ($bestResult ==0 || $bestResult > $result) {
                    $bestResult = $result;
                    $bestUid = $challengeCompletion->user_id;
                }
            }
        }
        return $bestUid;
    }

    public function save(array $options = []) {
        parent::save($options);
        BadgeController::processTriggers('challenge');
    }
}
