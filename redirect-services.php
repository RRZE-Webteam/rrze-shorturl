<?php

// 9wv0h => 1196144


$shorturl_domain = "https://go-fau.test.rrze.fau.de"; // Domain on which the plugin rrze-shorturl runs
function decryptBase37(string $encrypted): int {
    $ShortURLModChars = "abcdefghijklmnopqrstuvwxyz0123456789-";
    $len = strlen($ShortURLModChars); // Basis 37

    $decrypted = 0;

    for ($i = 0; $i < strlen($encrypted); $i++) {
        $char = $encrypted[$i];
        $index = strpos($ShortURLModChars, $char);

        if ($index === false) {
            throw new Exception("Invalid character in encrypted string.");
        }

        // Die Basis-37-Zahl wird in eine Dezimalzahl umgewandelt
        $decrypted = $decrypted * $len + $index;
    }

    return $decrypted;
}

echo "test<br>";
$encrypted = "ba";
echo decryptBase37($encrypted); // Ausgabe: 75
exit;

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

            $decrypted = decryptBase37($encrypted);

            echo '$decrypted = ' . $decrypted;
            exit;

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