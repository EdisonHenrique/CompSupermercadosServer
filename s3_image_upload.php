<?php

    function compress_to_jpeg(string $source, int $quality, int $scaleWidth=null) {
        switch (mime_content_type($source)) {
            case 'image/jpeg':
                $imageData = imagecreatefromjpeg($source);
                break;
            case 'image/gif':
                $imageData = imagecreatefromgif($source);
                break;
            case 'image/png':
                $imageData = imagecreatefrompng($source);
                break;
            case 'image/bmp':
                $imageData = imagecreatefrombmp($source);
                break;
        }

        if ($scaleWidth) $imageData = imagescale($imageData, $scaleWidth);

        $exif = @exif_read_data($source);
        if (!empty($exif['Orientation'])) {
            switch ($exif['Orientation']) {
                case 3:
                    $imageData = imagerotate($imageData, -180, 0);
                    break;
                case 6:
                    $imageData = imagerotate($imageData, -90, 0);
                    break;
                case 8:
                    $imageData = imagerotate($imageData, 90, 0);
                    break;
            }
        }
        
        ob_start();
            imagejpeg($imageData, null, $quality);
            $compressedImageData = ob_get_contents();
        ob_end_clean();

        return $compressedImageData;
    }

    require_once "util.php";
    require('vendor/autoload.php');

    // this will simply read AWS_ACCESS_KEY_ID and AWS_SECRET_ACCESS_KEY from env vars
    $s3 = new Aws\S3\S3Client([
        'version'  => 'latest',
        'region'   => getenv('S3_BUCKET_REGION'),
    ]);
    $bucket = getenv('S3_BUCKET')?: die('No "S3_BUCKET" config var in found in env!');

    $allowedTypes = [
        "image/jpeg",
        "image/png",
        "image/bmp",
        "image/gif"
    ];

    check_superglobal_params("POST", ["itemId"]);

    if (
        $_SERVER['REQUEST_METHOD'] == 'POST' 
        && isset($_FILES['userfile'])
        && $_FILES['userfile']['error'] == UPLOAD_ERR_OK 
        && is_uploaded_file($_FILES['userfile']['tmp_name'])
    ) {
        // Obtenção de alguns dados do item, para verificação e nomeação do arquivo enviado
        $query = "
            SELECT cod_barras
                 , imagem_url
            FROM item
            INNER JOIN produto ON item.id_produto = produto.id
            WHERE item.id = {$_POST['itemId']}
        ";
        $resultObject = run_query($query);
        if (!$resultObject) {
            throw_exception_response("Couldn't find specified item or it's invalid");
        } 
        
        $currentImageUrl = $resultObject[0]["imagem_url"];
        $barcode = $resultObject[0]["cod_barras"];

        // Obtenção de informações do arquivo recebido
        $userfilename = $_FILES['userfile']['tmp_name'];
        $usermime = mime_content_type($userfilename);
        
        // Encerra se o arquivo recebido não é uma imagem
        if (!in_array($usermime, $allowedTypes)) {
            throw_exception_response("Disallowed file type '$usermime'. Only images are allowed.");
        }
        
        // Converte/reescala/comprime a imagem recebida para JPEG
        try {
            $compressedImage = compress_to_jpeg($userfilename, 80, 500);
        } catch (Exception $e) {
            throw_exception_response("Failed to compress image as JPEG");
        }
        
        // Upload para o bucket do Amazon S3
        try {
            $upload = $s3->upload(
                $bucket, 
                "$barcode.jpeg", // name of uploaded file
                $compressedImage, // image resource object
                'public-read', 
                array('params' => array('ContentType' => 'image/jpeg'))
            );
            
            $uploadUrl = $upload->get('ObjectURL') . "?time_uploaded=" . time();
            
            $query = "
                UPDATE produto
                SET imagem_url = '$uploadUrl'
                WHERE cod_barras = '$barcode'
            ";
            
            run_query($query);
        } 
        catch (Exception $e) {
            throw_exception_response("Failed to upload image to Amazon S3");
        }

        finish_with_json_response(1, "Image uploaded and product updated successfully");        
    }

    else {
        throw_exception_response("Invalid or missing image file. Error code: " . var_or_default($_FILES['userfile']['error'], "none given") );
    }

?> 
