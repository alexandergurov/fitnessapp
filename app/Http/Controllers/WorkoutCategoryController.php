<?php

namespace App\Http\Controllers;

use App\Models\Workout;
use App\Models\WorkoutCategory;
use Illuminate\Http\Request;

class WorkoutCategoryController extends Controller
{
    public function getOne($id) {
        $workoutCategory = WorkoutCategory::workoutCategoryLoad($id);
        if (empty($workoutCategory)) {
            return response()->json('No workout with such id.', 204);
        }
        return response()->json($workoutCategory, 200);
    }

    public function getList() {
        $workoutCategories = WorkoutCategory::workoutCategoryList();
        if (isset($workoutCategories[0])) {
            return response()->json($workoutCategories, 200);
        } else {
            return response()->json('No workout categories available.', 200);
        }
    }
}
