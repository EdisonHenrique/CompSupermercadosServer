<?php
    require_once "util.php";

    function makeUpdate() {
        $update = "UPDATE " . trim($_POST["table"]);
        $set = " SET";
        foreach ($_POST as $key => $value) {
            if ($key != "table" or $key != "where") {
                $value = trim($value);
                $set .= " $key = '$value',";
            }
        }
        $set = substr_replace($set," ", strlen($set) -1);
        $where = "WHERE id = " . $_POST["id"];
        $query = $update . $set . $where;
        return $query;
    }

    // Create the parameters array
    $parameters = array();
    foreach ($_POST as $key => $value) {
        array_push($parameters, $key);
    }
    
    check_superglobal_params("POST", $parameters);

    $query = makeUpdate();

    // Attempt server connection
    $conn = pg_connect(getenv("DATABASE_URL"));
    if (!$conn){
		exit_with_error_response("Server connection failed");
	}
    
    // Run SQL query
	$result = pg_query($conn, $query);

    // Close server connection 
	pg_close($conn);

    if (!$result) {
		$resultError = pg_result_error($result);
		exit_with_error_response("Query error: $resultError");
	}
	else {
		output_json_response(1, $_POST["table"] . "criado com suceesso.");
	}
?>