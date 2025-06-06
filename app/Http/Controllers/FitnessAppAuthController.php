<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use TCG\Voyager\Facades\Voyager;
use TCG\Voyager\Http\Controllers\VoyagerAuthController;

class FinessAppAuthController extends VoyagerAuthController {
    public function postLogin(Request $request) {
        try {

            $this->validateLogin($request);

            // If the class is using the ThrottlesLogins trait, we can automatically throttle
            // the login attempts for this application. We'll key this by the username and
            // the IP address of the client making these requests into this application.
            if ($this->hasTooManyLoginAttempts($request)) {
                $this->fireLockoutEvent($request);

                return $this->sendLockoutResponse($request);
            }

            $credentials = $this->credentials($request);

            if ($this->guard()->attempt($credentials, $request->has('remember'))) {
                return $this->sendLoginResponse($request);
            }

            // If the login attempt was unsuccessful we will increment the number of attempts
            // to login and redirect the user back to the login form. Of course, when this
            // user surpasses their maximum number of attempts they will get locked out.
            $this->incrementLoginAttempts($request);

            $this->sendFailedLoginResponse($request);
        }
        catch (ValidationException $e) {
            $message =  $e->getMessage();
        }
        return Voyager::view('voyager::login', ['message' => $message]);
    }

}
