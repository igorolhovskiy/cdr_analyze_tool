<?php
require_once "config.php";
require_once "functions.php";

$process_cdr_options = array(
    'local' => 3,
    'outbound' => 3,
    'is_detailed' => True,
    'round_digits' => 5
);

/*$csv_object = (new FileReader())->read_csv_file("COLT_MZ.csv", 'colt_austria');
print("Done processing CSV\n");

$db_ops = new DatabaseOps($local_config, $astpp_config);
$db_ops->import_cdr($csv_object);

print("Done importing CSV\n");

$db_ops->sync_databases();

print("Done database sync\n");

$rm = new RateMachine($local_config);

$res = $rm->process_cdr($process_cdr_options);

var_dump($res);
*/
$rm = new RateMachine($local_config);

var_dump($rm->get_rates_info());


?>