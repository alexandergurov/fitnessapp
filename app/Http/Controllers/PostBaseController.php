<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use TCG\Voyager\Database\Schema\SchemaManager;
use TCG\Voyager\Events\BreadDataAdded;
use TCG\Voyager\Events\BreadDataDeleted;
use TCG\Voyager\Events\BreadDataRestored;
use TCG\Voyager\Events\BreadDataUpdated;
use TCG\Voyager\Events\BreadImagesDeleted;
use TCG\Voyager\Facades\Voyager;
use TCG\Voyager\Http\Controllers\Traits\BreadRelationshipParser;
use TCG\Voyager\Http\Controllers\VoyagerBaseController;
use App\Models\Instructor;

class PostBaseController extends ContentBaseController {
    protected $searchFields = [
        'created_at' => [
            'type' => 'datetime',
            'title' => 'Created',
            'action' => '>',
            'classes' => 'form-control datepicker'
        ],
        'title' => [
            'type' => 'textarea',
            'title' => 'Title',
            'placeholder' => 'Contains:',
            'action' => 'like'
        ],
        'instructor_id' => [
            'type' => 'select',
            'title' => 'Trainer',
            'options' => [],
            'action' => '='
        ],
        'name' => [
            'type' => 'textarea',
            'title' => 'Name',
            'placeholder' => 'Contains:',
            'action' => 'like'
        ]
    ];



    //***************************************
    //               ____
    //              |  _ \
    //              | |_) |
    //              |  _ <
    //              | |_) |
    //              |____/
    //
    //      Browse our Data Type (B)READ
    //
    //****************************************

    public function index(Request $request)
    {
        $instructors = Instructor::all()->toArray();
        foreach ($instructors as $instructor) {
            $this->searchFields['instructor_id']['options'][$instructor['id']] = $instructor['full_name'];
        }
        return parent::index($request);
    }

}
