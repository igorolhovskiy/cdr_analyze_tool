<?php

class mysqlix extends mysqli {
    public function __construct($host, $user, $pass, $db) {
        parent::__construct($host, $user, $pass, $db);

        if ($this->connect_error) {
            die('Connect Error to host ' . $host . ': (' . $this->connect_errno . ') ' . $this->connect_error);
        }

    }

    public function insert_array($insData, $table) {
        $prep = array();
        foreach($insData as $k => $v ) {
            $prep[$k] = "'" . $v . "'";
        }
        $sql = "INSERT INTO $table ( " . implode(', ',array_keys($insData)) . ") VALUES (" . implode(', ',array_values($prep)) . ")";
        if (!$this->query($sql)) {
            die('Error in statement ' . $sql . " \n " . $this->error);
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
            $this->$db_conn_local = False;
            $this->$db_conn_astpp = False;
            return False;
        }
        $this->db_conn_local = new mysqlix($local_config['host'], $local_config['username'], $local_config['password'], $local_config['database']);

        // Do astpp connection
        if ($astpp_config) {
            $this->db_conn_astpp = new mysqlix($astpp_config['host'], $astpp_config['username'], $astpp_config['password'], $astpp_config['database']);
        } else {
            $this->db_conn_astpp = False;
        }
    }

    public function get_local_db_status() {
        if (isset($this->db_conn_local) && $this->db_conn_local) {
            return True;
        }
        return False;
    }

    function sync_databases() {
        if (!$this->db_conn_astpp) {
            return False;
        }

        foreach ($this->sync_tables as $table) {
            // First - get database description
            $fields_data = $this->exec_query_astpp("SHOW CREATE TABLE ".$table);

            $sql = $fields_data[0]['Create Table'];
            $sql = str_replace("\n", '', $sql);
            // Create local table
            $this->exec_query_local("DROP TABLE IF EXISTS " . $table);
            $this->exec_query_local($sql);

            // Copy data from astpp table to local
            $table_data = $this->exec_query_astpp("SELECT * FROM $table");
            foreach ($table_data as $row) {
                $this->db_conn_local->insert_array($row, $table);
            }
        }
    }

    function import_cdr($csv_object) {
        // Assume, we receive array with assoc arrays with following format
        // number - number been called in international format
        // duration - duration in seconds

        $sql = "DROP TABLE IF EXISTS cdr";
        $this->exec_query_local($sql);

        $sql = "CREATE TABLE cdr (number VARCHAR(255), duration VARCHAR(255))";
        $this->exec_query_local($sql);

        foreach ($csv_object as $cdr_line) {
            // Sterialize data here;
            if (isset($cdr_line['number']) && isset($cdr_line['duration'])) {
                $this->db_conn_local->insert_array($cdr_line, 'cdr');
            } else {
                var_dump($cdr_line);
                printf("Cannot import line\n");
                return False;
            }
        }
        
        return True;
    }

    function exec_query_local($sql) {
        return $this->exec_query($sql, True);
    }

    function exec_query_astpp($sql) {
        return $this->exec_query($sql, False);
    }

    function exec_query($sql, $is_local = True) {
        if (!$is_local && !$this->db_conn_astpp) {
            return False;
        }

        $db_conn = $is_local ? $this->db_conn_local : $this->db_conn_astpp;
        
        $db_res = $db_conn->query($sql);

        if (!$db_res) {
            die('Error in statement ' . $sql . " \n " . $db_conn->error);
        }

        if (substr($sql, 0, 6) === "SELECT" || substr($sql, 0, 4) === "SHOW") {
            if ($db_res->num_rows == 0) {
                return -1;
            }
            $result = array();
            while ($row = $db_res->fetch_array(MYSQLI_ASSOC)) {
                $result[] = $row;
            }
            return $result;
        } 
        return True;

    }

    function get_cdr() {
        return $this->exec_query_local("SELECT * FROM cdr");
    }
}

Class RateMachine {

    private $database_ops;

    public function __construct($database_ops) {
        if ($database_ops->get_local_db_status()) {
            $this->database_ops = $database_ops;
            return True;
        }
        return False;
    }


    private function get_correct_time($time, $init_inc = 1, $inc = 1) {
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

    private function number_loop($number, $field_name = 'pattern') {
        // Form SQL request
        $sql = "(" . $field_name . " = '^ " . $number . ".*'";

        print(strlen($number)."\n");

        for ($i = strlen($number) - 1; $i >= 1; $i--) {
            $sql .= " OR $field_name = '^" . substr($number, 0, $i) . ".*'";
        }
        $sql .= ")";
        return $sql;      
    }

    private function get_info_local($cdr_line, $pricelist_id, $round_digits = 2) {

        // SELECT * FROM routes WHERE number_loop(number, pattern) AND status = 0 AND pricelist_id = 6  ORDER BY LENGTH(pattern) DESC,cost DESC LIMIT 1
        $sql = "SELECT * FROM routes WHERE " . number_loop($cdr_line['number']) . " AND pricelist_id = " . $pricelist_id . " ORDER BY LENGTH(pattern) DESC,cost DESC LIMIT 1";
        $rate_line = $database_ops->exec_query_local($sql);
        if (!$rate_line) {
            die("Cannon run sql: $sql\n");
        }

        if ($rate_line == -1) {
            return ['undefined', 0];
        }

        $call_time = $this->get_correct_time($cdr_line['duration'], $rate_line['init_inc'], $rate_line['inc']);

        $call_price = round((float) $call_time * (float) $rate_line['cost'], $round_digits);
        
        return [$rate_line['comment'], $call_price];

        // Return destination and price
    }

    private function get_info_outbound($cdr_line, $pricelist_id, $round_digits = 2) {

        $sql = "SELECT * FROM outbound_routes WHERE " . number_loop($cdr_line['number']) . " AND trunk_id = " . $pricelist_id . " ORDER BY LENGTH(pattern) DESC,cost DESC LIMIT 1";

        $rate_line = $database_ops->exec_query_local($sql);
        if (!$rate_line) {
            return [False, False];
        }

        if ($rate_line == -1) {
            return ['undefined', 0];
        }

        $call_time = $this->get_correct_time($cdr_line['duration'], $rate_line['init_inc'], $rate_line['inc']);

        $call_price = round((float) $call_time * (float) $rate_line['cost'], $round_digits);
        
        return [$rate_line['comment'], $call_price];
        // Return destination and price
    }

    public function process_cdr($options) {
        /*
            $options in format
            'local' => ID of tariff plan - 'routes'. Optional.
            'outbound' => ID of gateway - 'outbound_routes'. Optional
            'is_detailed' => return array with detailed info on destinations. TBD. Optional
        */
        $local_id = isset($options['local']) ? $options['local'] : False;
        $outbound_id = isset($options['outbound']) ? $options['outbound'] : False;
        $is_detailed = isset($options['is_detailed']) ? filter_var($options['is_detailed'], FILTER_VALIDATE_BOOLEAN) : False;

        if (!$local_id && !$outbound_id) {
            return False;
        }
        if (!$this->database_ops->get_local_db_status()) {
            return False;
        }

        // Get CDR data;
        $cdr_data = $this->database_ops->get_cdr();
        if (!$cdr_data) {
            return False;
        }

        $local_detail_data = array(
            'total' => 0
        );
        $outbound_detail_data = array(
            'total' => 0
        );

        foreach ($cdr_data as $cdr_line) {
            if ($local_id) {
                list($destination, $call_cost) = $this->get_info_local($cdr_line, $local_id);
                $local_detail_data['total'] += (float) $call_cost;
                if ($is_detailed) {
                    if (array_key_exists($destination, $local_detail_data)) {
                        $local_detail_data[$destination] += $call_cost;
                    } else {
                        $local_detail_data[$destination] = $call_cost;
                    }
                }
            }
            if ($outbound_id) {
                list($destination, $call_cost) = $this->get_info_outbound($cdr_line, $outbound_id);
                $outbound_detail_data['total'] += (float) $call_cost;
                if ($is_detailed) {
                    if (array_key_exists($destination, $outbound_detail_data)) {
                        $outbound_detail_data[$destination] += $call_cost;
                    } else {
                        $outbound_detail_data[$destination] = $call_cost;
                    }
                }

            }
        } // Foreach end
        
        return [$local_detail_data, $outbound_detail_data];

    }
}
?>