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

class RoutinesBaseController extends ContentBaseController {
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
        // GET THE SLUG, ex. 'posts', 'pages', etc.
        $slug = $this->getSlug($request);

        // GET THE DataType based on the slug
        $dataType = Voyager::model('DataType')->where('slug', '=', $slug)->first();

        // Check permission
        $this->authorize('browse', app($dataType->model_name));

        $getter = $dataType->server_side ? 'paginate' : 'get';


        $orderBy = $request->get('order_by', $dataType->order_column);
        $sortOrder = $request->get('sort_order', $dataType->order_direction);
        $usesSoftDeletes = false;
        $showSoftDeleted = false;

        // Next Get or Paginate the actual content from the MODEL that corresponds to the slug DataType
        if (strlen($dataType->model_name) != 0) {
            $model = app($dataType->model_name);
            $table = $model->getTable();
            $columns = $model->getConnection()->getSchemaBuilder()->getColumnListing($table);
            $searchNames = $this->searchFields;
            foreach ($searchNames as $key => $row) {
                if (!in_array($key, $columns)) {
                    unset($searchNames[$key]);
                }
            }

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
                foreach (array_keys($searchNames) as $filter) {
                    if ($filter_value = $request->get($filter)) {
                        $search[$filter] = ['key' => $filter, 'value' => $filter_value];
                        if ($this->searchFields[$filter]['action'] == 'like') {
                            $filter_value = '%' . $filter_value . '%';
                        }
                        if ($this->searchFields[$filter]['type'] == 'datetime') {
                            $date = date('Y-m-d H:i:s', strtotime($filter_value));
                            if ($request->get('created_time') == 'before') {
                                $this->searchFields['created_at']['action'] = '<';
                                $query->where($table.'.' . $filter, $this->searchFields[$filter]['action'], $date);
                            } elseif ($request->get('created_time') == 'between') {
                                $date1 = date('Y-m-d H:i:s', strtotime($request->get($filter . '_1')));
                                $query->where($table.'.' . $filter, '>', $date);
                                $query->where($table.'.' . $filter, '<', $date1);
                            } else {
                                $query->where($table.'.' . $filter, $this->searchFields[$filter]['action'], $date);
                            }
                        } elseif ($this->searchFields[$filter]['type'] == 'select') {
                            $query->where($filter, $this->searchFields[$filter]['action'], $filter_value);
                        } else {
                            $query->where($filter, $this->searchFields[$filter]['action'], $filter_value);
                        }
                        if ($this->searchFields[$filter]['action'] == 'like') {
                            $filter_value = substr($filter_value,1,strlen($filter_value)-2);
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

        $rows = $dataType->browseRows;
        foreach ($dataTypeContent as &$item) {
            foreach ($rows as $row) {
                if ($row->type == 'rich_text_box') {
                    $item->{$row->field} = strip_tags($item->{$row->field});
                } elseif ($row->field == 'result_timer' && !empty($item->{$row->field})) {
                    $hh = intdiv($item->{$row->field},3600);
                    $mm = intdiv($item->{$row->field},60) % 60;
                    if (strlen($mm) == 1) {
                        $mm = 0 . $mm;
                    }
                    $ss = $item->{$row->field}%60;
                    if (strlen($ss) == 1) {
                        $ss = 0 . $ss;
                    }
                    $item->{$row->field} = $hh . ':' . $mm . ':' . $ss;
                }
            }
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
            $view = "voyager::routines.browse";
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

}
