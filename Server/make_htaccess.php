<?php

# Run make_htaccess.php one time
# It generates one rule: redirect all URL with path starting with a number to shorturl-redirect.php
# shorturl-redirect.php handles all redirects and updates .htaccess if needed

$htaccess_file = '.htaccess';

try {
    $rules = "RewriteEngine On\n";
    $rules .= "RewriteBase /\n";
    // first rule: redirect all paths that start with a number and end with "+" to shorturl-redirect.php with preview = 1
    $rules .= "RewriteRule ^([0-9]+)(.*)\+$ shorturl-redirect.php?prefix=$1&code=$2&preview=1 [L]\n";
    // second rule: redirect all paths that start with a number but not 1 to shorturl-redirect.php (1 == customer domain) 
    $rules .= "RewriteRule ^([2-9][0-9]*)(.*)$ shorturl-redirect.php?prefix=$1&code=$2 [L]\n";
    // last two rule: redirect shorturl-redirect.php to find out if new customer rule or unknown link
    $rules .= "RewriteRule ^1(.+)$ shorturl-redirect.php?prefix=1&code=$1 [L]\n";
    $rules .= "RewriteRule ^(.+)$ shorturl-redirect.php?prefix=1&code=$1 [L]\n";

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