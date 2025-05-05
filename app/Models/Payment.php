<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use \Imdhemy\Purchases\Facades\Subscription;
use \Imdhemy\GooglePlay\Subscriptions\SubscriptionPurchase;



class Payment extends CustomModel
{
    protected $guarded = ['id'];

    static function saveReceipt($data) {
        $payment = Payment::create($data);
        $payment->update(['amount_paid'=>$payment->getPrice()]);
        return $payment;
    }

    public function validateUpdate() {
        $user = User::where('id', $this->user_id)->get()->first();
        if (!$user) {
            return FALSE;
        }
        $paymentId = $this->id;
        $status = FALSE;
        switch ($this->service) {
            case 'google':
                putenv("GOOGLE_APPLICATION_CREDENTIALS=".config('purchase.google_play_application_credentials'));
                $itemId = $this->plan_name;
                $token = $this->receipt;
                Subscription::googlePlay()->id($itemId)->token($token)->acknowledge();
                $serviceResponse = Subscription::googlePlay()->id($itemId)->token($token)->get();
                $expires = $serviceResponse->getExpiryTime()->getCarbon();
                if ($this->active_until && strtotime($this->active_until)) {
                    $activeUntil = Carbon::createFromTimeString($this->active_until);
                    if ($expires->gt(Carbon::now())) {
                        $status = TRUE;
                        if ($expires->diffInSeconds($activeUntil) > 0) {
                            $paymentId = $this->renewSubscription($expires->toDateTimeString());
                        }
                    }
                } else {
                    if ($expires->gt(Carbon::now())) {
                        $paymentId = $this->id;
                        $this->update(['active_until' => $expires->toDateTimeString()]);
                        $status = TRUE;
                    }
                }
                break;
            case 'apple':
                $token = $this->receipt;
                $serviceResponse = Subscription::appStore()->receiptData($token)->verifyReceipt();
                $latestReceipts = $serviceResponse->getLatestReceiptInfo();
                if (is_array($latestReceipts)) {
                    $expires = $latestReceipts[0]->getExpiresDate()->getCarbon();
                    if ($this->active_until && strtotime($this->active_until)) {
                        $activeUntil = Carbon::createFromTimeString($this->active_until);
                        if ($expires->gt(Carbon::now())) {
                            $status = TRUE;
                            if ($expires->diffInSeconds($activeUntil) > 0) {
                                $paymentId = $this->renewSubscription($expires->toDateTimeString());
                            }
                        }
                    } else {
                        if ($expires->gt(Carbon::now())) {
                            $paymentId = $this->id;
                            $this->update(['active_until' => $expires->toDateTimeString()]);
                            $status = TRUE;
                        }
                    }
                }
                break;
        }
        if ($status) {
            if (!$subscription_plan = SubscriptionPlan::where('machine_name',$this->plan_name)->get()->first()) {
                $subscription_plan = SubscriptionPlan::create([
                    'machine_name' => $this->plan_name,
                    'plan_name' => $this->plan_name,
                    'service' => $this->service,
                ]);
            }
            $user->attachSubscription($paymentId, $subscription_plan->id);
        } elseif ($user->latest_payment_id == $this->id && (!$serviceResponse || !$status)) {
            $user->cancelSubscription();
        }
        return $this->id;
    }

    public function renewSubscription($expires) {
        $newPayment = Payment::saveReceipt([
            'receipt' => $this->receipt,
            'plan_name' => $this->plan_name,
            'service' => $this->service,
            'amount_paid' => $this->getPrice(),
            'user_id' => $this->user_id,
            'active_until' => $expires,
        ]);
        return $newPayment->id;
    }

    public function getPrice() {
        if ($this->amount_paid && !strpos($this->amount_paid, 'subscription')) {
            return $this->amount_paid;
        } else {
            if ($subscription_plan = SubscriptionPlan::where('machine_name',$this->plan_name)->get()->first()) {
                if ($subscription_plan->price) {
                    return $subscription_plan->price;
                }
            }
        }
        return 'Set value in subscription plan details';
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\belongsTo
     */
    public function user()
    {
        return $this->belongsTo('App\Models\User');
    }

}
