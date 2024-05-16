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



class ShortURLRedirect
{
    private string $shorturl_domain;
    private string $htaccess_file;
    private string $services_file;
    private string $baseChars;
    private string $base;

    public function __construct(string $shorturl_domain, string $htaccess_file, string $services_file)
    {
        $this->shorturl_domain = $shorturl_domain;
        $this->htaccess_file = $htaccess_file;
        $this->services_file = $services_file;
        $this->baseChars = '-abcdefghijklmnopqrstuvwxyz0123456789';
        $this->base = strlen($this->baseChars);
    }

    public function handleRequest(): void
    {
        $code = (!empty($_GET["code"]) ? htmlspecialchars($_GET["code"]) : '');
        $prefix = (!empty($_GET["prefix"]) ? (int)htmlspecialchars($_GET["prefix"]) : 0);

        if ($prefix == 0) {
            $this->send404Response("Unknown service with prefix $prefix");
        }elseif (empty($code)) {
            $this->send404Response("Unknown link");
        } elseif ($prefix == 1) {
            $this->handleCustomerLink($code);
        } else {
            $this->handleServiceLink($code, $prefix);
        }
    }

    private function send404Response(string $message): void
    {
        http_response_code(404);
        echo $message;
        exit;
    }

    private function handleCustomerLink(string $code): void
    {
        try {
            $response = file_get_contents($this->shorturl_domain . "/wp-json/short-url/v1/get-longurl?shortURL=" . $code);
            if ($response === false) {
                throw new Exception("Failed to fetch short URLs from the REST API endpoint.");
            }

            if (!empty($response)) {
                header('Location: ' . $response, true, 303);
                $this->updateHtaccess();
            }
        } catch (Exception $e) {
            echo "Error: " . $e->getMessage();
        }
    }

    private function handleServiceLink(string $code, int $prefix): void
    {
        $service_link = $this->getServiceRegEx($prefix);
        $decrypted = $this->getDecrypted($code);
        $redirect_url = preg_replace('/\$\w+/', $decrypted, $service_link);
        header('Location: ' . $redirect_url, true, 303);
        exit;
    }

    private function getServiceRegEx(int $prefix): string
    {
        $ret = '';
        try {
            session_start();
            // Check if $_SESSION exists and return RegEx
            if (isset($_SESSION['rrze-shorturl-services'])) {
                $aServices = json_decode($_SESSION['rrze-shorturl-services'], true);
                if (!empty($aServices[$prefix])) {
                    return $aServices[$prefix];
                }
            }
    
            // Check if $service_file exists, save it in SESSION and return RegEx
            if (file_exists($this->services_file)) {
                $response = file_get_contents($this->services_file);
                if ($response === false) {
                    throw new Exception("Failed to fetch short URLs from the REST API endpoint.");
                }
                $_SESSION['rrze-shorturl-services'] = $response;
                $aServices = json_decode($response, true);
                if (!empty($aServices[$prefix])) {
                    return $aServices[$prefix];
                }
            } else {
                try {
                    // Fetch update from REST-API, save it in SESSION and $service_file and return RegEx
                    $response = file_get_contents($this->shorturl_domain . "/wp-json/short-url/v1/services");
                    if ($response === false) {
                        throw new Exception("Failed to fetch short URLs from the REST API endpoint.");
                    }
                    $_SESSION['rrze-shorturl-services'] = $response;
                    $aServices = json_decode($response, true);
                    $result = file_put_contents($this->services_file, $response);
                    if ($result === false) {
                        throw new Exception("Error writing file: $this->services_file");
                    }
                    if (!empty($aServices[$prefix])) {
                        return $aServices[$prefix];
                    }
                } catch (Exception $e) {
                    echo "Error: " . $e->getMessage();
                }
            }
        } catch (Exception $e) {
            error_log("Error in getServicesArray: " . $e->getMessage());
            return null;
        }
    
        // unknown Service => send 404
        http_response_code(404);
        echo "Unknown service with prefix $prefix";
        exit;
        }

    private function getDecrypted(string $code): int
    {
        if (!preg_match('/^[-a-z0-9]+$/i', $code)) {
            throw new InvalidArgumentException("Invalid code: $code");
        }
    
        $result = 0;
        $len = strlen($code) - 1;
    
        for ($t = 0; $t <= $len; $t++) {
            $result = $result + strpos($this->baseChars, substr($code, $t, 1)) * pow($this->base, $len - $t);
        }
    
        return $result;
        }

    private function get_rules()
        {
            $ret = '';
            try {
                $response = file_get_contents($this->shorturl_domain . "/wp-json/short-url/v1/active-shorturls");
                if ($response === false) {
                    throw new Exception("Failed to fetch short URLs from the REST API endpoint.");
                }
        
                $short_urls = json_decode($response, true);
                if ($short_urls === null) {
                    throw new Exception("Failed to decode JSON response.");
                }
        
                // Generate RewriteRules
                foreach ($short_urls as $url) {
                    $short_url = trim(parse_url(esc_url_raw($url['short_url']), PHP_URL_PATH), '/');
                    $long_url = esc_url_raw($url['long_url']);
        
        
                    // $expires = ($url['valid_until']) ? date('D, d M Y H:i:s', strtotime($url['valid_until'])) . ' GMT' : ''; # spaces lead to an error
                    // $rules .= "RewriteRule ^$short_url$ $long_url [R=303,L,E=set_expires:1]\n";
                    // $rules .= "Header set Expires $expires env=set_expires\n";
                    $ret .= "RewriteRule ^$short_url$ $long_url [R=303,L]\n";
                }
            } catch (Exception $e) {
                echo "Error: " . $e->getMessage();
            }
        
            return $ret;
        
        }
        

    private function updateHtaccess(): void
    {
        $new_rules = $this->get_rules();

        if (!empty($new_rules)) {
            $rules = "RewriteEngine On\n";
            $rules .= "RewriteBase /\n";
            // first rule: redirect all paths that start with a number but not 1 to redirect-services.php (1 == customer domain) 
            $rules .= "RewriteRule ^([2-9][0-9]*)(.*)$ redirect-services.php?prefix=$1&code=$2 [L]";
            // list of customer rules
            $rules .= $new_rules;
            // last two rules: redirect redirect-services.php to find out if new service or new customer rule or unknown prefix
            $rules .= "RewriteRule ^1(.+)$ redirect-services.php?prefix=1&code=$1 [L]\n";
            $rules .= "RewriteRule ^([^0-9].+)$ redirect-services.php?prefix=0&code=$1 [L]\n";
    
            // Read .htaccess content
            $htaccess_content = file_get_contents($this->htaccess_file);
            if ($htaccess_content === false) {
                throw new Exception("Failed to read .htaccess file.");
            }
    
            // Delete existing rules between markers
            $marker_start = "# BEGIN ShortURL\n";
            $marker_end = "# END ShortURL\n";
            $pattern = '/' . preg_quote($marker_start, '/') . '.*?' . preg_quote($marker_end, '/') . '/s';
            $htaccess_content = preg_replace($pattern, '', $htaccess_content);
    
            // Generate new rules section
            $new_rules_section = $marker_start . $rules . $marker_end;
    
            $htaccess_content = $new_rules_section . $htaccess_content;
    
            // Save .htaccess content
            $result = file_put_contents($this->htaccess_file, $htaccess_content);
            if ($result === false) {
                throw new Exception("Error writing file: $this->htaccess_file");
            }
    
            echo nl2br($htaccess_content);
    
            if ($result === false) {
                throw new Exception("Failed to save .htaccess file.");
            }
        }
        }
}

// SETTINGS
$shorturl_domain = "https://go-fau.test.rrze.fau.de";
$htaccess_file = '.htaccess';
$services_file = 'rrze-shorturl-services.json';

// Instantiate and execute the class
$shortURLRedirect = new ShortURLRedirect($shorturl_domain, $htaccess_file, $services_file);
$shortURLRedirect->handleRequest();
exit;

?>