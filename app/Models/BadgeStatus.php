<?php

namespace App\Models;


class BadgeStatus extends CustomModel
{
    protected $guarded = ['id'];

    static function assignBadge($userId, $badgeId, $recurring = FALSE, $unique = FALSE) {
        if (is_object($userId)) {
            $userId = $userId->id;
        }
        if (is_object($badgeId)) {
            $badgeId = $badgeId->id;
        }
        if ($unique) {
            $badgeStatus = BadgeStatus::where([['badge_id', $badgeId]])->orderBy('created_at', 'desc')->get()->first();
            if ($badgeStatus && strtotime($badgeStatus->created_at) >= strtotime('first day of previous month')) {
                return NULL;
            }
        }
        $badgeStatus = BadgeStatus::where([['user_id', $userId],['badge_id', $badgeId]])->orderBy('created_at', 'desc')->get()->first();
        if (!$badgeStatus || (!$recurring && strtotime($badgeStatus->created_at) <= strtotime('first day of previous month'))) {
            $data = [
                'user_id' => $userId,
                'badge_id' => $badgeId,
            ];
            $badgeStatus = BadgeStatus::create($data);
        }
        return $badgeStatus;
    }
}
