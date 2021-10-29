<?php

    function makeInsert($dados) {
        $dadosS = $dados[1];
        $insertComand = "INSERT INTO " . $dados[0][1] . "(" . $dadosS[0];
        $insertValues = "VALUES (" . "'$dadosS[1]'";
        for ($i=2; $i < count($dados); $i++) {
            $dadosS = $dados[$i];
            $insertComand .= ", " . $dadosS[0];
            $insertValues .= ", '$dadosS[1]'"; 
        }
        $insertComand .= ") " . $insertValues . ")";
        if (!empty($dados[0][0])) {
            $insertComand = $insertComand . "WERE id = " . $dados[0][0];
        }
        return $insertComand;
    }

    $response = array();
    $dados = array();
    
    if (!empty($_POST)) {
        
        //O foreach abaixo é para que os dados fiquem 
        //dentro de um array que facilite o seu manuseio 
        //para a criação da string sql de forma dinamica
        //É importante que o primeiro item do post siga o padrão: ação no slq:tabela
        $count = 0;
        foreach ($_POST as $key => $value) {
            $dados = array_merge($dados, array($count => array($key, $value)));
            $count++;
        }

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
        $result = pg_query($con, makeInsert($dados));
        
        // Daqui pra baixo o codigo foi 100% copiado do professor
        if ($result) {
            // Se o produto foi inserido corretamente no servidor, o cliente 
            // recebe a chave "success" com valor 1
            $response["success"] = 1;
            $response["message"] = "Produto criado com sucesso";
            
            // Fecha a conexao com o BD
            pg_close($con);
     
            // Converte a resposta para o formato JSON.
            echo json_encode($response);
        } else {
            // Se o produto nao foi inserido corretamente no servidor, o cliente 
            // recebe a chave "success" com valor 0. A chave "message" indica o 
            // motivo da falha.
            $response["success"] = 0;
            $response["message"] = "Erro ao criar produto no BD";
            
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
        $response["message"] = "Campo requerido nao preenchido";
     
        // Converte a resposta para o formato JSON.
        echo json_encode($response);
    }
?>