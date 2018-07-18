<?php

/**
 * Debug function
 * d($var);
 */
function d($var,$caller=null)
{
    if(!isset($caller)){
        $caller = array_shift(debug_backtrace(1));
    }
    echo '<code>File: '.$caller['file'].' / Line: '.$caller['line'].'</code>';
    echo '<pre>';
    yii\helpers\VarDumper::dump($var, 10, true);
    echo '</pre>';
}

/**
 * Debug function with die() after
 * dd($var);
 */
function dd($var)
{
    $caller = array_shift(debug_backtrace(1));
    d($var,$caller);
    die();
}

/**
 * @param $date
 * @return array
 */
function x_week_range($date) {
    $ts = strtotime($date);

    $start = (date('w', $ts) == 1) ? $ts : strtotime('last monday', $ts);
    $dateStart = date('Y-m-d', $start);

    $end = (date('w', $ts) == 0) ? $ts : strtotime('next sunday', $ts);
    $dateEnd = date('Y-m-d', $end);

    return array($dateStart,$dateEnd);

}