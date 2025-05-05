<?php

namespace App\Actions;

use TCG\Voyager\Actions\AbstractAction;

class OpenLeaderboard extends AbstractAction
{
    public function getTitle()
    {
        return 'Open Leaderboard';
    }

    public function getIcon()
    {
        return 'voyager-medal-rank-star';
    }

    public function getAttributes()
    {
        return [
            'class' => 'btn btn-sm btn-primary pull-left',
        ];
    }

    public function shouldActionDisplayOnDataType()
    {
        // show or hide the action button, in this case will show for posts model
        return $this->dataType->slug == 'challenges';
    }

    public function getDefaultRoute()
    {
        return route('challenge.leaderboard', array("id"=>$this->data->{$this->data->getKeyName()}));
    }
}
