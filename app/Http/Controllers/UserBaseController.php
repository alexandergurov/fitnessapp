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
use App\Models\SubscriptionPlan;

class UserBaseController extends VoyagerBaseController {
    protected $searchFields = [
        'email' => [
            'type' => 'text',
            'title' => 'Email',
            'placeholder' => 'Enter email',
            'action' => 'like'
        ],
        'created_at' => [
            'type' => 'datetime',
            'title' => 'Created',
            'action' => '>',
            'classes' => 'form-control datepicker'
        ],
        'role' => [
            'type' => 'select',
            'options' => [
                'Superadmin' => 'Superadmin',
                'Administrator' => 'Administrator',
                'Editor' => 'Editor',
                'Normal User' => 'Normal User'
            ],
            'title' => 'Role',
            'action' => '='
        ],
        'subscription_plan_id' => [
            'type' => 'select',
            'title' => 'Subscription',
            'options' => [],
            'action' => '='
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
        $subscriptionPlans = SubscriptionPlan::all()->toArray();
        $this->searchFields['subscription_plan_id']['options']['NULL'] = 'Not Subscribed';
        $this->searchFields['subscription_plan_id']['options']['canceled'] = 'Subscription canceled';
        foreach ($subscriptionPlans as $subscriptionPlan) {
            $this->searchFields['subscription_plan_id']['options'][$subscriptionPlan['id']] = $subscriptionPlan['plan_name'];
        }
        // GET THE SLUG, ex. 'posts', 'pages', etc.
        $slug = $this->getSlug($request);

        // GET THE DataType based on the slug
        $dataType = Voyager::model('DataType')->where('slug', '=', $slug)->first();

        // Check permission
        $this->authorize('browse', app($dataType->model_name));

        $getter = $dataType->server_side ? 'paginate' : 'get';

        $searchNames = $this->searchFields;

        $orderBy = $request->get('order_by', $dataType->order_column);
        $sortOrder = $request->get('sort_order', $dataType->order_direction);
        $usesSoftDeletes = false;
        $showSoftDeleted = false;

        // Next Get or Paginate the actual content from the MODEL that corresponds to the slug DataType
        if (strlen($dataType->model_name) != 0) {
            $model = app($dataType->model_name);

            if ($dataType->scope && $dataType->scope != '' && method_exists($model, 'scope'.ucfirst($dataType->scope))) {
                $query = $model->{$dataType->scope}();
            } else {
                $query = $model::select('users.*');
            }

            // Use withTrashed() if model uses SoftDeletes and if toggle is selected
            if ($model && in_array(SoftDeletes::class, class_uses_recursive($model)) && Auth::user()->can('delete', app($dataType->model_name))) {
                $usesSoftDeletes = true;

                if ($request->get('showSoftDeleted')) {
                    $showSoftDeleted = true;
                    $query = $query->withTrashed();
                }
            }

            // If a column has a relationship associated with it, we do not want to show that field
            $this->removeRelationshipField($dataType, 'browse');
            $search = [];
            if ($request->get('s')) {
                foreach (array_keys($this->searchFields) as $filter) {
                    if ($filter_value = $request->get($filter)) {
                        $search[$filter] = ['key' => $filter, 'value' => $filter_value];
                        if ($this->searchFields[$filter]['action'] == 'like') {
                            $filter_value = '%' . $filter_value . '%';
                        }
                        if ($filter == 'role') {
                            $query->leftJoin('roles', 'users.role_id', '=', 'roles.id');
                            $query->where('roles.display_name', $this->searchFields[$filter]['action'], $filter_value);
                            if (($orderBy && $orderBy=='created_at') || !$orderBy) {
                                //$query->orderBy($orderBy, $sortOrder);
                            }
                        } elseif ($this->searchFields[$filter]['type'] == 'datetime') {
                            if ($request->get('created_time') == 'before') {
                                $this->searchFields['created_at']['action'] = '<';
                                $query->where('users.' . $filter, $this->searchFields[$filter]['action'], $filter_value);
                            } elseif ($request->get('created_time') == 'between') {
                                $date = date('Y-m-d H:i:s', strtotime($filter_value));
                                $date1 = date('Y-m-d H:i:s', strtotime($request->get($filter . '_1')));
                                $query->where('users.' . $filter, '>', $date);
                                $query->where('users.' . $filter, '<', $date1);
                            } else {
                                $query->where('users.' . $filter, $this->searchFields[$filter]['action'], $filter_value);
                            }
                        } elseif ($filter == 'subscription_plan_id' && $filter_value == 'canceled') {
                            $query->whereNull($filter);
                            $query->leftJoin('payments', 'users.id', '=', 'payments.user_id');
                            $query->groupBy('payments.user_id');
                            $query->whereNotNull('payments.id');
                        } else {
                            $query->where($filter, $this->searchFields[$filter]['action'], $filter_value);
                        }
                    }
                }
            }
            $csvUrl = route('getCsv', array_merge(['slug' => $slug], $request->all()));

            if ($orderBy && in_array($orderBy, $dataType->fields())) {
                $querySortOrder = (!empty($sortOrder)) ? $sortOrder : 'desc';
                $dataTypeContent = call_user_func([
                    $query->orderBy($orderBy, $querySortOrder),
                    $getter,
                ]);
            } elseif ($model->timestamps) {
                $dataTypeContent = call_user_func([$query->latest('users.created_at'), $getter]);
            } else {
                $dataTypeContent = call_user_func([$query->orderBy($model->getKeyName(), 'DESC'), $getter]);
            }

            // Replace relationships' keys for labels and create READ links if a slug is provided.
            $dataTypeContent = $this->resolveRelations($dataTypeContent, $dataType);
        } else {
            // If Model doesn't exist, get data from table name
            $dataTypeContent = call_user_func([DB::table($dataType->name), $getter]);
            $model = false;
        }
        $arrayForLinks = ['s' => $request->get('s') ?? 0, 'created_time' => $request->get('created_time') ?? 'before', 'sort_order'=>$sortOrder];
        if ($orderBy) {
            $arrayForLinks['orderBy'] = $orderBy;
        }
        foreach ($search as $KeyValuePair) {
            $arrayForLinks[$KeyValuePair['key']] = $KeyValuePair['value'];
        }
        $search = (object) $search;

        // Check if BREAD is Translatable
        $isModelTranslatable = is_bread_translatable($model);

        // Eagerload Relations
        $this->eagerLoadRelations($dataTypeContent, $dataType, 'browse', $isModelTranslatable);

        // Check if server side pagination is enabled
        $isServerSide = isset($dataType->server_side) && $dataType->server_side;

        // Check if a default search key is set
        $defaultSearchKey = $dataType->default_search_key ?? null;

        // Actions
        $actions = [];
        if (!empty($dataTypeContent->first())) {
            foreach (Voyager::actions() as $action) {
                $action = new $action($dataType, $dataTypeContent->first());

                if ($action->shouldActionDisplayOnDataType()) {
                    $actions[] = $action;
                }
            }
        }

        // Define showCheckboxColumn
        $showCheckboxColumn = false;
        if (Auth::user()->can('delete', app($dataType->model_name))) {
            $showCheckboxColumn = true;
        } else {
            foreach ($actions as $action) {
                if (method_exists($action, 'massAction')) {
                    $showCheckboxColumn = true;
                }
            }
        }

        // Define orderColumn
        $orderColumn = [];
        if ($orderBy) {
            $index = $dataType->browseRows->where('field', $orderBy)->keys()->first() + ($showCheckboxColumn ? 1 : 0);
            $orderColumn = [[$index, $sortOrder ?? 'desc']];
        }

        $view = 'voyager::bread.browse';

        if (view()->exists("voyager::$slug.browse")) {
            $view = "voyager::$slug.browse";
        }

        return Voyager::view($view, compact(
            'actions',
            'dataType',
            'dataTypeContent',
            'isModelTranslatable',
            'search',
            'orderBy',
            'orderColumn',
            'sortOrder',
            'searchNames',
            'isServerSide',
            'defaultSearchKey',
            'usesSoftDeletes',
            'showSoftDeleted',
            'showCheckboxColumn',
            'csvUrl',
            'arrayForLinks'
        ));
    }

    protected function getData(Request $request, $slug) {


        // GET THE DataType based on the slug
        $dataType = Voyager::model('DataType')->where('slug', '=', $slug)->first();

        // Check permission
        $this->authorize('browse', app($dataType->model_name));

        $getter = 'get';

        $orderBy = $request->get('order_by', $dataType->order_column);
        $sortOrder = $request->get('sort_order', $dataType->order_direction);

        // Next Get or Paginate the actual content from the MODEL that corresponds to the slug DataType
        if (strlen($dataType->model_name) != 0) {
            $model = app($dataType->model_name);

            if ($dataType->scope && $dataType->scope != '' && method_exists($model, 'scope'.ucfirst($dataType->scope))) {
                $query = $model->{$dataType->scope}();
            } else {
                $query = $model::select('*');
            }

            // Use withTrashed() if model uses SoftDeletes and if toggle is selected
            if ($model && in_array(SoftDeletes::class, class_uses_recursive($model)) && Auth::user()->can('delete', app($dataType->model_name))) {
                $usesSoftDeletes = true;

                if ($request->get('showSoftDeleted')) {
                    $showSoftDeleted = true;
                    $query = $query->withTrashed();
                }
            }

            // If a column has a relationship associated with it, we do not want to show that field
            $this->removeRelationshipField($dataType, 'browse');
            $search = [];
            if ($request->get('s')) {
                foreach (array_keys($this->searchFields) as $filter) {
                    if ($filter_value = $request->get($filter)) {
                        $search[$filter] = ['key' => $filter, 'value' => $filter_value];
                        if ($this->searchFields[$filter]['action'] == 'like') {
                            $filter_value = '%' . $filter_value . '%';
                        }if ($this->searchFields[$filter]['type'] == 'datetime') {
                            if ($request->get('created_time') == 'before') {
                                $this->searchFields['created_at']['action'] = '<';
                            }
                            $filter_value = date('Y-m-d H:i:s', strtotime($filter_value));
                            $query->where('users.' . $filter, $this->searchFields[$filter]['action'], $filter_value);
                        } else {
                            $query->where($filter, $this->searchFields[$filter]['action'], $filter_value);
                        }
                    }
                }
            }

            if ($orderBy && in_array($orderBy, $dataType->fields())) {
                $querySortOrder = (!empty($sortOrder)) ? $sortOrder : 'desc';
                $dataTypeContent = call_user_func([
                    $query->orderBy($orderBy, $querySortOrder),
                    $getter,
                ]);
            } elseif ($model->timestamps) {
                $dataTypeContent = call_user_func([$query->latest($model::CREATED_AT), $getter]);
            } else {
                $dataTypeContent = call_user_func([$query->orderBy($model->getKeyName(), 'DESC'), $getter]);
            }

            // Replace relationships' keys for labels and create READ links if a slug is provided.
            $dataTypeContent = $this->resolveRelations($dataTypeContent, $dataType);
        } else {
            // If Model doesn't exist, get data from table name
            $dataTypeContent = call_user_func([DB::table($dataType->name), $getter]);
            $model = false;
        }
        return $dataTypeContent;
    }

    public function exportCsv(Request $request, $slug)
    {
        $dataType = Voyager::model('DataType')->where('slug', '=', $slug)->first();
        $rows = $dataType->browseRows;
        $fileName = $slug.'.csv';
        $items = UserBaseController::getData($request);

        $headers = array(
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        );


        $callback = function() use($items, $rows) {
            $columns = [];
            foreach ($rows as $row) {
                $columns[] = $row->getTranslatedAttribute('display_name');
            }
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns,',','"');

            foreach ($items as $item) {
                $datarow = [];
                foreach ($rows as $row) {
                    if ($row->type == 'timestamp') {
                        $datarow[$row->display_name] = $item->{$row->field}->toDateString();
                    } elseif ($row->type == 'relationship') {
                        $label = $row->getAttribute('details')->label;
                        $datarow[$row->display_name] = '';
                        switch ($row->getAttribute('details')->table) {
                            case 'badges':
                                if ($entity = $item->badges) {
                                    $datarow[$row->display_name] = $entity->{$label};
                                }
                                break;
                        }
                    } else {
                        $datarow[$row->display_name] = $item->{$row->field};
                    }
                }

                fputcsv($file, $datarow,',','"');
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    //***************************************
    //                _____
    //               |  __ \
    //               | |__) |
    //               |  _  /
    //               | | \ \
    //               |_|  \_\
    //
    //  Read an item of our Data Type B(R)EAD
    //
    //****************************************

    public function show(Request $request, $id)
    {
        $slug = $this->getSlug($request);

        $dataType = Voyager::model('DataType')->where('slug', '=', $slug)->first();

        $isSoftDeleted = false;

        if (strlen($dataType->model_name) != 0) {
            $model = app($dataType->model_name);

            // Use withTrashed() if model uses SoftDeletes and if toggle is selected
            if ($model && in_array(SoftDeletes::class, class_uses_recursive($model))) {
                $model = $model->withTrashed();
            }
            if ($dataType->scope && $dataType->scope != '' && method_exists($model, 'scope'.ucfirst($dataType->scope))) {
                $model = $model->{$dataType->scope}();
            }
            $dataTypeContent = call_user_func([$model, 'findOrFail'], $id);
            if ($dataTypeContent->deleted_at) {
                $isSoftDeleted = true;
            }
        } else {
            // If Model doest exist, get data from table name
            $dataTypeContent = DB::table($dataType->name)->where('id', $id)->first();
        }

        // Replace relationships' keys for labels and create READ links if a slug is provided.
        $dataTypeContent = $this->resolveRelations($dataTypeContent, $dataType, true);

        // If a column has a relationship associated with it, we do not want to show that field
        $this->removeRelationshipField($dataType, 'read');

        // Check permission
        $this->authorize('read', $dataTypeContent);

        // Check if BREAD is Translatable
        $isModelTranslatable = is_bread_translatable($dataTypeContent);

        // Eagerload Relations
        $this->eagerLoadRelations($dataTypeContent, $dataType, 'read', $isModelTranslatable);

        $view = 'voyager::bread.read';

        if (view()->exists("voyager::$slug.read")) {
            $view = "voyager::$slug.read";
        }

        $longTexts = [];
        foreach ($dataType->readRows as $row) {
            if ($row->type == 'rich_text_box') {
                if (in_array($row->field, $dataTypeContent->getTranslatableAttributes())) {
                    $translations = $dataTypeContent->getTranslationsOf($row->field);
                    $longTexts[$row->field] = '';
                    foreach ($translations as $lg => $translation) {
                        $longTexts[$row->field] .= '<h4>' . $lg . '</h4>';
                        $longTexts[$row->field] .= '<div>' . $translation . '</div>';
                    }
                } else {
                    $longTexts[$row->field] = $dataType->{$row->field};
                }
            }
        }

        return Voyager::view($view, compact('dataType', 'dataTypeContent', 'isModelTranslatable', 'isSoftDeleted', 'longTexts'));
    }
}
