<?php

namespace App\Http\Controllers;

use App\Models\Charity;

class CharityController extends Controller
{
    public function getOne($id) {
        $charity = Charity::where('id', $id)->get()->first();
        $charity->loadTranslated();
        if (empty($charity)) {
            return response()->json('No charity with such id.', 200);
        }
        return response()->json($charity, 200);
    }

    public function getList() {
        $user = auth()->guard('api')->user();
        $language = $user->language ?? 'enGB';
        $charities = Charity::where('language', 'like' , '%' . $language . '%')
                            ->orderByDesc('created_at')
                            ->get();
        if (isset($charities[0])) {
            foreach ($charities as $charity) {
                $charity->loadTranslated();
            }
            return response()->json($charities, 200);
        } else {
            return response()->json('No charities available.', 200);
        }
    }

    public function getListForCountry($country) {
        $charities = Charity::where('related_country', $country)->orderByDesc('created_at')->get();
        if (isset($charities[0])) {
            return response()->json($charities, 200);
        } else {
            return response()->json('No charities available for this country.', 200);
        }
    }
}
