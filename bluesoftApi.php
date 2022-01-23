<?php
    
    require_once "util.php";

    function get_specified_item_id($barcode, $supermarketId) {
        $query = "
            SELECT item.id
            FROM item
            INNER JOIN produto ON item.id_produto = produto.id
            WHERE cod_barras = '$barcode'
            AND id_supermercado = $supermarketId
        ";

        $resultData = run_query($query);

        if ($resultData) {
            $resultData = [
                "itemId" => $resultData[0]["id"]
            ];
            finish_with_json_response(1, $resultData);
            exit;
        } 
        
        else return false;
    }

    check_superglobal_params("POST", ["barcode", "supermarketId"]);

    $barcode = $_POST["barcode"];
    $supermarketId = $_POST["supermarketId"];

    // Se a tentativa de obter o item especificado falhar
    if ( !get_specified_item_id($barcode, $supermarketId) ) {
        /*
            INICIO DO REQUEST À API BLUESOFT COSMOS
        */
        $url = "https://api.cosmos.bluesoft.com.br/gtins/$barcode.json";
        $agent = "Cosmos-API-Request";
        $headers = array(
            "Content-Type: application/json",
            "X-Cosmos-Token: uCT7hFrsRUDdIA4JD9U26A"
        );
        
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_USERAGENT, $agent);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_FAILONERROR, true);
        
        $data = curl_exec($curl);
        
        if ($data === false || $data == NULL) {
            $resultError = curl_error($curl);
            throw_exception_response("Bluesoft Cosmos query error: $resultError");
        }
        
        curl_close($curl);
        /*  
        FIM DO REQUEST À API
        */
        
        $object = json_decode($data, true); // Array assoc. de dados retornados pela API  
        
        // Obtenção do nome do produto
        $productName = var_or_default($object['description']);
        
        // Obtenção da imagem
        $productImageUrl = var_or_default($object['thumbnail']);
        if ($productImageUrl == "") {
            $productImageUrl = var_or_default($object['brand']['picture']); // se não tem foto, pega foto da marca
        }
        if ($productImageUrl == "") {
            $productImageUrl = "https://comp-supermercados.s3.sa-east-1.amazonaws.com/default.jpeg"; // se não tem foto da marca, coloca default
        }
        
        // Obtenção do preço médio
        $itemAvgPrice = var_or_default($object['avg_price'], 0); // se avg_price vazio, seta como 0


        // Criação de novo produto no BD.
        // O valor '1' é o tipo_produto padrão, por enquanto
        // TODO: criar lógica para definir o tipo_produto a partir dos dados da API
        $query = "
            INSERT INTO produto (cod_barras, nome, imagem_url, id_tipo_produto)
            VALUES ( 
                '$barcode', 
                '$productName', 
                '$productImageUrl', 
                1
            )
        ";
        $possibleInsertError = run_query($query, $returnErrorInsteadOfExiting=true);


        // Obtenção do id do produto em questão
        $query = "
            SELECT id 
            FROM produto
            WHERE cod_barras = '$barcode'
        ";
        $resultData = run_query($query);

        $productId = $resultData[0]["id"];

        if ($productId == "" or $productId == NULL) { // $possibleInsertError confirmado
            throw_exception_response($possibleInsertError);
        }
    

        // Criação do novo item de supermercado no BD
        $query = "
            INSERT INTO item (preco_atual, data_alter_preco, id_supermercado, id_produto)
            VALUES (
                $itemAvgPrice,
                now(),
                $supermarketId,
                $productId
            )
        ";
        run_query($query);
        

        // Ao fim desse processo, é para termos criado um novo produto e/ou item,
        // então buscamos por ele para que seja retornado no response.
        get_specified_item_id($barcode, $supermarketId);        
    }

    ?>