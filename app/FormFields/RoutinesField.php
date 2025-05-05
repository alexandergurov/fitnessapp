<?php

namespace App\FormFields;

use TCG\Voyager\FormFields\AbstractHandler;
use App\Models\Routine;

class RoutinesField extends AbstractHandler
{
    protected $codename = 'routines';

    public function createContent($row, $dataType, $dataTypeContent, $options)
    {
        $routines = Routine::orderBy('created_at', 'DESC')->get();;
        $dataStored = json_decode($dataTypeContent->{$row->field}, TRUE);
        return view('formfields.routines', [
            'row' => $row,
            'routineOptions' => $routines,
            'dataStored' => $dataStored,
            'dataType' => $dataType,
            'dataTypeContent' => $dataTypeContent
        ]);
    }
}
