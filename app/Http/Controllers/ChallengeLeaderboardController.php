<?php

namespace App\Http\Controllers;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use TCG\Voyager\Facades\Voyager;
use TCG\Voyager\Http\Controllers\VoyagerBaseController;
use App\Models\Challenge;
use App\Models\User;
use App\Models\ChallengeCompletion;

class ChallengeLeaderboardController extends Controller {
    private $rows = [
        [
            'field' => 'name',
            'type' => 'standard',
            'label' => 'Name'
        ],
        [
            'field' => 'result',
            'type' => 'custom',
            'label' => 'Result'
        ]
    ];

    public function index(Request $request, $id = NULL)
    {
        if (!$id) {
            return redirect('/admin/challenges');
        }
        $slug = 'challenge-completions';
        $dataType = Voyager::model('DataType')->where('slug', '=', $slug)->first();
        $this->authorize('browse', app($dataType->model_name));
        $getter = 'get';
        $orderBy = $request->get('order_by', $dataType->order_column);
        $sortOrder = $request->get('sort_order', $dataType->order_direction);
        $model = app($dataType->model_name);

        $query = $model::select('*');

        $csvUrl = route('Cleaderboard.getCsv', array_merge(['id' => $id], $request->all()));
        $startDate = date('Y-m-d', strtotime('-30 days')) . ' 00:00:00';
        $query->where('created_at','>',$startDate);
        $query->where('challenge_id', $id);

        $challenge = Challenge::where('id', $id)->get()->first();
        $pageTitle = $challenge->title . ' Leaderboard';
        $dataTypeContent = call_user_func([$query->latest($model::CREATED_AT), $getter]);

        $dataset = $dataTypeContent->all();

        $view = 'reports.challenge-leaderboard';

        $rows = [];

        $isDesc = $sortOrder != 'asc';
        foreach ($this->rows as $row) {
            $row = json_decode (json_encode ($row));
            if ($row->field == 'name') {
                $row = Voyager::modelClass('DataRow')::where('field', $row->field)->get()->first();
            } elseif ($row->field == 'result') {
                $params = [];
                $row->isCurrentSortField = FALSE;
                $params['order_by'] = $row->field;
                if ($orderBy == 'result' && $isDesc) {
                    $params['sort_order'] = 'asc';
                } else {
                    $params['sort_order'] = 'desc';
                }
                if ($orderBy == $row->field) {
                    $row->isCurrentSortField = TRUE;
                }
                $row->sortByUrl = url()->current().'?'.http_build_query(array_merge(\Request::all(), $params));
            }
            $rows[] = $row;
        }

        foreach ($dataset as $key => &$data) {
            $user = User::where('id', $data->user_id)->get()->first();
            if ($user) {
                if ($user->name) {
                    $data->name = $user->name;
                } else {
                    $data->name = $user->email;
                }
                switch ($challenge->type) {
                    case 'amount':
                        $data->result = $data->result_amount;
                        break;
                    case 'timer':
                    case 'time_required':
                        $data->result = $data->result_timer;
                        $hours = (int) ($data->result / 3600);
                        $minutes = (int) (($data->result - $hours * 3600) / 60);
                        if (strlen($minutes) == 1) {
                            $minutes = '0' . $minutes;
                        }
                        $seconds = (int) ($data->result - $hours * 3600 - $minutes * 60);
                        if (strlen($seconds) == 1) {
                            $seconds = '0' . $seconds;
                        }
                        $data->result = $hours . ':' . $minutes . ':' . $seconds;
                        break;
                }
                $data->url = url("/admin/users/{$user->id}/edit");
            }
            else {
                unset($dataset[$key]);
            }
        }

        $orderColumn = $orderBy;
        if ($orderBy == 'result') {
            switch ($challenge->type) {
                case 'amount':
                    if ($isDesc) {
                        usort($dataset, "challengeResultCompare");
                    }
                    else {
                        usort($dataset, "challengeResultCompareDesc");
                    }
                    break;
                case 'timer':
                    if ($isDesc) {
                        usort($dataset, "challengeTimerCompare");
                    }
                    else {
                        usort($dataset, "challengeTimerCompareDesc");
                    }
                    break;
                case 'time_required':
                    if ($isDesc) {
                        usort($dataset, "challengeTimerCompareDesc");
                    }
                    else {
                        usort($dataset, "challengeTimerCompare");
                    }
                    break;
            }
        }
        if ($orderBy == 'name') {
            if ($isDesc) {
                usort($dataset, "nameCmp");
            } else {
                usort($dataset, "nameCmpDesc");
            }
        }

        return Voyager::view($view, compact(
            'pageTitle',
            'rows',
            'dataType',
            'dataset',
            'orderBy',
            'orderColumn',
            'sortOrder',
            'csvUrl'
        ));
    }

    public function exportCsv(Request $request, $id)
    {
        $slug = 'challenge-completions';
        $dataType = Voyager::model('DataType')->where('slug', '=', $slug)->first();
        $this->authorize('browse', app($dataType->model_name));
        $getter = 'get';
        $orderBy = $request->get('order_by', $dataType->order_column);
        $sortOrder = $request->get('sort_order', $dataType->order_direction);
        $model = app($dataType->model_name);

        $query = $model::select('*');

        $csvUrl = route('Cleaderboard.getCsv', array_merge(['id' => $id], $request->all()));
        $startDate = date('Y-m-d', strtotime('-30 days')) . ' 00:00:00';
        $query->where('created_at','>',$startDate);
        $query->where('challenge_id', $id);

        $challenge = Challenge::where('id', $id)->get()->first();

        $dataTypeContent = call_user_func([$query->latest($model::CREATED_AT), $getter]);

        $items = $dataTypeContent->all();

        $rows = [];

        foreach ($this->rows as $row) {
            $rows[] = json_decode (json_encode ($row));
        }

        $isDesc = $sortOrder != 'asc';

        foreach ($items as $key => &$data) {
            $user = User::where('id', $data->user_id)->get()->first();
            if ($user) {
                if ($user->name) {
                    $data->name = $user->name;
                } else {
                    $data->name = $user->email;
                }
                switch ($challenge->type) {
                    case 'amount':
                        $data->result = $data->result_amount;
                        break;
                    case 'timer':
                    case 'time_required':
                        $data->result = $data->result_timer;
                        $hours = (int) ($data->result / 3600);
                        $minutes = (int) (($data->result - $hours * 3600) / 60);
                        if (strlen($minutes) == 1) {
                            $minutes = '0' . $minutes;
                        }
                        $seconds = (int) ($data->result - $hours * 3600 - $minutes * 60);
                        if (strlen($seconds) == 1) {
                            $seconds = '0' . $seconds;
                        }
                        $data->result = $hours . ':' . $minutes . ':' . $seconds;
                        break;
                }
            }
            else {
                unset($items[$key]);
            }
        }

        if ($orderBy == 'result') {
            switch ($challenge->type) {
                case 'amount':
                    if ($isDesc) {
                        usort($items, "challengeResultCompare");
                    }
                    else {
                        usort($items, "challengeResultCompareDesc");
                    }
                    break;
                case 'timer':
                    if ($isDesc) {
                        usort($items, "challengeTimerCompare");
                    }
                    else {
                        usort($items, "challengeTimerCompareDesc");
                    }
                    break;
                case 'time_required':
                    if ($isDesc) {
                        usort($items, "challengeTimerCompareDesc");
                    }
                    else {
                        usort($items, "challengeTimerCompare");
                    }
                    break;
            }
        }
        if ($orderBy == 'name') {
            if ($isDesc) {
                usort($dataset, "nameCmp");
            } else {
                usort($dataset, "nameCmpDesc");
            }
        }

        $fileName = $challenge->title . ' leaderboard' . '.csv';

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
                    $datarow[$row->label] = $item->{$row->field};
                }
                fputcsv($file, $datarow,',','"');
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

}
