<?php

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
        $insert = $insertComand . $insertValues;
        //if (!empty($dados[0][0])) {
        //    $insertComand = $insertComand . "WERE id = " . $dados[0][0];
        //}
        return $insert;
    }

    $response = array();
    $dados = array();
    
    if (!empty($_POST)) {
        /* Tem um problema aqui, essa função precisa filtrar
        caso aja a superglobal $_FILES, caso a superglobal 
        não exista o codigo deve ser executado normalmente,
        caso ela exista e passe pelo isset ela deve ser adicionada
        ao array de dados, caso não passe pelo isset o codigo
        deve parar e retornar que um item está faltando*/
        if (!empty($_FILES)) {
            //Codigo para imagem do professor
            $imageFileType = strtolower(pathinfo(basename($_FILES["img"]["name"]),PATHINFO_EXTENSION));
            $image_base64 = base64_encode(file_get_contents($_FILES['img']['tmp_name']) );
            $img = 'data:image/'.$imageFileType.';base64,'.$image_base64;
            $dados = array_merge($dados, array($count => array("img", $img)));
        }

        $con = pg_connect(getenv("DATABASE_URL"));
        $insert = makeInsert();
        $result = pg_query($con, $insert);

        
        // Daqui pra baixo o codigo foi 100% copiado do professor
        if ($result) {
            // Se o produto foi inserido corretamente no servidor, o cliente 
            // recebe a chave "success" com valor 1
            $response["success"] = 1;
            $response["message"] = "Produto criado com sucesso $insert";
            
            // Fecha a conexao com o BD
            pg_close($con);
     
            // Converte a resposta para o formato JSON.
            echo json_encode($response);
        } else {
            // Se o produto nao foi inserido corretamente no servidor, o cliente 
            // recebe a chave "success" com valor 0. A chave "message" indica o 
            // motivo da falha.
            $response["success"] = 0;
            $response["message"] = "Erro ao criar produto no BD $insert";
            
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
        $response["message"] = "Campo requerido nao preenchido $insert";
     
        // Converte a resposta para o formato JSON.
        echo json_encode($response);
    }
?>