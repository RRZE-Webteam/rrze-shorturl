<?php

$htaccess_file = '.htaccess';

try {
    $rules = "RewriteEngine On\n";
    $rules .= "RewriteBase /\n";
    // Debugging rules
    $rules .= "RewriteCond %{REQUEST_URI} .\n";
    $rules .= "RewriteRule ^ - [E=DEBUG_LOG:%{REQUEST_URI}]\n";
    $rules .= "RewriteCond %{QUERY_STRING} .\n";
    $rules .= "RewriteRule ^ - [E=DEBUG_LOG:%{QUERY_STRING}]\n";
    // First rule: redirect all paths that start with a number and end with "+" to shorturl-redirect.php with preview = 1
    $rules .= "RewriteRule ^([0-9]+)(.*)\\+$ shorturl-redirect.php?prefix=\$1&shorturlcode=\$2&preview=1 [L]\n";
    // Second rule: redirect all paths that start with a number but not 1 to shorturl-redirect.php (1 == customer domain)
    $rules .= "RewriteRule ^([2-9][0-9]*)(.*)$ shorturl-redirect.php?prefix=\$1&shorturlcode=\$2 [L]\n";
    // Next-to-last rule: redirect shorturl-redirect.php to find out if new customer rule (not custom URI)
    $rules .= "RewriteRule ^1(.+)$ shorturl-redirect.php?prefix=1&shorturlcode=\$1 [L]\n";
    // Last rule: redirect shorturl-redirect.php to find out if new customer rule with custom URI or unknown link
    // Check if $1 is not equal to "shorturl-redirect.php"
    $rules .= "RewriteCond %{REQUEST_URI} !^/shorturl-redirect\.php$\n";
    $rules .= "RewriteCond %{THE_REQUEST} !shorturl-redirect\.php [NC]\n";
    // Redirect to shorturl-redirect.php with prefix=0 and code=$1
    $rules .= "RewriteRule ^(.+)$ shorturl-redirect.php?prefix=0&shorturlcode=\$1 [L]\n";

    // Check if .htaccess file exists
    if (!file_exists($htaccess_file)) {
        // Create an empty .htaccess file
        if (file_put_contents($htaccess_file, '') === false) {
            throw new Exception("Failed to create .htaccess file.");
        }
    }

    // Read .htaccess content
    $htaccess_content = file_get_contents($htaccess_file);
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
    $result = file_put_contents($htaccess_file, $htaccess_content);

    echo nl2br($htaccess_content);

    if ($result === false) {
        throw new Exception("Failed to save .htaccess file.");
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
