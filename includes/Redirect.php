<?php

namespace RRZE\ShortURL;

class Redirect
{
    public static function generate_redirect_rules() {
        global $wpdb;
    
        try {
            $table_name = $wpdb->prefix . 'shorturl_links';
            $results = $wpdb->get_results(
                "SELECT long_url, short_url FROM $table_name WHERE active = 1 AND (valid_until IS NULL OR valid_until > NOW())"
            );
    
            if (empty($results)) {
                return '';
            }
    
            $rules = '';
    
            foreach ($results as $result) {
                $long_url = esc_url_raw($result->long_url);
                $short_url = esc_url_raw($result->short_url);
    
                if (!$long_url || !$short_url) {
                    throw new Exception('Invalid URL found in database.');
                }
    
                $rules .= "RewriteRule ^$short_url$ $long_url [R=303,L]\n";
            }
    
            return $rules;
        } catch (Exception $e) {
            // Handle exceptions, e.g., log error message
            error_log('Error generating redirect rules: ' . $e->getMessage());
            return '';
        }
    }
    
}
