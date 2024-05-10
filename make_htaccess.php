<?php
$shorturl_domain = "https://go-fau.test.rrze.fau.de"; // Domain on which the plugin rrze-shorturl runs

try {
    $response = file_get_contents($shorturl_domain . "/wp-json/short-url/v1/active-short-urls");
    if ($response === false) {
        throw new Exception("Failed to fetch short URLs from the REST API endpoint.");
    }

    $short_urls = json_decode($response, true);
    if ($short_urls === null) {
        throw new Exception("Failed to decode JSON response.");
    }

    // Generate RewriteRules
    $rules = '';
    foreach ($short_urls as $url) {
        $short_url = trim(parse_url(esc_url_raw($url['short_url']), PHP_URL_PATH), '/');
        $long_url = esc_url_raw($url['long_url']);
        // $expires = ($url['valid_until']) ? date('D, d M Y H:i:s', strtotime($url['valid_until'])) . ' GMT' : ''; # spaces lead to an error
        // $rules .= "RewriteRule ^$short_url$ $long_url [R=303,L,E=set_expires:1]\n";
        // $rules .= "Header set Expires $expires env=set_expires\n";
        $rules .= "RewriteRule ^$short_url$ $long_url [R=303,L]\n";
    }

    if ($rules) {
        // $rules = "<IfModule mod_rewrite.c>\nRewriteEngine On\nRewriteBase /\n" . $rules . "</IfModule>\n";

        // first rule: redirect all paths that start with a number but not 1 to redirect-services.php (1 == customer domain)
        $rules = "RewriteEngine On\nRewriteBase /\nRewriteRule ^([2-9].*)$ redirect-services.php?code=$1 [L]\n" . $rules;

        // Read .htaccess content
        $htaccess_file = '.htaccess';
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

        // echo nl2br($htaccess_content);

        if ($result === false) {
            throw new Exception("Failed to save .htaccess file.");
        }
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>