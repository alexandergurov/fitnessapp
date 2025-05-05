<?php

namespace App\Models;

use App\Http\Controllers\BadgeController;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use TCG\Voyager\Traits\Translatable;

class Badge extends CustomModel
{
    use HasThumbnails, Translatable;
    protected $translatable = ['title', 'description'];
    protected $thumbnailsSizes = ['45x45' => [45, 45], '90x90' => [90, 90], '135x135' => [135, 135]];

    protected $guarded = ['id'];

    static function allBadges() {

    }

    static function getOne($badge) {
        if (!is_object($badge)) {
            $badge = Badge::where('id', $badge)->get()->first();
        }
        $badge->generateThumbnails('icon');
        $badge->loadTranslated();
        if (!strpos($badge->triggers, 'monthly')) {
            $conditions = json_decode($badge->conditions, TRUE);
            if (isset($conditions['overall_amount'])) {
                foreach ($conditions['overall_amount'] as $type => $condition) {
                    switch ($type) {
                        case 'challenges':
                            $completed = ChallengeCompletion::uniqueCompletedAmount();
                            if ($completed > $conditions['overall_amount'][$type]) {
                                $completed = $conditions['overall_amount'][$type];
                            }
                            $conditions['overall_amount'][$type] = $completed . '/' . $conditions['overall_amount'][$type];
                            break;
                        case 'challenges participations':
                            $completed = ChallengeCompletion::overallCompletedAmount();
                            if ($completed > $conditions['overall_amount'][$type]) {
                                $completed = $conditions['overall_amount'][$type];
                            }
                            $conditions['overall_amount'][$type] = $completed . '/' . $conditions['overall_amount'][$type];
                            break;
                        case 'workouts':
                            $completed = UsersWorkout::overallCompletedAmount();
                            if ($completed > $conditions['overall_amount'][$type]) {
                                $completed = $conditions['overall_amount'][$type];
                            }
                            $conditions['overall_amount'][$type] = $completed . '/' . $conditions['overall_amount'][$type];
                            break;
                    }
                }
                $badge->conditions = $conditions['overall_amount'];
            }
        }
        return $badge;
    }

    static function usersBadgesStatuses() {
        $badges = [];
        $badgeStatuses = BadgeStatus::where([['user_id', auth()->guard('api')->id()]])->get()->all();
        foreach ($badgeStatuses as $badgeStatus) {
            $badge = Badge::getOne($badgeStatus->badge_id);
            $badge->completed = $badgeStatus->created_at;
            $badges[] = $badge;
        }

        $relevantBadges = Badge::where('triggers', 'not like', '%' . 'monthly' . '%')
                               ->get();
        foreach ($relevantBadges as $relevantBadge) {
            $id = $relevantBadge->id;
            if (!array_filter(
                $badges,
                function ($e) use (&$id) {
                    return $e->id == $id;
                })) {
                $badges[] = Badge::getOne($relevantBadge);
            }
        }
        return $badges;
    }

    public function checkConditionsAndAssign() {
        $conditions = json_decode($this->conditions, TRUE);
        if (is_array($conditions)) {
            $conditionFailed = FALSE;
            $userId = auth()->guard('api')->id();
            foreach ($conditions as $type => $condition) {
                switch ($type) {
                    case 'overall_amount':
                        if (isset($condition['workouts'])) {
                            if ($condition['workouts'] > UsersWorkout::overallCompletedAmount()) {
                                $conditionFailed = TRUE;
                            }
                        }
                        if (isset($condition['challenges'])) {
                            if ($condition['challenges'] > ChallengeCompletion::uniqueCompletedAmount()) {
                                $conditionFailed = TRUE;
                            }
                        }
                        if (isset($condition['challenges participations'])) {
                            if ($condition['challenges participations'] > ChallengeCompletion::overallCompletedAmount()) {
                                $conditionFailed = TRUE;
                            }
                        }
                        break;
                    case 'streak':
                        if ($condition > UsersWorkout::currentStreak()) {
                            $conditionFailed = TRUE;
                        }
                        break;
                    case 'best':
                        $startDate = date('Y-m-d', strtotime('first day of previous month')) . ' 00:00:00';
                        $endDate = date('Y-m-d', strtotime('last day of previous month')) . ' 00:00:00';
                        if (isset($condition['challenge'])) {
                            $userId = ChallengeCompletion::bestResultInChallenge($startDate, $endDate, $condition['challenge']);
                        }
                        if (isset($condition['hours'])) {
                            $userId = UsersWorkout::mostTimeWorkouting($startDate, $endDate);
                        }
                        break;
                }
            }
            if (!$conditionFailed) {
                return BadgeStatus::assignBadge($userId, $this->id, $this->recurring ?? NULL, $this->unique ?? NULL);
            }
            else {
                return FALSE;
            }
        } else {
            return ['corrupted_badge' => $this];
        }
    }




}
