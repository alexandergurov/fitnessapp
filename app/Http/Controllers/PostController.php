<?php

namespace App\Http\Controllers;

use App\Models\FinessAppPost;

class PostController extends Controller
{
    public function getOne($id) {
        $post = FinessAppPost::where('id', $id)->get()->first();
        $post->loadTranslated();
        $post->generateThumbnails('image');
        if (!isset($post[0])) {
            return response()->json('No post with such id.', 204);
        }
        return response()->json($post[0], 200);
    }

    public function getList() {
        $user = auth()->guard('api')->user();
        $language = $user->language ?? 'enGB';
        $posts = FinessAppPost::where([['language', 'like' , '%' . $language . '%'],['status','PUBLISHED']])
                     ->orderByDesc('created_at')
                     ->get();
        if (isset($posts[0])) {
            foreach ($posts as &$post) {
                $post->loadTranslated();
                $post->generateThumbnails('image');
            }
            return response()->json($posts, 200);
        } else {
            return response()->json('No posts available.', 200);
        }
    }

    public function getListByCategory($categoryId) {
        $user = auth()->guard('api')->user();
        $language = $user->language ?? 'enGB';
        $posts = FinessAppPost::where([['category_id', $categoryId],['language', 'like' , '%' . $language . '%'],['status','PUBLISHED']])->orderByDesc('created_at')->get();
        if (isset($posts[0])) {
            foreach ($posts as &$post) {
                $post->loadTranslated();
                $post->generateThumbnails('image');
            }
            return response()->json($posts, 200);
        } else {
            return response()->json('No posts available.', 200);
        }
    }

    public function getListByInstructor($instructorId) {
        $user = auth()->guard('api')->user();
        $language = $user->language ?? 'enGB';
        $posts = FinessAppPost::where([['instructor_id', $instructorId],['language', 'like' , '%' . $language . '%'],['status','PUBLISHED']])->orderByDesc('created_at')->get();
        if (isset($posts[0])) {
            foreach ($posts as &$post) {
                $post->loadTranslated();
                $post->generateThumbnails('image');
            }
            return response()->json($posts, 200);
        } else {
            return response()->json('No posts available.', 200);
        }
    }
}
