<?php

namespace RRZE\ShortURL;

class Redirect
{
    public function __construct()
    {
        if (!wp_next_scheduled('rrze_shorturl_make_htaccess')) {
            wp_schedule_event(time(), 'hourly', 'rrze_shorturl_make_htaccess');
        }
        add_action('rrze_shorturl_make_htaccess', array($this, 'make_htaccess'));
    }

    public function make_htaccess()
    {
        try {
            // Define the path to the .htaccess file
            $htaccess_file = ABSPATH . '.htaccess';
    
            // Read the content of the .htaccess file
            $content = file_get_contents($htaccess_file);
    
            // Define markers
            $begin_marker = '# BEGIN ShortURL';
            $end_marker = '# END ShortURL';
    
            // Find positions of markers
            $begin_pos = strpos($content, $begin_marker);
            $end_pos = strpos($content, $end_marker);
    
            // If both markers are found, delete everything between them
            if ($begin_pos !== false && $end_pos !== false) {
                // Remove everything between the markers
                $content = substr_replace($content, '', $begin_pos, $end_pos - $begin_pos + strlen($end_marker));
            }
    
            // Generate redirect rules
            $redirect_rules = $begin_marker . "\n" . $this->generate_redirect_rules() . "\n" . $end_marker . "\n";
    
            // Add the new rules and markers at the beginning of the file
            $new_content = $redirect_rules . $content;
    
            // Write the updated content back to the .htaccess file
            file_put_contents($htaccess_file, $new_content);
        } catch (Exception $e) {
            error_log('Error generating .htaccess ' . $e->getMessage());
        }
    }
    
    private function generate_redirect_rules()
    {
        global $wpdb;

        try {
            $table_name = $wpdb->prefix . 'shorturl_links';
            $results = $wpdb->get_results(
                "SELECT long_url, short_url, valid_until FROM $table_name WHERE active = 1 AND (valid_until IS NULL OR valid_until > CURDATE())"
            );

            if (empty ($results)) {
                return '';
            }

            $rules = '';

            foreach ($results as $result) {
                $long_url = esc_url_raw($result->long_url);
                $short_url = esc_url_raw($result->short_url);
                $valid_until = $result->valid_until;

                if (!$long_url || !$short_url) {
                    throw new Exception('Invalid URL found in database.');
                }

                // Add Expires header based on valid_until field
                $expires = ($valid_until) ? date('D, d M Y H:i:s', strtotime($valid_until)) . ' GMT' : '';
                $rules .= "RewriteRule ^$short_url$ $long_url [R=303,L,E=expires:$expires]\n";
            }

            return $rules;
        } catch (Exception $e) {
            // Handle exceptions, e.g., log error message
            error_log('Error generating redirect rules: ' . $e->getMessage());
            return '';
        }
    }
}
