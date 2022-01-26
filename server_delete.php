<?php
    require_once "util.php";

    function generate_delete_query() {
        $delete = "DELETE FROM " . trim($_POST["table"]);

        $whereConditions = $_POST["where"];
        $where = " WHERE ";
        foreach ($whereConditions as $column => $value) {
            if (!is_numeric($value)) {
                $value = "'$value'";
            }
            $where .= $column . " = " . $value . " AND ";
        }
        $where = rtrim($where, " AND ");

        $query = $delete . $where;

        return $query;
    }

    check_superglobal_params("POST", ["table", "where"]);

    $query = "DELETE FROM " . $_POST["table"] . "WHERE " . $_POST["where_id"] 
                . " = " . $_POST["id"];
    run_query($query);

    finish_with_json_response(1, "Row deleted successfully");
?>