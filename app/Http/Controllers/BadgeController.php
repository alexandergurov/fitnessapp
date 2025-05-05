<?php

namespace App\Http\Controllers;

use App\Models\Badge;
use App\Models\BadgeStatus;
use App\Models\UsersWorkout;
use Illuminate\Http\Request;

class BadgeController extends Controller {
    public function getOne($id) {
        $badge = Badge::getOne($id);
        if (empty($badge)) {
            return response()->json('No badge with such id.', 200);
        }
        return response()->json($badge, 200);
    }

    public function usersBadges() {
        $badges = Badge::usersBadgesStatuses();
        if (isset($badges[0])) {
            return response()->json($badges, 200);
        }
        else {
            return response()->json('No badges available.', 200);
        }
    }

    public function triggerTriggers(Request $request) {
        if (isset($request->trigger)) {
            $badgesAssigned = $this::processTriggers($request->trigger);
            return response()->json($badgesAssigned, 200);
        }
        else {
            return response()->json('Trigger type is required.', 200);
        }
    }

    static function processTriggers($trigger) {
        $relevantBadges = Badge::where('triggers', 'like', '%' . $trigger . '%')
                               ->get();
        $badgesAssigned = [];
        foreach ($relevantBadges as $relevantBadge) {
            $badgesAssigned[] = $relevantBadge->checkConditionsAndAssign();
        }
        return $badgesAssigned;
    }
}

