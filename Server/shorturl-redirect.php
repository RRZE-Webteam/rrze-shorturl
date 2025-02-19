<?php

// How shorturl-redirect.php works

// call with mandatory GET-Parameters "code" and "prefix"
// Exception handling: 404 with Message

// A) if prefix is Service
// Get Regex to decrypt "code":
// if Regex in Session => 303 Redirect
// elseif in $this->services_file save in Session then => 303 Redirect
// elseif via REST-API (/services) save in Session, save in $this->services_file then => 303 Redirect
// else 404 "Service with prefix not found"

// A) if prefix is Customer Link
// get long_url from REST-API (/active-shorturls)
// then => 303 Redirect 
// then save new .htaccess 

// SETTINGS

// LIVE ! 
$shorturl_domain = "https://www.shorturl.rrze.fau.de";
$redirect_domain = "https://go.fau.de";

// TEST !
// $shorturl_domain = "https://go-fau.test.rrze.fau.de";
// $redirect_domain = "https://go-fau.test.rrze.fau.de";

$htaccess_file = '.htaccess';
$services_file = 'rrze-shorturl-services.json';




class ShortURLRedirect
{
    private string $shorturl_domain;
    private string $redirect_domain;
    private string $htaccess_file;
    private string $services_file;
    private string $baseChars;
    private string $base;

    public function __construct(string $shorturl_domain, string $redirect_domain, string $htaccess_file, string $services_file)
    {
        unset($_SESSION['rrze-shorturl-services']);
        $this->shorturl_domain = $shorturl_domain;
        $this->redirect_domain = $redirect_domain;
        $this->htaccess_file = $htaccess_file;
        $this->services_file = $services_file;
        $this->baseChars = '-abcdefghijklmnopqrstuvwxyz0123456789';
        $this->base = strlen($this->baseChars);
    }

    public function handleRequest(): void
    {
        // Log all server and incoming parameters
        // error_log('Incoming SERVER parameters: ' . print_r($_SERVER, true));
        // error_log('Incoming GET parameters: ' . print_r($_GET, true));

        // Sanitize and retrieve parameters
        $code = (!empty($_GET["code"]) ? htmlspecialchars($_GET["code"]) : '');
        $prefix = (!empty($_GET["prefix"]) ? (int) htmlspecialchars($_GET["prefix"]) : 0);
        $preview = (!empty($_GET["preview"]) ? (int) htmlspecialchars($_GET["preview"]) : 0);

        // Log the extracted parameters
        // error_log("Extracted parameters - code: $code, prefix: $prefix, preview: $preview");

        if (empty($code)) {
            $this->send404Response("Unknown link. No code given.");
        } elseif ($prefix == 1 || $prefix == 7) {
            $code = $prefix . $code;
            // error_log('handleRequest() $code = ' . $code);

            $this->handleCustomerLink($code, $preview);
        } else {
            $this->handleServiceLink($code, $prefix, $preview);
        }
    }

    private function send404Response(string $message): void
    {
        http_response_code(404);
        echo $message;
        exit;
    }

    private function handleCustomerLink(string $code, int $preview): void
    {

        try {
            $url = $this->shorturl_domain . "/wp-json/wp/v2/shorturl/get-longurl?code=" . $code;
            $response = $this->fetchUrl($url);

            // error_log('handleCustomerLink() $url = ' . $url);

            if ($response === false) {
                throw new Exception("Failed to fetch from the REST API endpoint get-longurl.");
            }

            $JSONresponse = json_decode($response, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception("JSON decoding error: " . json_last_error_msg());
            }

            // Log the API response for debugging
            // error_log(print_r($JSONresponse, true));

            if (!empty($JSONresponse['long_url'])) {
                $longUrl = $JSONresponse['long_url'];

                // Log the long URL for debugging
                // error_log("Long URL: " . $longUrl);

                if ($preview) {
                    $short_url = $this->redirect_domain . '/' . $code;
                    $this->showPreview($short_url, $longUrl);
                } else {
                    $this->updateHtaccess();
                    header('Location: ' . $longUrl, true, 303);
                    exit;
                }
            } else {
                $this->send404Response("Unknown link");
            }
        } catch (Exception $e) {
            echo "Error: " . $e->getMessage();
        }
    }

    private function handleServiceLink(string $code, int $prefix, int $preview): void
    {
        $service_link = $this->getServiceRegEx($prefix);
        $decrypted = $this->getDecrypted($code);
        $long_url = preg_replace('/\$\w+/', $decrypted, $service_link);
        if ($preview) {
            $short_url = $this->redirect_domain . '/' . $prefix . $code;
            $this->showPreview($short_url, $long_url);
        } else {
            header('Location: ' . $long_url, true, 303);
            exit;
        }
    }

    private function showPreview(string $short_url, string $long_url): void
    {
        echo '<!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Short-URL Preview</title>
        </head>
        <body>
        <br><br><br>
            <center>
                <a href="' . htmlspecialchars($short_url, ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($short_url, ENT_QUOTES, 'UTF-8') . '</a>
                <br><br>redirects to / wird weitergeleitet zu<br><br>
                <a href="' . htmlspecialchars($long_url, ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($long_url, ENT_QUOTES, 'UTF-8') . '</a>
            </center>
        </body>
        </html>';
    }

    private function fetchUrl(string $url): string
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);

        if ($response === false) {
            throw new Exception("cURL Error: " . curl_error($ch));
        }
        curl_close($ch);

        return $response;
    }

    private function getRegexFromServiceArray(int &$prefix, array &$aServices): string
    {
        foreach ($aServices as $service) {
            if ($service['prefix'] == $prefix) {
                return $service['regex'];
            }
        }

        return '';
    }

    private function getServiceRegEx(int $prefix): string
    {
        $ret = '';
        try {
            session_start();
            // Check if $_SESSION exists and return RegEx
            if (isset($_SESSION['rrze-shorturl-services'])) {
                $aServices = json_decode($_SESSION['rrze-shorturl-services'], true);

                if (!is_array($aServices)) {
                    throw new Exception('$aServices must be an array');
                }

                $regEx = $this->getRegexFromServiceArray($prefix, $aServices);

                if (!empty($regEx)) {
                    return $regEx;
                }
            }

            // Check if $service_file exists, save it in SESSION and return RegEx
            if (file_exists($this->services_file)) {
                $response = file_get_contents($this->services_file);

                if ($response === false) {
                    // we ignore that the file doesn't exist. It will be created later => file_put_contents()
                }

                $_SESSION['rrze-shorturl-services'] = $response;
                $aServices = json_decode($response, true);

                if (!is_array($aServices)) {
                    throw new Exception('$aServices must be an array');
                }

                $regEx = $this->getRegexFromServiceArray($prefix, $aServices);

                if (!empty($regEx)) {
                    return $regEx;
                }
            }

            // Fetch update from REST-API, save it in SESSION and $service_file and return RegEx
            try {
                $response = $this->fetchUrl($this->shorturl_domain . "/wp-json/wp/v2/shorturl/services");

                $_SESSION['rrze-shorturl-services'] = $response;
                $aServices = json_decode($response, true);

                if (!is_array($aServices)) {
                    throw new Exception('$aServices must be an array');
                }

                $result = file_put_contents($this->services_file, $response);

                if ($result === false) {
                    throw new Exception("Error writing file: $this->services_file");
                }

                $regEx = $this->getRegexFromServiceArray($prefix, $aServices);

                if (!empty($regEx)) {
                    return $regEx;
                }
            } catch (Exception $e) {
                echo "Error: " . $e->getMessage();
            }
        } catch (Exception $e) {
            error_log("Error in getServicesArray: " . $e->getMessage());
            return null;
        }

        // unknown Service => send 404
        http_response_code(404);
        echo " getServiceRegEx() Unknown service with prefix $prefix";
        exit;
    }

    private function getDecrypted(string $code): int
    {
        if (!preg_match('/^[-a-z0-9]+$/i', $code)) {
            throw new InvalidArgumentException("Invalid code: $code");
        }

        $result = '0';
        $len = strlen($code) - 1;

        for ($t = 0; $t <= $len; $t++) {
            $char = substr($code, $t, 1);
            $pos = strpos($this->baseChars, $char);
            $power = bcpow($this->base, $len - $t);
            $value = bcmul($pos, $power);
            $result = bcadd($result, $value);
        }

        return (int) $result;
    }

    private function get_rules()
    {
        $ret = '';
        try {
            $response = $this->fetchUrl($this->shorturl_domain . "/wp-json/wp/v2/shorturl/active-shorturls");

            $short_urls = json_decode($response, true);
            if ($short_urls === null) {
                throw new Exception("Failed to decode JSON response.");
            }

            if (!is_array($short_urls)) {
                throw new Exception("active-shorturls didn't send an array");
            }

            // Generate RewriteRules
            foreach ($short_urls as $url) {
                if (!empty($url['shorturl_custom'])){
                        $parsedUrl = parse_url($url['shorturl_custom']);
                        $short_url_path = trim($parsedUrl['path']);
                        $long_url = $url['long_url'];
                        if (empty($short_url_path)) {
                                continue;
                        }

                    $ret .= "RewriteRule ^$short_url_path$ $long_url [R=303,L,NE]\n";
                }

            }
        } catch (Exception $e) {
            echo "Error: " . $e->getMessage();
        }

        return $ret;

    }

    private function updateHtaccess(): void
    {
        // error_log("we are about to write the .htaccess");

        $new_rules = $this->get_rules();

        if (!empty($new_rules)) {
            $rules = "RewriteEngine On\n";
            $rules .= "RewriteBase /\n";
            // Debugging rules
            $rules .= "RewriteCond %{REQUEST_URI} .\n";
            $rules .= "RewriteRule ^ - [E=DEBUG_LOG:%{REQUEST_URI}]\n";
            $rules .= "RewriteCond %{QUERY_STRING} .\n";
            $rules .= "RewriteRule ^ - [E=DEBUG_LOG:%{QUERY_STRING}]\n";
            // First rule: redirect all paths that start with a number or not (not = a customer URI) and end with "+" to shorturl-redirect.php with preview = 1
            $rules .= "RewriteRule ^([0-9]*)?(.*)\\+$ shorturl-redirect.php?prefix=\$1&code=\$2&preview=1 [L]\n";
            // Second rule: redirect all paths that start with a number but not 1 to shorturl-redirect.php (1 == customer domain)
            $rules .= "RewriteRule ^([2-9][0-9]*)(.*)$ shorturl-redirect.php?prefix=\$1&code=\$2 [L]\n";
            // List of customer rules
            $rules .= $new_rules;
            // Next-to-last rule: redirect shorturl-redirect.php to find out if new customer rule (not custom URI)
            $rules .= "RewriteRule ^1(.+)$ shorturl-redirect.php?prefix=1&code=\$1 [L]\n";
            // Last rule: redirect shorturl-redirect.php to find out if new customer rule with custom URI or unknown link
            // Check if $1 is not equal to "shorturl-redirect.php"
            $rules .= "RewriteCond %{REQUEST_URI} !^/shorturl-redirect\.php$\n";
            $rules .= "RewriteCond %{THE_REQUEST} !shorturl-redirect\.php [NC]\n";
            // Redirect to shorturl-redirect.php with prefix=0 and code=$1
            $rules .= "RewriteRule ^(.+)$ shorturl-redirect.php?prefix=0&code=\$1 [L]\n";

            // Read .htaccess content
            $htaccess_content = file_get_contents($this->htaccess_file);
            if ($htaccess_content === false) {
                throw new Exception("Failed to read .htaccess file.");
            }

            // Delete existing rules between markers
            $marker_start = "# ShortURL BEGIN\n";
            $marker_end = "# ShortURL END\n";
            $timestamp = '# ' . date('Y-m-d H:i:s') . " \n";
            $pattern = '/' . preg_quote($marker_start, '/') . '.*?' . preg_quote($marker_end, '/') . '/s';
            $htaccess_content = preg_replace($pattern, '', $htaccess_content);

            // Generate new rules section
            $new_rules_section = $marker_start . $timestamp . $rules . $marker_end;

            $htaccess_content = $new_rules_section . $htaccess_content;

            // Save .htaccess content
            $result = file_put_contents($this->htaccess_file, $htaccess_content);
            if ($result === false) {
                throw new Exception("Error writing file: $this->htaccess_file");
            }

            // echo nl2br($htaccess_content);

            if ($result === false) {
                throw new Exception("Failed to save .htaccess file.");
            }
        }
    }

}

// Instantiate and execute the class
$shortURLRedirect = new ShortURLRedirect($shorturl_domain, $redirect_domain, $htaccess_file, $services_file);
$shortURLRedirect->handleRequest();
exit;

?>
