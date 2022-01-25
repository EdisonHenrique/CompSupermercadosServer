<?php
    require_once "util.php";

    function makeWhere() {
        $whereConditions = $_POST["where"];
        $where = " WHERE ";
        foreach ($whereConditions as $column => $value) {
            if (!is_numeric($value)) {
                $value = "'$value'";
            }
            $where .= $column . " = " . $value . " AND ";
        }
        $where = rtrim($where, " AND ");
        return $where;
    }

    function makeUpdate(array $columnValues) {
        $update = "UPDATE " . trim($_POST["table"]);

        $set = " SET ";
        foreach ($columnValues as $column => $value) {
            if (!is_numeric($value)) {
                $value = "'$value'";
            }
            $set .= "$column = $value, ";
        }
        $set = rtrim($set, ", ");

        $where = makeWhere();

        $query = $update . $set . $where;

        return $query;
    }

    
    // Create the parameters array
    $requiredParams = ["table", "where"];
    $columnValues = array();
    foreach ($_POST as $key => $value) {
        if (!in_array($key, $requiredParams, true)) {
            $requiredParams[] = $key;
            $columnValues[$key] = trim($value);
        }
    }

    check_superglobal_params("POST", $requiredParams);

    $query = makeUpdate($columnValues);
    run_query($query); // em caso de erros, a própria função para o script

	finish_with_json_response(1, "Table updated successfully");

?>