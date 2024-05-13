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

    foreach ($services as $service) {
        if ($service["prefix"] == $prefix) {
            $service_link = $service["regex"];

            try {
                $decrypted = file_get_contents($shorturl_domain . '/wp-json/short-url/v1/decrypt?encrypted=' . $encrypted);
                if ($decrypted === false) {
                    throw new Exception("Failed to decrypt using from the REST API endpoint.");
                }
            } catch (Exception $e) {
                echo "Error: " . $e->getMessage();
            }

            $redirect_url = preg_replace('/\$\w+/', $decrypted, $service_link);

            header('Location: ' . $redirect_url);
            exit;
        }
    }

    echo "Unknown service with prefix $prefix";
    exit;

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>