<?php
    
    require_once "util.php";

    function run_query(string $query, bool $ignoreErrors=false) {
        // Attempt server connection
        $conn = pg_connect(getenv("DATABASE_URL"));
        if (!$conn){
            exit_with_error_response("Server connection failed");
        }

        // Run SQL query
        $result = pg_query($conn, $query);
        // Close server connection 
        pg_close($conn);

        if (!$result and !$ignoreErrors) {
            //$resultError = pg_result_error($result);
            //exit_with_error_response("Query error: $resultError");
            $query = str_replace("\r\n", "", $query);
            exit_with_error_response("Query error: $query");
        }

        return pg_fetch_all($result);
    }

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
            output_json_response(1, $resultData);
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
        curl_close($curl);
        
        if ($data === false || $data == NULL) {
            $resultError = curl_error($curl);
            exit_with_error_response("Bluesoft Cosmos query error: $resultError");
        }
        /*  
            FIM DO REQUEST À API
        */
        
        $object = json_decode($data, true); // Array assoc. de dados retornados pela API   


        // Criação de novo produto no BD.
        // O valor '1' é o tipo_produto padrão, por enquanto
        // TODO: criar lógica para definir o tipo_produto a partir dos dados da API
        $query = "
            INSERT INTO produto (cod_barras, nome, imagem, id_tipo_produto)
            VALUES ( 
                '$barcode', 
                '{$object['description']}', 
                '{$object['thumbnail']}', 
                1
            )
        ";
        // Em teoria, o único erro que pode dar é se já existir o cod_barras especificado,
        // o que é o comportamento esperado. Por isso, sem tratamento de erro aqui.
        run_query($query, $ignoreErrors=true);


        // Obtenção do id do produto em questão
        $query = "
            SELECT id 
            FROM produto
            WHERE cod_barras = '$barcode'
        ";
        $resultData = run_query($query);

        $productId = $resultData[0]["id"];
    

        // Criação do novo item de supermercado no BD
        $query = "
            INSERT INTO item (preco_atual, data_alter_preco, id_supermercado, id_produto)
            VALUES (
                {$object['avg_price']},
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