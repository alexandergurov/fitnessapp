<?php

namespace App\Http\Controllers;

use App\Models\Challenge;
use App\Models\ChallengeCompletion;
use App\Models\User;
use Illuminate\Http\Request;

class ChallengeController extends Controller {
    public function getOne($id) {
        $challenge = Challenge::where('id', $id)->get()->first();
        $challenge->generateThumbnails('class_image');
        $challenge->loadTranslated();
        if (empty($challenge)) {
            return response()->json('No challenge with such id.', 200);
        }
        return response()->json($challenge, 200);
    }

    public function getList() {
        $user = auth()->guard('api')->user();
        $language = $user->language ?? 'enGB';
        $challenges = Challenge::where('language', 'like' , '%' . $language . '%')
                               ->orderByDesc('created_at')
                               ->get();
        if (isset($challenges[0])) {
            foreach ($challenges as &$challenge) {
                $challenge->generateThumbnails('icon');
                $challenge->loadTranslated();
            }
            return response()->json($challenges, 200);
        }
        else {
            return response()->json('No challenges available.', 200);
        }
    }

    public function complete($id, Request $request) {
        $challenge = Challenge::where('id', $id)->get()->first();
        if (empty($challenge)) {
            return response()->json('No challenge with such id.', 200);
        }
        if (empty($request->result)) {
            return response()->json('Result value is required.', 200);
        }
        $data = [
            'user_id' => auth()->guard('api')->id(),
            'challenge_id' => $id,
        ];

        switch ($challenge->type) {
            case 'amount':
                $data['result_amount'] = $request->result;
                break;
            case 'timer':
            case 'time_required':
                $data['result_timer'] = $request->result;
                break;
        }

        $challengeCompletion = ChallengeCompletion::create($data);

        return response()->json($challengeCompletion, 200);
    }

    public function allResults($id, Request $request) {
        $challenge = Challenge::where('id', $id)->get()->first();
        $results = ChallengeCompletion::where([
            ['challenge_id', $id],
            ['created_at', '>=', date('Y-m-d', strtotime('-30 days')) . ' 00:00:00']])
                                      ->get()
                                      ->all();
        if ($results) {
            switch ($challenge->type) {
                case 'amount':
                    usort($results, function ($a, $b) {
                        return $a->result_amount < $b->result_amount;
                    });
                    break;
                case 'timer':
                    usort($results, function ($a, $b) {
                        return $a->result_timer < $b->result_timer;
                    });
                    break;
                case 'time_required':
                    usort($results, function ($a, $b) {
                        return $a->result_timer > $b->result_timer;
                    });
                    break;
            }
        }
        foreach ($results as &$result) {
            $user = User::UserLoad($result->user_id);
            $result->user = $user;
        }
        return $results;
    }

    public function usersResults(Request $request) {
        $results = ChallengeCompletion::where('user_id', auth()->guard('api')->id())->get()->all();
        $structuredResults = [];
        foreach ($results as $result) {
            $structuredResults[$result->challenge_id][] = $result;
        }
        return $structuredResults;
    }
}

