<?php

    require_once "util.php";

    function makeInsert(array $columnValues) {
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
        $query = str_replace("\r\n", "", $query);

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

    $query = makeInsert($columnValues);

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
		$resultError = pg_result_error($result);
		exit_with_error_response($error);
	}
	else {
		output_json_response(1, "Row inserted successfully");
	}

    /* Tem um problema aqui, essa função precisa filtrar
    caso aja a superglobal $_FILES, caso a superglobal 
    não exista o codigo deve ser executado normalmente,
    caso ela exista e passe pelo isset ela deve ser adicionada
    ao array de dados, caso não passe pelo isset o codigo
    deve parar e retornar que um item está faltando
    if (!empty($_FILES)) {
        //Codigo para imagem do professor
        $imageFileType = strtolower(pathinfo(basename($_FILES["img"]["name"]),PATHINFO_EXTENSION));
        $image_base64 = base64_encode(file_get_contents($_FILES['img']['tmp_name']) );
        $img = 'data:image/'.$imageFileType.';base64,'.$image_base64;
        $dados = array_merge($dados, array($count => array("img", $img)));
    }*/
?>