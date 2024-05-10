<?php

$shorturl_domain = "https://go-fau.test.rrze.fau.de"; // Domain on which the plugin rrze-shorturl runs

try {
    $response = file_get_contents($shorturl_domain . "/wp-json/short-url/v1/services");
    if ($response === false) {
        throw new Exception("Failed to fetch short URLs from the REST API endpoint.");
    }

    $services = json_decode($response, true);
    if ($services === null) {
        throw new Exception("Failed to decode JSON response.");
    }


    $code = htmlspecialchars($_GET["code"]);
    $prefix = (int) $code[0];
    $encrypted = substr($code, 1);

    $ShortURLModChars = "abcdefghijklmnopqrstuvwxyz0123456789-";

    foreach ($services as $service) {
        if ($service["prefix"] == $prefix) {
            $service_link = $service["regex"];

            $decrypted = '';

            $len = strlen("abcdefghijklmnopqrstuvwxyz0123456789-");

            for ($i = 0; $i < strlen($encrypted); $i++) {
                $index = strpos($ShortURLModChars, $encrypted[$i]);

                $adjustedIndex = ($index + 1) % $len;

                $decrypted .= $adjustedIndex;
            }

            $redirect_url = preg_replace('/\$\w+/', $decrypted, $service_link);

            header('Location: ' . $redirect_url);
            exit;


            // try {
            //     $data = http_build_query(array('encrypted' => $encrypted));

            //     $options = array(
            //         'https' => array(
            //             'method' => 'POST',
            //             'header' => 'Content-type: application/x-www-form-urlencoded',
            //             'content' => $data
            //         )
            //     );

            //     $context = stream_context_create($options);

            //     $response = file_get_contents($shorturl_domain . "/wp-json/short-url/v1/service-decrypt", false, $context);


            //     if ($response === false) {
            //         $error = error_get_last();
            //         throw new Exception("Failed to decrypt from the REST API endpoint. Error: " . $error['message']);
            //     }

            //     $decrypted = json_decode($response, true);
            //     if ($decrypted === null) {
            //         throw new Exception("Failed to decode JSON response.");
            //     }

            //     // we don't know what the var is named, like f.e. $id or $param, ...
            //     $redirect_url = preg_replace('/\$\w+/', $decrypted, $service_link);

            //     echo $redirect_url;

            //     header('Location: ' . $redirect_url);
            //     exit;
            // } catch (Exception $e) {
            //     echo "Error: " . $e->getMessage();
            // }

            break;
        }
    }

    echo "Unknown service with prefix $prefix";
    exit;

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>