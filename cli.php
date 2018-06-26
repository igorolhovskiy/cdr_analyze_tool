<?php
require_once "config.php";
require_once "functions.php";

$rm = new RateMachine($local_config);
$local = 8;
$outbound = 3;

$process_cdr_options = array(
          'local' => $local, // id from drop-down menu
          'outbound' => $outbound, // id from drop-down menu
          'is_detailed' => True,
          'round_digits' => 5
      );

$process_data = $rm->process_cdr($process_cdr_options);  

var_dump($process_data);
?>