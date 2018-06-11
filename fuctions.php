<?php

class mysqlix extends mysqli {
    public function __construct($host, $user, $pass, $db) {
        parent::__construct($host, $user, $pass, $db);

        if ($this->connect_error) {
            die('Connect Error to host ' . $host . ': (' . $this->connect_errno . ') ' . $this->connect_error);
        }
    }
}


Class DatabaseOps {
    private $db_conn_astpp;
    private $db_conn_local;
    private $sync_tables = ['gateways', 'pricelists', 'outbound_routes', 'routes'];

    public function __construct($local_config = false, $astpp_config = false) {
        // Do local connection
        if (!$local_config) {
            return False;
        }
        $db_conn_local = new mysqlix($local_config['host'], $local_config['username'], $local_config['password'], $local_config['database']);

        // Do astpp connection
        if ($astpp_config) {
            $db_conn_astpp = new mysqlix($astpp_config['host'], $astpp_config['username'], $astpp_config['password'], $astpp_config['database']);
        } else {
            $db_conn_astpp = False;
        }
    }

    function sync_databases() {
        if (!$db_conn_astpp) {
            return False;
        }

        foreach ($sync_tables as $table) {
            // First - get database description
            $fields_data = $this->exec_query_astpp("DESCRIBE ".$table);
            $sql_create = "CREATE TABLE " . $table . " (";
            foreach ($fields_data  as $key => $value) {
               print('Key: ' . $key . ' Value: ' . $value);
            }
            // Create local table
            $this->exec_query_local("DROP TABLE IF EXISTS " . $table);
        }
    }

    function import_cdr($csv_object) {

    }

    function exec_query_local($sql) {
        return exec_query($sql, True);
    }

    function exec_query_astpp($sql) {
        return exec_query($sql, False);
    }

    function exec_query($sql, $is_local = True) {
        if (!($is_local && $db_conn_astpp)) {
            return False;
        }

        $db_conn = $is_local ? $db_conn_local : $db_conn_astpp;
        
        $db_res = $db_conn->query($sql);

        if (!$db_res) {
            die('Error in statement ' . $sql . '  ' . $db_conn->error);
        }
        if (substr($sql, 0, 6) === "SELECT" || substr($sql, 0, 8) === "DESCRIBE") {
            return $db_res->fetch_all(MYSQLI_ASSOC);
        } 
        return True;

    }
}

Class RateMachine {

    public function __construct($database_ops) {

    }
}
?>