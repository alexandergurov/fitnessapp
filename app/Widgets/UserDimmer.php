<?php

namespace App\Widgets;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use TCG\Voyager\Facades\Voyager;
use Arrilot\Widgets\AbstractWidget;
use App\Models\User;
use App\Models\Payment;

class UserDimmer extends AbstractWidget
{
    /**
     * The configuration array.
     *
     * @var array
     */
    protected $config = [];

    /**
     * Treat this method as a controller action.
     * Return view() or other content to display.
     */
    public function run()
    {
        $signups = User::where('role_id', 2)->get()->count();
        $activeSubscriptions = User::whereNotNull('latest_payment_id')->get()->count();
        $query = User::select('users.*');
        $query->leftJoin('payments', 'users.id', '=', 'payments.user_id');
        //$query->whereNull('users.subscription_plan_id');
        $query->whereNotNull('payments.id');
        $query->groupBy('users.id');
        $cancelledSubscriptions = $query->get()->count() - $activeSubscriptions;

        return view('voyager::dimmer', array_merge($this->config, [
            'icon'   => 'voyager-group',
            'title'  => "{$signups} SignUps",
            'text'   => $activeSubscriptions . ' Active subscriptions <br>' . $cancelledSubscriptions . ' Cancelled Subscriptions' ,
            'button' => [
                'text' => __('voyager::dimmer.user_link_text'),
                'link' => route('voyager.users.index'),
            ],
            'image' => voyager_asset('images/widget-backgrounds/01.jpg'),
        ]));
    }

    /**
     * Determine if the widget should be displayed.
     *
     * @return bool
     */
    public function shouldBeDisplayed()
    {
        return Auth::user()->can('browse', Voyager::model('User'));
    }
}
