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
			throw_exception_response("Invalid query name");
			
		case "supermarketItems":
			$id = require_id();
			$query = "
				SELECT produto.id
					 , produto.nome
					 , preco_atual
					 , imagem_url
				FROM item
				INNER JOIN produto ON item.id_produto = produto.id
				INNER JOIN supermercado ON item.id_supermercado = supermercado.id
				WHERE supermercado.id = $id
			";
			break;

		case "supermarketInfo":
			$id = require_id();
			$query = "
				SELECT * 
				FROM supermercado
				WHERE id = $id
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

		case "verifyLogin":
			check_superglobal_params("SERVER", ['PHP_AUTH_USER', 'PHP_AUTH_PW']);
			$query = "
				SELECT *
				FROM usuario
				WHERE email = '" . $_SERVER['PHP_AUTH_USER'] . "' 
				AND senha = '" . $_SERVER['PHP_AUTH_PW'] . "'
			";
			break;

		case "itemInfo":
			$id = require_id();
			$query = "
				SELECT nome
				 	 , cod_barras
					 , preco_atual
					 , imagem_url
				FROM item
				INNER JOIN produto ON item.id_produto = produto.id
				WHERE item.id = $id
			";
			break;

		case "nearestSupermarkets":
			$id = require_id();
			$km = 1/111;
			$query = "
				SELECT id, nome
				FROM supermercado
				WHERE localizacao <-> point$id <= 1 * $km
				ORDER BY localizacao <-> point$id
			"; #Pra aumentar a range de busca basta aumentar o número de Km 
			break;
		
		case "lastId":
			$id = require_id();
			$query = "
				SELECT MAX(id)
				FROM $id
			";
			break;

		case "cartsHistory":
			$id = require_id();
			$query = "
				SELECT carrinho.id
					 , nome 
					 , data 
					 , SUM(carrinho_item.quantidade) as quantidade
					 , SUM(item.preco_atual * carrinho_item.quantidade) as total
				FROM carrinho
				INNER JOIN carrinho_item ON carrinho.id = carrinho_item.id_carrinho
				INNER JOIN item ON carrinho_item.id_item = item.id
				WHERE id_usuario = $id
				GROUP BY carrinho.id
			";
			break;
	}

	$resultData = run_query($query);

	if ($resultData) {
		finish_with_json_response(1, $resultData);
	} 
	else {
		finish_with_json_response(0, "Query returned no results");
	}
	
?>