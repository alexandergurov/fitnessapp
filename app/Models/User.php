<?php

namespace App\Models;

use App\Http\Controllers\NotificationsController;
use App\Mail\PasswordResetMail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\LinkedSocialAccount;
use TCG\Voyager\Traits\VoyagerUser;
use DB;
use Str;
use Mail;

class User extends \TCG\Voyager\Models\User {
    use HasApiTokens, HasFactory, Notifiable;
    use HasThumbnails {
        save as thumbnailSave;
    }

    protected $table = 'users';
    protected $guarded = ['id'];
    protected $unique = ['phone', 'nickname', 'email'];
    protected $required = ['phone', 'nickname', 'email'];
    protected $thumbnailsSizes = ['90x90' => [90, 90], '180x180' => [180, 180], '270x270' => [270, 270], ];
    protected $hidden = ['tokens', 'password', 'connected_devices', 'thumbnails'];

    public function findForPassport($username) {
        return $this->whereEmail($username)->first();
    }

    public function __construct(array $attributes = []) {
        parent::__construct($attributes);
    }

    public function save(array $options = []) {
        if ($this->first_name && $this->last_name) {
            $this->name = $this->first_name . ' ' . $this->last_name;
        }
        $this->thumbnailSave(array_merge($options,['thumbnails_only' => TRUE]));
        parent::save($options);
    }

    public function validateUpdate($data, $id = NULL) {
        foreach ($data as $fieldName => $fieldValue) {
            if (!Schema::hasColumn('users', $fieldName)) {
                return [
                    'code' => 400,
                    'message' => 'Invalid field name:' . $fieldName
                ];
            }
            if (in_array($fieldName, $this->unique)) {
                $existingUser = User::where($fieldName, $fieldValue)->get();
                if (isset($existingUser[0]) && $existingUser[0]->id != $id) {
                    return [
                        'code' => 400,
                        'message' => 'Incorrect field value for ' . $fieldName
                    ];
                }
            }
        }
        return ['code' => 200, 'message' => 'User updated'];
    }

    /**
     * @return User
     */
    static function UserLoad($user, $myself = FALSE) {
        if (!is_object($user)) {
            $user = User::where('id', $user)->get()->first();
        }
        if ($user) {
            $user->generateThumbnails('avatar');
            if (!empty($user->badges)) {
                $badges = $user->badges()->get();
                $user->badges = $badges;
            }
            if ($user->latest_payment_id) {
                $user->subscribed = 1;
                $payment = Payment::where('id', $user->latest_payment_id)->get()->first();
                if (isset($payment->active_until)) {
                    $user->subscribed_until = $payment->active_until;
                    $user->subscription_service = $payment->service;
                    $subscriptionPlan = SubscriptionPlan::where('machine_name', $payment->plan_name)->get()->first();
                    if ($subscriptionPlan) {
                        $user->subscription_plan_id = $subscriptionPlan->machine_name;
                        $user->subscription_plan_name = $subscriptionPlan->plan_name;
                    } else {
                        $user->subscription_plan_id = $payment->plan_name;
                        $user->subscription_plan_name = $payment->plan_name;
                    }
                }
            } else {
                $user->subscribed = 0;
            }
            if (!$myself) {
                $user->email = NULL;
            }
        }
        return $user;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\belongsToMany
     */
    public function badges() {
        return $this->belongsToMany('App\Models\Badge');
    }

    public function linkedSocialAccounts()
    {
        return $this->hasMany(LinkedSocialAccount::class);
    }

    public function notifyUser($title, $message, $type){
        $devices = json_decode($this->connected_devices, 'true');
        foreach ($devices as $device) {
            $res = User::send_notification_FCM($device, $title, $message, $this->id, $type);
            if($res == 1){
                // success code
            }else{
                // fail code
            }
        }
    }

    static function massNotify($title, $message) {
        $users = User::whereNotNull('connected_devices')->get()->all();
        foreach ($users as $user) {
            $user->notifyUser($title, $message,'test_notification');
        }
    }

    static function send_notification_FCM($device_id, $title, $message, $id, $type) {

        $accesstoken = config('fcm.key');
        $URL = 'https://fcm.googleapis.com/fcm/send';
        $post_data = '{
            "to" : "' . $device_id . '",
            "data" : {
              "body" : "",
              "title" : "' . $title . '",
              "type" : "' . $type . '",
              "id" : "' . $id . '",
              "message" : "' . $message . '",
            },
            "notification" : {
                 "body" : "' . $message . '",
                 "title" : "' . $title . '",
                  "type" : "' . $type . '",
                 "id" : "' . $id . '",
                 "message" : "' . $message . '",
                "icon" : "new",
                "sound" : "default"
                },

          }';

        $crl = curl_init();

        $headr = array();
        $headr[] = 'Content-type: application/json';
        $headr[] = 'Authorization: ' . 'key='.$accesstoken;
        curl_setopt($crl, CURLOPT_SSL_VERIFYPEER, false);

        curl_setopt($crl, CURLOPT_URL, $URL);
        curl_setopt($crl, CURLOPT_HTTPHEADER, $headr);

        curl_setopt($crl, CURLOPT_POST, true);
        curl_setopt($crl, CURLOPT_POSTFIELDS, $post_data);
        curl_setopt($crl, CURLOPT_RETURNTRANSFER, true);

        $rest = curl_exec($crl);
        if ($rest === false) {
            // throw new Exception('Curl error: ' . curl_error($crl));
            //print_r('Curl error: ' . curl_error($crl));
            $result_noti = 0;
        } else {

            $result_noti = 1;
        }

        curl_close($crl);
        //print_r($result_noti);die;
        return $result_noti;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\belongsToMany
     */
    public function favorites()
    {
        return $this->belongsToMany('App\Models\Workouts');
    }

    public function cancelSubscription() {
        $this->update(['latest_payment_id' => NULL, 'subscription_plan_id' => NULL]);
        NotificationsController::scheduleNotification($this->id, 'subscription_finished');
    }

    public function attachSubscription($paymentId, $subscriptionPlanId) {
        if ($this->latest_payment_id != $paymentId) {
            $this->update(['latest_payment_id' => $paymentId, 'subscription_plan_id' => $subscriptionPlanId]);
            NotificationsController::scheduleNotification($this->id, 'subscription_prolonged');
        }
    }

    public function passwordChange($password)
    {
        $this->update(array_merge(
            ['password' => bcrypt($password)]
        ));
    }

    static function passwordReset($email)
    {
        $token = Str::random(8);
        $user = User::where('email', $email)->get()->first();
        if ($user) {
            DB::table('reset_codes')->insert([
                'created_at' => now(),
                'reset_code' => $token,
                'user_id' => $user->id,
            ]);
            if ($user) {
                Mail::to($user['email'])->send(new PasswordResetMail($token,$user->name,$user['email']));
            }
        }
    }
    static function validateToken($email, $token)
    {
        $user = User::where('email', $email)->get()->first();
        if ($user) {
            $token_entry = DB::table('reset_codes')
              ->select('reset_code')
              ->where('user_id', $user->id)
              ->where('created_at', '>',
                  date('Y-m-d H:i:s', strtotime('-15 minutes')))->get()->last();
            if ($token_entry && $token == $token_entry->reset_code) {
                return TRUE;
            }
        }
        return FALSE;
    }
    static function setPassword($email, $password)
    {
        $user = User::where('email', $email)->get()->first();
        if ($user) {
            $user->update(['password' => bcrypt($password)]);
        }
    }
}
