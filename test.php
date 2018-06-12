<?php

function get_correct_time($time, $init_inc = 1, $inc = 1) {

    if ($time <= 0) {
        return 0;
    }

    if ($time <= $init_inc) {
        return $init_inc;
    }

    $time_corrected = $time - $init_inc;
    $extra_add = ($time_corrected % $inc == 0) ? 0 : 1;
    
    $time_corrected = $init_inc + (floor($time_corrected / $inc) + $extra_add) * $inc;
    return $time_corrected;
}

$time = 25;
$init_inc = 30;
$inc = 1;

print(get_correct_time($time, $init_inc, $inc). "\n");
?>