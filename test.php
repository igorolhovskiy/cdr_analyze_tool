<?php

function format_number_colt($number) {

    $national_pattern = "/^0([1-9].*)$/";
    $international_pattern = "/^00([1-9].*)$/";

    if (preg_match($national_pattern, $number, $matches)) {
        return "41" . $matches[1];
    } elseif (preg_match($international_pattern, $number, $matches)) {
        return $matches[1];
    }
    return $number;
}

if (($handle = fopen("COLT_MZ.csv", "r")) === FALSE) {
    die("Cannot open file\n");
}
while (($data = fgetcsv($handle, 0, ",")) !== FALSE) {
    if (count($data) == 2) {
        print("Number: " . format_number_colt($data[0]) . " Duration: " . $data[1] . "\n");
    } else {
        var_dump($data);
        die("Cannot parse line\n");
    }
}
fclose($handle);

?>