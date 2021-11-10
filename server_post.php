<?php

    require_once "util.php";

    // Create the parameters array
    $parameters = array();
    foreach ($_POST as $key => $value) {
        array_push($parameters, $key);
    }
    
    check_request_params("POST", $parameters);

    function makeInsert() {
        $insertComand = "INSERT INTO " . $_POST["table"] . "(";
        $insertValues = "VALUES (";
        foreach ($_POST as $key => $value) {
            if ($key != "table") {
                $insertComand .= $key . ", ";
                $insertValues .= "'$value', ";
            }
        } 
        $insertComand = substr_replace($insertComand,") ", strlen($insertComand) -2);
        $insertValues = substr_replace($insertValues,")", strlen($insertValues) -2);
        $query = $insertComand . $insertValues;
        //if (!empty($dados[0][0])) {
        //    $insertComand = $insertComand . "WERE id = " . $dados[0][0];
        //}
        return $query;
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

    // Attempt server connection
    $conn = pg_connect(getenv("DATABASE_URL"));
    if (!$conn){
		exit_with_error_response("Server connection failed");
	}

    // Unique exception for usuario
    if ($_POST["table"] == "usuario") {
        $email = $_POST["email"];
        $usuario = pg_query($con, "SELECT email FROM usuario WHERE email = '$email'");
        if (pg_num_rows($usuario) > 0) {
            output_json_response(0, "Usuario já cadastrado.");
        }
    }

    // Create SQL query
    $query = makeInsert();
    
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