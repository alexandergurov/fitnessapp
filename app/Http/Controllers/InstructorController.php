<?php

namespace App\Http\Controllers;

use App\Models\Instructor;

class InstructorController extends Controller
{
    public function getOne($id) {
        $instructor = Instructor::instructorLoad($id);
        $instructor->loadTranslated();
        $instructor->generateThumbnails('photo');
        if (!isset($instructor->id)) {
            return response()->json('No instructor with such id.', 204);
        }
        return response()->json($instructor, 200);
    }

    public function getList() {
        $user = auth()->guard('api')->user();
        $language = $user->language ?? 'enGB';
        $instructors = Instructor::where('language', 'like' , '%' . $language . '%')
                           ->orderByDesc('created_at')
                           ->get();
        if (isset($instructors[0])) {
            foreach ($instructors as &$instructor) {
                $instructor->loadTranslated();
                $instructor->generateThumbnails('photo');
            }
            return response()->json($instructors, 200);
        } else {
            return response()->json('No instructors available.', 200);
        }
    }
}
