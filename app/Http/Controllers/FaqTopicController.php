<?php

namespace App\Http\Controllers;

use App\Models\FaqItem;
use App\Models\FaqTopic;

class FaqTopicController extends Controller
{
    public function getOne($id) {
        $faqTopic = FaqTopic::FaqTopicLoad($id);
        if (empty($faqTopic)) {
            return response()->json('No FAQ topic with such id.',204);
        }
        return response()->json($faqTopic, 200);
    }

    public function getList() {
        $user = auth()->guard('api')->user();
        $language = $user->language ?? 'enGB';
        $faqTopics = FaqTopic::where([['language', 'like' , '%' . $language . '%'],['status','PUBLISHED']])
                             ->orderByDesc('created_at')
                             ->get();
        if (isset($faqTopics[0])) {
            foreach ($faqTopics as &$faqTopic) {
                $faqTopic->loadTranslated();
            }
            return response()->json($faqTopics, 200);
        } else {
            return response()->json('No FAQ topics available.', 200);
        }
    }

    public function getItems() {
        $user = auth()->guard('api')->user();
        $language = $user->language ?? 'enGB';
        $faqItems = FaqItem::where([['language', 'like' , '%' . $language . '%'],['status','PUBLISHED']])
                           ->orderByDesc('created_at')
                           ->get();
        if (isset($faqItems[0])) {
            foreach ($faqItems as &$faqItem) {
                $faqItem->loadTranslated();
                if ($faqItem->faqTopic) {
                    $faqItem->faq_topic = $faqItem->faqTopic;
                }
            }
            return response()->json($faqItems, 200);
        } else {
            return response()->json('No faq items available.', 200);
        }
    }
}
