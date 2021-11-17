<?php
    
	require_once "util.php";
		
	function require_id() {
		check_superglobal_params("GET", ["id"]);
		return $_GET["id"];
	}

	// ------------------------------------------------- //

	check_superglobal_params("GET", ["queryType"]);
	
	// Determine SQL query
	switch( $_GET["queryType"] ) {

		default:
			exit_with_error_response("Invalid query name");
			
		case "supermarketItems":
			$id = require_id();
			$query = "
				SELECT produto.id
					 , produto.nome
					 , preco_atual
					 , imagem 
				FROM item
				INNER JOIN produto ON item.id_produto = produto.id
				INNER JOIN supermercado ON item.id_supermercado = supermercado.id
				WHERE supermercado.id = $id
			";
			break;
		
		case "userInfo":
			$id = require_id();
			$query = "
				SELECT nome
					 , data_nascimento
					 , email 
				FROM usuario
				WHERE id = $id
			";
			break;

		case 'verifyLogin':
			check_superglobal_params("SERVER", ['PHP_AUTH_USER', 'PHP_AUTH_PW']);
			$query = "
				SELECT *
				FROM usuario
				WHERE email = '" . $_SERVER['PHP_AUTH_USER'] . "' 
				AND senha = '" . $_SERVER['PHP_AUTH_PW'] . "'
			";
			break;
	}

	// Attempt server connection
	$conn = pg_connect(getenv("DATABASE_URL"));
	if (!$conn){
		exit_with_error_response("Server connection failed");
	}
	
	// Run SQL query
	$result = pg_query($conn, $query);
	
	// Close server connection 
	pg_close($conn);

	// Echo query result/error JSON
	if (!$result) {
		$resultError = pg_result_error($result);
		exit_with_error_response("Query error: $resultError");
	}
	else {
		$resultData = pg_fetch_all($result);
		if ($resultData) {
			output_json_response(1, $resultData);
		} 
		else {
			output_json_response(0, "Query returned no results");
		}
	}
	
?>