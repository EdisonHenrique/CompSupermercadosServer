<?php

    $consult_bd_url = $_SERVER["SERVER_NAME"] . "/server_select.php?queryType=itemInfo&id=1";

    $curl = curl_init($consult_bd_url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_FAILONERROR, true);

    $data = curl_exec($curl);
    curl_close($curl);

    $object = json_decode($data);

    echo var_dump($object);
    echo var_dump($consult_bd_url);
    echo $_SERVER["SERVER_NAME"];

?>