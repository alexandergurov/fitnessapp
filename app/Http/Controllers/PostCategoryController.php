<?php

namespace App\Http\Controllers;

use App\Models\Category;

class PostCategoryController extends Controller
{

    public function getList() {
        $user = auth()->guard('api')->user();
        $language = $user->language ?? 'enGB';
        $categories = Category::where('language', 'like' , '%' . $language . '%')
                              ->orderByDesc('created_at')
                              ->get();
        if (isset($categories[0])) {
            foreach ($categories as $category) {
                $category->loadTranslated();
            }
            return response()->json($categories, 200);
        } else {
            return response()->json('No posts available.', 200);
        }
    }
}
