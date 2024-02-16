<?php

namespace RRZE\ShortURL;

class Redirect
{
    public static function getActiveShortURLs(): array
    {
        global $wpdb;

        try {
            $currentTimestamp = current_time('mysql', 1);

            // Prepare the SQL query
            $query = $wpdb->prepare(
                "SELECT long_url, short_url FROM {$wpdb->prefix}shorturl_links WHERE active = TRUE AND (valid_until IS NULL OR valid_until >= %s)",
                $currentTimestamp
            );

            // Execute the query
            $results = $wpdb->get_results($query, ARRAY_A);

            if ($results === null) {
                throw new \Exception("Error fetching active short URLs from the database.");
            }

            return $results;
        } catch (\Exception $e) {
            // Handle the exception
            error_log("Error in getActiveShortURLs: " . $e->getMessage());
            return [];
        }
    }
}
