<?php

function getLocale() {
    $user = auth()->guard('api')->user();
    $locale = 'en';
    switch ($user->language) {
        case 'enGB': $locale = 'en'; break;
        case 'thTH': $locale = 'th'; break;
    }
    return $locale;
}

function wrkLgthA($a, $b) {
    return $a->result < $b->result;
}
function wrkLgthD($a, $b) {
    return $a->result > $b->result;
}

function challengeTimerCompare($a, $b) {
    return (int) ($a->result_timer) < (int) ($b->result_timer);
}
function challengeTimerCompareDesc($a, $b) {
    return (int) ($a->result_timer) > (int) ($b->result_timer);
}
function challengeResultCompare($a, $b) {
    return (int) ($a->result_amount) < (int) ($b->result_amount);
}
function challengeResultCompareDesc($a, $b) {
    return (int) ($a->result_amount) > (int) ($b->result_amount);
}
function nameCmp($a, $b) {
    return strcmp($a->name,$b->name);
}
function nameCmpDesc($a, $b) {
    return strcmp($b->name,$a->name);
}
