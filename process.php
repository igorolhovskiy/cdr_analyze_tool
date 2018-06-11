<?php
require_once "config.php";
require_once "functions.php";

$db_ops = new DatabaseOps($local_config, $astpp_config);
$db_ops->sync_databases();
?>