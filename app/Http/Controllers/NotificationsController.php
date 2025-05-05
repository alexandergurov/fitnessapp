<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\User;
use App\Mail\NotificationMail;
use Mail;
use mysql_xdevapi\Exception;

class NotificationsController extends Controller {
    static function scheduleNotification($uid, $type, $send_at = 'now', $data = NULL) {
        if ('now' != $send_at) {
            Notification::create([
                'user_id' => $uid,
                'notification_type' => $type,
                'send_at' => $send_at,
                'variables' => json_encode($data) ?? NULL,
            ]);
        }
        else {
            $notification = new Notification();
            $notification->notification_type = $type;
            $notification->variables = $data;
            if ($uid) {
                $user = User::where('id', $uid)->get()->first();
                NotificationsController::sendNotification($notification, $user);
            }
            else {
                $users = User::whereNotNull('connected_devices')->get()->all();
                foreach ($users as $user) {
                    NotificationsController::sendNotification($notification, $user);
                }
            }
        }
    }

    static function sendNotifications($triggerType = 'morning') {
        $notifications = Notification::relevantNotifications($triggerType);
        foreach ($notifications as $notification) {
            if ($notification->user_id) {
                $user = User::where('id', $notification->user_id)
                            ->get()
                            ->first();
                if ($user) {
                    NotificationsController::sendNotification($notification, $user);
                }
            }
            else {
                $users = User::whereNotNull('connected_devices')->get()->all();
                foreach ($users as $user) {
                    NotificationsController::sendNotification($notification, $user);
                }
            }
            $notification->delete();
        }
    }

    static function sendNotification($notification, $user) {
        if (!$user->name) {
            $user->name = $user->email;
        }
        if (!is_array($notification->variables)) {
            $notification->variables = json_decode($notification->variables, TRUE);
        }
        if (isset($notification->mail_notifications[$notification->notification_type])) {
            $title = $notification->mail_notifications[$notification->notification_type]['default']['title'];
            $header = $notification->mail_notifications[$notification->notification_type]['default']['header'];
            $header = str_replace('%user', $user->name, $header);
            $message = $notification->mail_notifications[$notification->notification_type]['default']['message'];
            if (filter_var($user->email, FILTER_VALIDATE_EMAIL)) {
                try {
                    Mail::to($user->email)
                        ->send(new NotificationMail($title, $header, $message, $user->email));
                } catch (Exception $e) {

                }
            }
        }
        if (isset($notification->app_notifications[$notification->notification_type])) {
            $message = $notification->app_notifications[$notification->notification_type]['message'];
            $message = str_replace('%user', $user->name, $message);
            if (isset($notification->variables[0])) {
                $message = str_replace('%1', $notification->variables[0], $message);
            }
            $title = $notification->app_notifications[$notification->notification_type]['title'];
            $devices = json_decode($user->connected_devices, 'true');
            if ($devices) {
                foreach ($devices as $device) {
                    $res = User::send_notification_FCM($device, $title, $message, $user->id, $notification->notification_type);
                    if($res == 1){
                        // success code
                    }else{
                        // fail code
                    }
                }
            }
        }
    }
}
