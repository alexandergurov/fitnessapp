<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Vimeo\Laravel\Facades\Vimeo;

class VimeoController extends Controller
{
    static function getInfo(Request $request) {
        $response = Vimeo::request('/videos/' . $request->get('video-id'), [], 'GET');
        if (true) {
            return json_encode($response["body"]);
        }
    }
}
