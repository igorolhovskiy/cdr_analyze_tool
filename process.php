<?php
require_once "config.php";
require_once "functions.php";

$process_cdr_options = array(
    'local' => 3,
    'outbound' => 3,
    'is_detailed' => True,
    'round_digits' => 5
);

if (($handle = fopen("COLT_MZ.csv", "r")) === FALSE) {
    die("Cannot open file\n");
}

$number_translator = new NumberTranslation();
$csv_object = array();

while (($data = fgetcsv($handle, 0, ",")) !== FALSE) {
    if (count($data) == 2) {
        $csv_object[] = array('number' => $number_translator->colt_austria($data[0]), 'duration' => $data[1]);
    } else {
        var_dump($data);
        die("Cannot parse line\n");
    }
}
fclose($handle);

print("Done processing CSV\n");

$db_ops = new DatabaseOps($local_config);
$db_ops->import_cdr($csv_object);

print("Done importing CSV\n");

$rm = new RateMachine($db_ops);

$res = $rm->process_cdr($process_cdr_options);

echo (new ArrayToTextTable($res))->render();

?>