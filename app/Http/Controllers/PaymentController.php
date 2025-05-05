<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Payment;
use Illuminate\Http\Request;
use Validator;

class PaymentController extends Controller
{
    public function saveReceipt(Request $request) {
        if (!($user = auth()->guard('api')->user())) {
            return response()->json('Unauthorised.', 401);
        }
        $data = $request->all();
        $data['user_id'] = $user->id . '';
        $savedPayment = Payment::where([['receipt', $data['receipt']],['user_id', $user->id]])->get()->first();
        if (!$savedPayment) {
            $validator = Validator::make($data, [
                'receipt' => 'required|string|unique:payments',
                'plan_name' => 'required|string',
                'service' => 'required|string',
                'amount_paid' => 'string',
                'user_id' => 'required|string',
                'active_until' => 'string',
            ]);
            if ($validator->fails()) {
                return response()->json($validator->errors(), 422);
            }
            $savedPayment = Payment::saveReceipt($validator->validated());
        }
        $savedPayment->validateUpdate();
        return response()->json($savedPayment, 200);
    }

    static function massValidate() {
        $subscribedUsers = User::whereNotNull('latest_payment_id')->get()->all();
        $activePayments = [];
        foreach ($subscribedUsers as $subscribedUser) {
            $payment = Payment::where([
            ['id', $subscribedUser->latest_payment_id],
            ['active_until', '<', date('Y-m-d H:i:s', strtotime('-5 minutes'))]])
                                                                         ->get()->first();
            if ($payment) {
                $activePayments[] = $payment->validateUpdate();
            }
        }
    }
}
