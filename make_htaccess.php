<?php
$shorturl_domain = "https://go-fau.test.rrze.fau.de"; // Domain on which the plugin rrze-shorturl runs

try {
    $response = file_get_contents($domain . "/wp-json/short-url/v1/active-short-urls");
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
        $short_url = esc_url_raw($url['short_url']);
        $long_url = esc_url_raw($url['long_url']);
        $expires = ($url['valid_until']) ? date('D, d M Y H:i:s', strtotime($url['valid_until'])) . ' GMT' : '';

        $rules .= "RewriteRule ^$short_url$ $long_url [R=303,L,E=expires:$expires]\n";
    }

    // Read .htaccess content
    $htaccess_file = '.htaccess';
    $htaccess_content = file_get_contents($htaccess_file);
    if ($htaccess_content === false) {
        throw new Exception("Failed to read .htaccess file.");
    }

    // Delete existing rules including markers
    $marker_start = "# BEGIN ShortURL\n";
    $marker_end = "# END ShortURL\n";
    $pattern = '/' . preg_quote($marker_start, '/') . '.*?' . preg_quote($marker_end, '/') . '/s';
    $htaccess_content = preg_replace($pattern, '', $htaccess_content);
    if ($htaccess_content === null) {
        throw new Exception("Failed to delete existing rules between markers.");
    }

    // Insert new rules between markers
    $htaccess_content = $marker_start . $rules . $marker_end . $htaccess_content;
    if ($htaccess_content === false) {
        throw new Exception("Failed to insert new rules between markers.");
    }

    // Save .htaccess content
    $result = file_put_contents($htaccess_file, $htaccess_content);
    if ($result === false) {
        throw new Exception("Failed to save .htaccess file.");
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
