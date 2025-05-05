<?php

namespace App\Http\Controllers;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use TCG\Voyager\Facades\Voyager;
use TCG\Voyager\Http\Controllers\VoyagerBaseController;
use App\Models\UsersWorkout;

class WorkoutLeaderboardController extends VoyagerBaseController {
    private $rows = [
        [
            'field' => 'name',
            'type' => 'standard',
            'label' => 'Name'
        ],
        [
            'field' => 'result',
            'type' => 'custom',
            'label' => 'Time Working Out'
        ]
    ];

    public function index(Request $request)
    {
        $slug = $this->getSlug($request);
        $dataType = Voyager::model('DataType')->where('slug', '=', 'users')->first();
        $this->authorize('browse', app($dataType->model_name));
        $getter = 'get';
        $orderBy = $request->get('order_by', $dataType->order_column);
        $sortOrder = $request->get('sort_order', $dataType->order_direction);
        $model = app($dataType->model_name);

        $query = $model::select('*');

        $csvUrl = route('Wleaderboard.getCsv', array_merge(['slug' => $slug], $request->all()));

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
        foreach ($dataTypeContent as &$item) {
            $startDate = date('Y-m-d', strtotime('-30 days')) . ' 00:00:00';
            $endDate = date('Y-m-d', strtotime('now')) . ' 23:59:59';
            $item->result = UsersWorkout::overallLength($startDate, $endDate, $item->id);
        }

        $dataset = $dataTypeContent->all();

        // Define orderColumn
        $orderColumn = [];
        if ($orderBy) {
            $index = $dataType->browseRows->where('field', $orderBy)->keys()->first() + (0);
            $orderColumn = [[$index, $sortOrder ?? 'desc']];
        }

        $view = 'reports.workout-leaderboard';

        $rows = [];
        foreach ($this->rows as $row) {
            $row = json_decode (json_encode ($row));
            if ($row->field == 'name') {
                $row = Voyager::modelClass('DataRow')::where('field', $row->field)->get()->first();
            } elseif ($row->field == 'result') {
                $params = [];
                $isDesc = $sortOrder != 'asc';
                $row->isCurrentSortField = FALSE;
                $params['order_by'] = $row->field;
                if ($orderBy == $row->field) {
                    if ($row->field && $isDesc) {
                        $params['sort_order'] = 'asc';
                        usort($dataset, "wrkLgthA");
                    } else {
                        $params['sort_order'] = 'desc';
                        usort($dataset, "wrkLgthD");
                    }
                    $row->isCurrentSortField = TRUE;
                }
                $row->sortByUrl = url()->current().'?'.http_build_query(array_merge(\Request::all(), $params));
            }
            $rows[] = $row;
        }

        foreach ($dataset as &$data) {
            if (!$data->name) {
                $data->name = $data->email;
            }
            $data->url = url("/admin/users/{$data->id}/edit");
            if ($data->result) {
                $hours = (int) ($data->result/3600);
                $minutes = (int) (($data->result - $hours * 3600) / 60);
                if (strlen($minutes) == 1) {
                    $minutes = '0' . $minutes;
                }
                $seconds = (int) ($data->result - $hours * 3600 - $minutes * 60);
                if (strlen($seconds) == 1) {
                    $seconds = '0' . $seconds;
                }
                $data->result = $hours . ':' . $minutes . ':' . $seconds;
            } else {
                $data->result = NULL;
            }
        }

        return Voyager::view($view, compact(
            'rows',
            'dataType',
            'dataset',
            'orderBy',
            'orderColumn',
            'sortOrder',
            'csvUrl'
        ));
    }

    public function exportCsv(Request $request)
    {
        $dataType = Voyager::model('DataType')->where('slug', '=', 'users')->first();
        $model = app($dataType->model_name);
        $fileName = 'workouts-leaderboard' . '.csv';
        $getter = 'get';
        $orderBy = $request->get('order_by', $dataType->order_column);
        $sortOrder = $request->get('sort_order', $dataType->order_direction);

        $query = $model::select('*');
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
        foreach ($dataTypeContent as &$item) {
            $startDate = date('Y-m-d', strtotime('-30 days')) . ' 00:00:00';
            $endDate = date('Y-m-d', strtotime('now')) . ' 23:59:59';
            $item->result = UsersWorkout::overallLength($startDate, $endDate, $item->id);
        }

        $items = $dataTypeContent->all();

        $rows = [];
        foreach ($this->rows as $row) {
            $row = json_decode (json_encode ($row));
            if ($row->field == 'result') {
                $params = [];
                $isDesc = $sortOrder != 'asc';
                $row->isCurrentSortField = FALSE;
                $params['order_by'] = $row->field;
                if ($orderBy == $row->field) {
                    if ($row->field && $isDesc) {
                        $params['sort_order'] = 'asc';
                        usort($items, "wrkLgthA");
                    } else {
                        $params['sort_order'] = 'desc';
                        usort($items, "wrkLgthD");
                    }
                    $row->isCurrentSortField = TRUE;
                }
                $row->sortByUrl = url()->current().'?'.http_build_query(array_merge(\Request::all(), $params));
            }
            $rows[] = $row;
        }

        foreach ($items as &$data) {
            if (!$data->name) {
                $data->name = $data->email;
            }
            if ($data->result) {
                $hours = (int) ($data->result/3600);
                $minutes = (int) (($data->result - $hours * 3600) / 60);
                $seconds = (int) ($data->result - $hours * 3600 - $minutes * 60);
                $data->result = $hours . ':' . $minutes . ':' . $seconds;
            } else {
                $data->result = NULL;
            }
        }

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
                $columns[] = $row->label;
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
                    } else {
                        $datarow[$row->label] = $item->{$row->field};
                    }
                }
                fputcsv($file, $datarow,',','"');
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

}
