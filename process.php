<?php
require_once "config.php";
require_once "functions.php";

define('WORK_MODE', 'live'); // 'mock' or 'live'

if (WORK_MODE === 'mock') {
  require_once "mockdata.php";
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
  $action = $_GET['action'];
  if ($action === 'menu_info') {
    if (WORK_MODE === 'live') {
      $rm = new RateMachine($local_config);
      $menu_info = $rm->get_rates_info();    
    }
    header('Content-Type: application/json');
    echo json_encode($menu_info);
  }

} else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (isset($_FILES['csvfile'])) { // Upload CSV
    $csv_file = basename($_FILES['csvfile']['name']);    
    if ( move_uploaded_file($_FILES['csvfile']['tmp_name'], $csv_file)) {
      // Upload CSV
      if (WORK_MODE === 'live') {
        $csv_object = (new FileReader())->read_csv_file($csv_file, 'colt_austria');

        $db_ops = new DatabaseOps($local_config);
        $db_ops->import_cdr($csv_object);
      } else {
        sleep(5);
      }
      // End Upload CSV      
    }
    echo "OK";
  } else if ($_POST['action'] === 'process') {
    $local = $_POST['local'];
    $outbound = $_POST['outbound'];
    if (WORK_MODE === 'live') {
      $process_cdr_options = array(
          'local' => $local, // id from drop-down menu
          'outbound' => $outbound, // id from drop-down menu
          'is_detailed' => True,
          'round_digits' => 5
      );
      // Process CDR
      $rm = new RateMachine($local_config);

      $process_data = $rm->process_cdr($process_cdr_options);      
    } else {
      sleep(5);
    }
    header('Content-Type: application/json');
    echo json_encode($process_data);

  } else if ($_POST['action'] === 'sync-databases') {
    if (WORK_MODE === 'live') {
      // Sync databases
      $db_ops = new DatabaseOps($local_config, $astpp_config);

      $db_ops->sync_databases();

      // End Sync Databases
    } else {
      sleep(5);
    }
    header('Content-Type: application/json');
    echo json_encode(array('success' => 'true'));
  }
}

return true;

/*
output example

array(2) {
  ["local"]=>
  array(22) {
    ["total"]=>
    float(645.70999999998)
    ["Austria"]=>
    float(73.400000000001)
    ["Austria-Personal Number"]=>
    float(14.1)
    ["Germany"]=>
    float(1.14)
    ["France"]=>
    float(0.12)
    ["United Kingdom"]=>
    float(1.5)
    ["Israel-Tel Aviv"]=>
    float(1.65)
    ["Japan-Tokyo"]=>
    float(1.35)
    ["Switzerland-Zurich"]=>
    float(0.6)
    ["Austria-Mobile-Others"]=>
    float(0.45)
    ["Saturn Mobil / Media Markt Mobil"]=>
    float(1.35)
    ["Austria-Mobile-Mobilkom"]=>
    float(424.49999999999)
    ["Austria-Mobile-Hutchison"]=>
    float(47.7)
    ["Mobile others"]=>
    float(0.15)
    ["Austria-Mobile-TMobile"]=>
    float(71.1)
    ["France-Mobile-Orange"]=>
    float(1.2)
    ["United Kingdom-Mobile-H3G"]=>
    float(0.3)
    ["Israel-Mobile"]=>
    float(0.9)
    ["Switzerland-Mobile-Salt"]=>
    float(0.9)
    ["Slovakia-Mobile-EuroTel"]=>
    float(1.2)
    ["Slovakia-Mobile-Orange"]=>
    float(1.8)
    ["Hungary-Mobile-Vodafone"]=>
    float(0.3)
  }
  ["outbound"]=>
  array(20) {
    ["total"]=>
    float(43.50736)
    ["Austria"]=>
    float(14.04252)
    ["Austria-Personal Number"]=>
    float(0)
    ["Germany"]=>
    float(0.04954)
    ["France"]=>
    float(0.00548)
    ["United Kingdom"]=>
    float(0.01793)
    ["Israel-Tel Aviv"]=>
    float(0.02287)
    ["Japan-Tokyo"]=>
    float(0.12513)
    ["Switzerland-Zurich"]=>
    float(0.08371)
    ["Austria-Mobile-Others"]=>
    float(0.0826)
    ["Austria-Mobile-Mobilkom"]=>
    float(21.7728)
    ["Austria-Mobile-Hutchison"]=>
    float(2.8318)
    ["Austria-Mobile-TMobile"]=>
    float(3.9206)
    ["France-Mobile-Orange"]=>
    float(0.15745)
    ["United Kingdom-Mobile-H3G"]=>
    float(0.0135)
    ["Israel-Mobile"]=>
    float(0.05194)
    ["Switzerland-Mobile-Salt"]=>
    float(0)
    ["Slovakia-Mobile-EuroTel"]=>
    float(0.09626)
    ["Slovakia-Mobile-Orange"]=>
    float(0.22156)
    ["Hungary-Mobile-Vodafone"]=>
    float(0.01167)
  }
}

*/
// End process CDR


// Get DropDown menu info

/*
output example
array(2) {
  ["local"]=>
  array(8) {
    [0]=>
    array(2) {
      ["id"]=>
      string(1) "1"
      ["name"]=>
      string(7) "default"
    }
    [1]=>
    array(2) {
      ["id"]=>
      string(1) "2"
      ["name"]=>
      string(6) "Fusion"
    }
    [2]=>
    array(2) {
      ["id"]=>
      string(1) "3"
      ["name"]=>
      string(6) "Fusion"
    }
    [3]=>
    array(2) {
      ["id"]=>
      string(1) "4"
      ["name"]=>
      string(16) "Fusion + Package"
    }
    [4]=>
    array(2) {
      ["id"]=>
      string(1) "5"
      ["name"]=>
      string(16) "Fusion + Package"
    }
    [5]=>
    array(2) {
      ["id"]=>
      string(1) "7"
      ["name"]=>
      string(11) "Mediaplanet"
    }
    [6]=>
    array(2) {
      ["id"]=>
      string(1) "8"
      ["name"]=>
      string(11) "TCN VoIP 59"
    }
    [7]=>
    array(2) {
      ["id"]=>
      string(1) "6"
      ["name"]=>
      string(16) "Terminate Hangup"
    }
  }
  ["outbound"]=>
  array(2) {
    [0]=>
    array(2) {
      ["id"]=>
      string(1) "2"
      ["name"]=>
      string(9) "A2Billing"
    }
    [1]=>
    array(2) {
      ["id"]=>
      string(1) "3"
      ["name"]=>
      string(9) "SkyTel_EE"
    }
  }
}
*/

// End get DropDown menu info

?>