<?php

# Run make_htaccess.php one time
# It generates one rule: redirect all URL with path starting with a number to shorturl-redirect.php
# shorturl-redirect.php handles all redirects and updates .htaccess if needed

$htaccess_file = '.htaccess';

try {
    $rules = "RewriteEngine On\n";
    $rules .= "RewriteBase /\n";
    $rules .= "RewriteRule ^([2-9][0-9]*)(.*)$ shorturl-redirect.php?prefix=$1&code=$2 [L]";

    // Read .htaccess content
    $htaccess_content = file_get_contents($htaccess_file);
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
    $result = file_put_contents($htaccess_file, $htaccess_content);

    echo nl2br($htaccess_content);

    if ($result === false) {
        throw new Exception("Failed to save .htaccess file.");
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>