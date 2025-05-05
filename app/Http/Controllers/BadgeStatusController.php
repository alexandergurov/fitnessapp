<?php

namespace App\Http\Controllers;

use App\Models\Badge;
use App\Models\BadgeStatus;

class BadgeStatusController extends Controller {
    public function getOne($id) {
        $badge = Badge::where('id', $id)->get()->first();
        $badge->loadTranslated();
        if (empty($badge)) {
            return response()->json('No badge with such id.', 200);
        }
        return response()->json($badge, 200);
    }

    public function getList() {
        $badges = Badge::all()->sortByDesc('created_at');
        if (isset($badges[0])) {
            foreach ($badges as &$badge) {
                $badge->loadTranslated();
            }
            return response()->json($badges, 200);
        }
        else {
            return response()->json('No badges available.', 200);
        }
    }
}

