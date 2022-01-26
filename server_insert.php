<?php

    require_once "util.php";

    function generate_insert_query(array $columnValues) {
        $insertCommand = "INSERT INTO " . trim($_POST["table"]) . " (";
        $insertValues = " VALUES (";
        foreach ($columnValues as $column => $value) {
            if (!is_numeric($value)) {
                $value = "'$value'";
            }
            $insertCommand .= "$column, ";
            $insertValues .= "$value, ";
        } 
        $insertCommand = rtrim($insertCommand, ", ") . ")";
        $insertValues = rtrim($insertValues, ", ") . ")";

        $query = $insertCommand . $insertValues;

        return $query;
    }


    // Create the parameters array
    $requiredParams = ["table"];
    $columnValues = array();
    foreach ($_POST as $key => $value) {
        if (!in_array($key, $requiredParams, true)) {
            $requiredParams[] = $key;
            $columnValues[$key] = trim($value);
        }
    }
    
    check_superglobal_params("POST", $requiredParams);

    $query = generate_insert_query($columnValues);
    run_query($query); // em caso de erros, a própria função para o script

	finish_with_json_response(1, "Row inserted successfully");
?>