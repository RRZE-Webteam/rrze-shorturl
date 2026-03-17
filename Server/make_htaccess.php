<?php
$htaccess_file = '.htaccess';

try {
    $rules  = "RewriteEngine On\n";
    $rules .= "RewriteBase /\n";
    $rules .= "Options -MultiViews\n"; // avoid content negotiation interfering with rewrites

    // Debug: expose URI and query to env for logging
    $rules .= "RewriteCond %{REQUEST_URI} .\n";
    $rules .= "RewriteRule ^ - [E=DEBUG_LOG:%{REQUEST_URI}]\n";
    $rules .= "RewriteCond %{QUERY_STRING} .\n";
    $rules .= "RewriteRule ^ - [E=DEBUG_LOG:%{QUERY_STRING}]\n";

    // Preview: optional single-digit prefix; URLs ending with "+"
    $rules .= "RewriteRule ^([0-9])?(.*)\\+$ shorturl-redirect.php?prefix=\$1&code=\$2&preview=1 [END]\n";

    // Services: single-digit prefix 2–9
    $rules .= "RewriteRule ^([2-9])(.*)$ shorturl-redirect.php?prefix=\$1&code=\$2 [END]\n";

    // Customer: prefix 1
    $rules .= "RewriteRule ^1(.+)$ shorturl-redirect.php?prefix=1&code=\$1 [END]\n";

    // Stop if the target is already the PHP endpoint (safety)
    $rules .= "RewriteRule ^shorturl-redirect\\.php$ - [END]\n";

    // Fallback: no prefix → pass to PHP as prefix=0
    $rules .= "RewriteRule ^(.+)$ shorturl-redirect.php?prefix=0&code=\$1 [END]\n";

    // Write managed block to .htaccess
    if (!file_exists($htaccess_file)) {
        if (file_put_contents($htaccess_file, '') === false) {
            throw new Exception("Failed to create .htaccess file.");
        }
    }
    $htaccess_content = file_get_contents($htaccess_file);
    if ($htaccess_content === false) {
        throw new Exception("Failed to read .htaccess file.");
    }

    $marker_start = "# ShortURL BEGIN\n";
    $marker_end   = "# ShortURL END\n";
    $timestamp    = '# ' . date('Y-m-d H:i:s') . " \n";
    $pattern      = '/' . preg_quote($marker_start, '/') . '.*?' . preg_quote($marker_end, '/') . '/s';
    $htaccess_content = preg_replace($pattern, '', $htaccess_content);

    $new_rules_section = $marker_start . $timestamp . $rules . $marker_end;
    $htaccess_content  = $new_rules_section . $htaccess_content;

    $result = file_put_contents($htaccess_file, $htaccess_content);
    echo nl2br($htaccess_content);
    if ($result === false) {
        throw new Exception("Failed to save .htaccess file.");
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
