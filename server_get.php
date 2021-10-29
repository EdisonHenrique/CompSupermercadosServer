<?php
    
    $response = array();

    if (!empty($_GET)) {
        $selectCommand = "SELECT " . $_GET["select"] . "FROM " . $_GET["from"];

        if (!empty($_GET["where"])) {
            $selectCommand .= "WHERE " . $_GET["where"];
        }

        $con = pg_connect(getenv("DATABASE_URL"));
        $result = pg_query($con, $selectCommand);
        //Codigo copiado do professor
        if (!empty($result)) {
            if (pg_num_rows($result) > 0) {
     
                // Se o produto existe, os dados de detalhe do produto 
                // sao adicionados no array de resposta.
                $result = pg_fetch_array($result);
     
                //Foreach criado para dinamização da inserção das chaves
                //e valores dentro do array de consulta que vai ser retornado
                $consult = array();
                foreach ($result as $key => $value) {
                    $consult[$key] = $value;
                }
                
                // Caso o produto exista no BD, o cliente 
                // recebe a chave "success" com valor 1.
                $response["success"] = 1;
     
                $response["product"] = array();
     
                // Converte a resposta para o formato JSON.
                array_push($response["consult"], $consult);
                
                // Fecha a conexao com o BD
                pg_close($con);
     
                // Converte a resposta para o formato JSON.
                echo json_encode($response);
            } else {
                // Caso o produto nao exista no BD, o cliente 
                // recebe a chave "success" com valor 0. A chave "message" indica o 
                // motivo da falha.
                $response["success"] = 0;
                $response["message"] = "Produto não encontrado";
                
                // Fecha a conexao com o BD
                pg_close($con);
     
                // Converte a resposta para o formato JSON.
                echo json_encode($response);
            }
        } else {
            // Caso o produto nao exista no BD, o cliente 
            // recebe a chave "success" com valor 0. A chave "message" indica o 
            // motivo da falha.
            $response["success"] = 0;
            $response["message"] = "Produto não encontrado";
     
            // Fecha a conexao com o BD
            pg_close($con);
     
            // Converte a resposta para o formato JSON.
            echo json_encode($response);
        }
    } else {
        // Se a requisicao foi feita incorretamente, ou seja, os parametros 
        // nao foram enviados corretamente para o servidor, o cliente 
        // recebe a chave "success" com valor 0. A chave "message" indica o 
        // motivo da falha.
        $response["success"] = 0;
        $response["message"] = "Campo requerido não preenchido";
     
        // Converte a resposta para o formato JSON.
        echo json_encode($response);
    }
?>