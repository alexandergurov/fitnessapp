<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UsersWorkout;
use App\Models\Workout;
use App\Models\Favorite;
use App\Models\Feedback;
use App\Models\Routine;
use Illuminate\Http\Request;
use Vimeo\Laravel\Facades\Vimeo;

class WorkoutController extends Controller
{
    public function getOne($id) {
        $workout = Workout::workoutLoad($id);
        if (empty($workout)) {
            return response()->json('No workout with such id.', 204);
        }
        return response()->json($workout, 200);
    }

    public function getList() {
        $workouts = Workout::workoutList();
        if (isset($workouts[0])) {
            return response()->json($workouts, 200);
        } else {
            return response()->json('No workouts available.', 200);
        }
    }

    public function getListByType($type) {
        $workouts = Workout::workoutList('type', $type);
        if (isset($workouts[0])) {
            return response()->json($workouts, 200);
        } else {
            return response()->json('No workouts available.', 200);
        }
    }

    public function getListByInstructor($instructorId) {
        $workouts = Workout::workoutList('instructor_id', $instructorId);
        if (isset($workouts[0])) {
            return response()->json($workouts, 200);
        } else {
            return response()->json('No workouts available.', 200);
        }
    }

    public function setUsersStatus($id, Request $request) {
        $workout = Workout::where('id', $id)->get()->first();
        if (!$workout) {
            return response()->json('No workout with such id.', 200);
        }
        if (empty($request->status)) {
            return response()->json('Status value is required.', 200);
        }

        $usersWorkout = $workout->getStatusObject(auth()->guard('api')->id(), $workout->id);
        if ($usersWorkout && $usersWorkout->status != 'completed') {
            $data = ['status' => $request->status,
                'timestamp' => $request->timestamp];
            $usersWorkout->update($data);
            $usersWorkout->save();
        } else {
            $data = [
                'user_id' => auth()->guard('api')->id(),
                'workout_id' => $id,
                'status' => $request->status,
                'timestamp' => $request->timestamp,
            ];
            $usersWorkout = UsersWorkout::create($data);
            $workout->update(['times_completed'=>$workout->times_completed+1]);
        }

        return response()->json($usersWorkout, 200);
    }

    public function toggleFavorite($id) {
        $workout = Workout::workoutLoad($id);
        if (!$workout) {
            return response()->json('No workout with such id.', 200);
        }
        if ($userId = auth()->guard('api')->id()) {
            $favoriteEntry = Favorite::createDelete($userId, $id);
            return response()->json($favoriteEntry, 200);
        } else {
            return response()->json('Something is wrong with your login.', 200);
        }

    }

    public function favoriteList() {
        $workouts = Favorite::favoritedList('user_id', auth()->guard('api')->id());
        if (isset($workouts[0])) {
            return response()->json($workouts, 200);
        } else {
            return response()->json('No workouts available.', 200);
        }
    }

    public function postFeedback($id, Request $request) {
        $workout = Workout::workoutLoad($id);
        if (!$workout) {
            return response()->json('No workout with such id.', 200);
        }
        $data = [
            'workout_id' => $id,
            'user_id' => auth()->guard('api')->id(),
            'enjoyed' => $request->enjoyed,
            'how_hard' => $request->how_hard,
            'comment' => $request->comment,
        ];
        $feedback = Feedback::create($data);

        if (isset($feedback)) {
            return response()->json($feedback, 200);
        } else {
            return response()->json('Something went wrong.', 200);
        }
    }

    public function getLeaderboard() {
        $startDate = date('Y-m-d', strtotime('-30 days')) . ' 00:00:00';
        $endDate = date('Y-m-d', strtotime('now')) . ' 23:59:59';
        $workoutCompletions = UsersWorkout::where([
            [
                'created_at',
                '>',
                $startDate
            ],
            ['status', 'completed']
        ])
                                          ->selectRaw('user_id')
                                          ->groupBy('user_id')
                                          ->get()
                                          ->all();
        $results = [];
        foreach ($workoutCompletions as $workoutCompletion) {
            $results[$workoutCompletion->user_id] = UsersWorkout::overallLength($startDate, $endDate, $workoutCompletion->user_id);
        }
        arsort($results);
        foreach ($results as $key => &$result) {
            $user = User::UserLoad($key);
            if ($user) {
                $user->time_workouting = $result;
                $result = $user;
            }
        }
        if (isset($workoutCompletions[0])) {
            return response()->json($results, 200);
        } else {
            return response()->json('No workouts available.', 200);
        }
    }

    public function massRoutinesCreation(Request $request) {
        $data = json_decode($request->data);
        if (isset($data[1])) {
            $length = $data[0];
            $count = count($data[1]);
            $chapters = [];
            for ($i = 0; $i<$count; $i++) {
                $identifier = $request->videoid.':'.$data[1][$i]->index;
                if ($routine = Routine::where('identifier', $identifier)->get()->first()) {
                    $chapters[$routine->id] = $routine;
                } else {
                    if ($i == $count-1) {
                        $chapterLength = $length - $data[1][$i]->startTime;
                    } else {
                        $chapterLength = $data[1][$i+1]->startTime - $data[1][$i]->startTime;
                    }
                    $routineData = [
                        'length' => $chapterLength,
                        'title' => $data[1][$i]->title,
                        'identifier' => $identifier,
                    ];
                    $routine = Routine::create($routineData);
                    $chapters[$routine->id] = $routine;
                }
            }
            print json_encode($chapters);
        }
    }
}
