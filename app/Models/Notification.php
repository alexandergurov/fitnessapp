<?php

namespace App\Models;

class Notification extends CustomModel
{
    protected $guarded = ['id'];
    protected $table = 'scheduled_notifications';
    public $mail_notifications = [
        'welcome' => [
            'default' => [
                'title' => 'Welcome to the FinessApp Fitness App',
                'header' => 'Hi %user!',
                'message' => '<spanstyle="font-size:16px;background-color:transparent">Welcome to the FinessApp Fitness &amp; Yoga app community. We hope you love our Fitness and Yoga workouts which will help you to stay fit and healthy.<br>
<br>
Don’t forget to use our Challenges and get Badges as you advance from Bronze to Silver and ultimately to Gold.<br>
<br>
On a regular basis we add new Fitness and Yoga workouts.<br>
<br>
If you wish you can compete with other participants and share your results in our Leaderboard.<br>
<br>
You might find it worthwhile to read our FAQ’s under Profile&gt;Settings&gt;Help - there are alot of valuable tips here to ensure you get the most from the FinessApp app.<br>
<br>
Enjoy!</span>'
            ]
        ],
        'trial_ends' => [
            'default' => [
                'title' => 'FinessApp App trial expires soon.',
                'header' => 'Hi, %user!',
                'message' => 'Your trial is ending in two days. Upon expiration
                you would have no access into the App, and have to subscribe to
                resume using it. So please consider subscribing to be able to use it further.'
            ]
        ],
        'subscription_finished' => [
            'default' => [
                'title' => 'FinessApp App subscription expired.',
                'header' => 'Hi, %user!',
                'message' => '<div>&nbsp;</div>

<div style="line-height:20px"><span style="font-size:16px;background-color:transparent">It looks like there is an issue with your FinessApp App Subcription renewal. </span></div>

<div style="line-height:20px">&nbsp;</div>

<div style="line-height:20px"><span style="font-size:16px;background-color:transparent"><strong>For iPhone Users</strong><br>
Please go to your Apple Settings&gt;Apple ID&gt;Payments screen and check that your credit card is valid and active. </span><br>
&nbsp;</div>

<div style="line-height:20px"><span style="font-size:16px;background-color:transparent"><strong>For Android Users</strong><br>
Please go to your Settings&gt;Google Settings&gt;Manage Your Google Account&gt;Payments and Subcriptions and check that your credit card is valid and active.</span></div>

<div style="line-height:20px">&nbsp;</div>

<div style="line-height:20px"><span style="font-size:16px">Once you have confirmed that above, try and activate your subscription by opening the FinessApp app.</span></div>

<div style="line-height:20px">&nbsp;</div>

<div style="line-height:20px"><span style="font-size:16px">Thanks in advance.</span></div>'
            ]
        ],
        'subscription_prolonged' => [
            'default' => [
                'title' => 'FinessApp App subscription renewed.',
                'header' => 'Hi, %user!',
                'message' => '<div>&nbsp;</div>

<div style="line-height:20px"><span style="font-size:16px;background-color:transparent">Thanks for renewing your subscription.&nbsp;<br>
<br>
We value your membership.
<br>
<br>
Enjoy!</span></div>

<div style="line-height:20px"><span style="font-size:16px">&nbsp;</span></div>

<div style="line-height:20px"><span style="font-size:16px">The FinessApp Team.</span></div>

<div style="line-height:20px"><span style="font-size:16px">Transform your body with FinessApp.</span></div>

<div style="line-height:20px"><span style="font-size:16px">Anyone. Anywhere. Anytime.</span></div>

<div><br>
&nbsp;</div>'
            ]
        ],
    ];

    public $app_notifications = [
        'new_workout' => ['title' => 'New workout available!', 'message' => 'Hi %user Great news - we\'ve added a new workout . Ideal for everyone the %1 is ready for you to try out now.'],
        'new_trainer' => ['title' => 'New trainer added!', 'message' => 'Hi %user Great news - we\'ve added a new Trainer. Check our their profile here.'],
        'new_article' => ['title' => 'New article available!', 'message' => 'Hi %user We\'ve added a new article called %1. If you feel up to it have a read, we\'re sure you\'ll enjoy it.'],
        'challenge_top' => ['title' => 'You\'re number one!', 'message' => 'Hi %user Great news - you\'re top of the %1 Leaderboard. Try beating your own record.'],
        'challenge_top_lost' => ['title' => 'Top position had been lost.', 'message' => 'Hi %user Finally you\'ve been overtaken and are no longer top of the %1 Leaderboard. Have another go at it.'],
        'inactivity' => ['title' => 'We miss you.', 'message' => 'Hi %user We\'ve missed you. If you\'re ready, we\'d love to help keep you moving. Try a new workout or just continue where you left off.'],
        'subscription_prolonged' => ['title' => 'Subscription renewed.', 'message' => 'Hi %user Thanks - your subscription has been renewed. We value your membership - enjoy.'],
        'subscription_finished' => ['title' => 'Subscription ended.', 'message' => 'Hi %user It looks like there is an issue with your renewal. Please go to your "link to Apple Subscription screen or Google Subscription Screen to find out how to re-activate your subscription.'],
        'trial_ends' => ['title' => 'Trial ends soon.', 'message' => 'Hi %user Your trial is expiring in about two days. Please consider subscribing'],
    ];

    static function relevantNotifications($triggerType) {
        return Notification::where([['created_at', '<', date('Y-m-d H:i:s', time())],['send_at', $triggerType]])->get()->all();
    }
}
