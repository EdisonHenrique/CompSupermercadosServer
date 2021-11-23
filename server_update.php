<?php
    require_once "util.php";

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

        $where = " WHERE id = " . $_POST["id"];

        $query = $update . $set . $where;
        $query = str_replace("\r\n", "", $query);

        return $query;
    }

    
    // Create the parameters array
    $requiredParams = ["table", "id"];
    $columnValues = array();
    foreach ($_POST as $key => $value) {
        if (!in_array($key, $requiredParams, true)) {
            $requiredParams[] = $key;
            $columnValues[$key] = trim($value);
        }
    }

    check_superglobal_params("POST", $requiredParams);

    $query = makeUpdate($columnValues);

    // Attempt server connection
    $conn = pg_connect(getenv("DATABASE_URL"));
    if (!$conn){
		exit_with_error_response("Server connection failed");
	}
    
    // Run SQL query
	$result = pg_query($conn, $query);
    $error = trim(pg_last_error($conn), "\n^ ");

    // Close server connection 
	pg_close($conn);

    if (!$result) {
        exit_with_error_response($error);
	}
	else {
		output_json_response(1, "Table updated successfully");
	}
?>