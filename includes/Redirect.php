<?php

namespace RRZE\ShortURL;

class Redirect
{
    // generate_redirect_rules returns an array with Redirect instructions
    public static function generate_redirect_rules() {
        global $wpdb;
    
        try {
            // Get current date
            $current_date = current_time('mysql');
    
            // Query for active links with valid_until in the future or NULL
            $query = $wpdb->prepare("
                SELECT long_shorturl, short_url
                FROM {$wpdb->prefix}shorturl_links
                WHERE is_active = 1 AND (valid_until > %s OR valid_until IS NULL)
            ", $current_date);
    
            $results = $wpdb->get_results($query);
    
            // Initialize an empty array to store redirect rules
            $redirect_rules = array();
    
            // Generate redirect rules for each active link
            foreach ($results as $result) {
                // Construct the redirect rule
                $redirect_rule = "Redirect 302 {$result->long_shorturl} {$result->short_url}";
                // Add the rule to the array
                $redirect_rules[] = $redirect_rule;
            }
    
            // Return the array of redirect rules
            return $redirect_rules;
        } catch (Exception $e) {
            // Handle exceptions
            error_log("Error generating redirect rules: " . $e->getMessage());
            return array(); // Return an empty array in case of error
        }
    }   
}
