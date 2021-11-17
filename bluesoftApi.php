<?php
    
    require_once "util.php";

    check_superglobal_params("GET", ["cod_barras"]);

    $cod_barras = trim($_GET["cod_barras"]);
    $url = "https://api.cosmos.bluesoft.com.br/gtins/$cod_barras.json";
    $agent = "Cosmos-API-Request";
    $headers = array(
    "Content-Type: application/json",
    "X-Cosmos-Token: bJ2x_WUhAmLnawr0dI50Mw"
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
        exit_with_error_response("Query error: $resultError");
    } else {
        $object = json_decode($data);
        output_json_response(1, $object);
    }

    curl_close($curl);
?>