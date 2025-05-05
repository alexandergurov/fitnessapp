<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Mail;

class UserController extends Controller
{
    public function getOne($id, Request $request) {
        if ($id != auth()->guard('api')->id()) {
            return response()->json('Access denied.', 403);
        }
        $user = User::UserLoad($id);

        if (empty($user)) {
            return response()->json('No user with such id.', 200);
        }
        return response()->json($user, 200);
    }

    public function getCurrent( Request $request) {
        $id = auth()->guard('api')->id();
        $user = User::UserLoad($id, TRUE);
        return response()->json($user, 200);
    }

    public function getList() {
        $user = auth()->guard('api')->user();
        $language = $user->language ?? 'enGB';
        $users = User::all()
                     ->sortByDesc('created_at');
        if (!empty($users)) {
            return response()->json($users, 200);
        } else {
            return response()->json('No users available.', 204);
        }
    }

    public function update(Request $request, $id) {
        if ($id != auth()->guard('api')->id()) {
            return response()->json('Access denied.', 403);
        }
        $user = auth()->guard('api')->user();
        if (!$user) {
            return response()->json('No user with such id.', 400);
        }
        $data = $request->all();
        if ($request->file('avatar') && $request->file('avatar')->isValid()) {
            $path = $request->avatar->store('users','public');
            $data['avatar'] = $path;
            $data['thumbnails'] = NULL;
        }
        $response = $user->validateUpdate($data, $id);
        if ($response['code']==200) {
            $user->update($data);
            $user->save();
        }
        return response()->json($response['message'], $response['code']);
    }

    public function addDevice(Request $request, $id) {
        $user = auth()->guard('api')->user();
        if ($id != $user->id) {
            return response()->json('Access denied.', 403);
        }
        if (!$user) {
            return response()->json('No user with such id.', 400);
        }
        $device_id = $request->device_id;
        $device_token = $request->device_token;
        $value = json_decode($user->connected_devices, 'true');
        $value[$device_id] = $device_token;
        $data = ['connected_devices' => $value];
        $response = $user->validateUpdate($data, $id);
        if ($response['code']==200) {
            $user->update($data);
            $user->save();
        }
        return response()->json($response['message'], $response['code']);
    }

    public function passwordChange(Request $request)
    {
        $user = auth()->guard('api')->user();
        if (!$user) {
            return response()->json('Access denied.', 403);
        }
        if (!$request->old_password || !$request->new_password) {
            return response()->json('Both new_password and old_password are required', 400);
        }
        if (!password_verify($request->old_password, $user->password)) {
            return response()->json('Incorrect password provided', 400);
        }
        $user->passwordChange($request->new_password);
        return response()->json('Password updated', 200);
    }

    public function passwordReset(Request $request)
    {
        User::passwordReset($request->email);
    }

    public function validateToken(Request $request)
    {
        if (!$request->email || !$request->token) {
            return response()->json('Both token and email are required', 400);
        }
        if (User::validateToken($request->email, $request->token)) {
            return response()->json(TRUE, 200);
        } else {
            return response()->json('Invalid code.', 400);
        }
    }

    public function setPassword(Request $request)
    {
        if (!$request->email || !$request->token || !$request->password) {
            return response()->json('Both token and email are required', 400);
        }
        if (User::validateToken($request->email, $request->token)) {
            User::setPassword($request->email, $request->password);
            return response()->json('Password successfully set, now you need to login with it.', 200);
        }
        return response()->json('Incorrect token.', 400);
    }

    public function welcome(Request $request)
    {
        $uid = auth()->guard('api')->id();
        if ($uid) {
            NotificationsController::scheduleNotification($uid, 'welcome');
        }
    }
}
