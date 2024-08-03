<?php

namespace Fabrikage\QR\Database;

class Tables
{
    public static function create(): void
    {
        // Create for all sites in the network
        if (is_multisite()) {
            global $wpdb;

            $blog_ids = $wpdb->get_col("SELECT blog_id FROM $wpdb->blogs");
            foreach ($blog_ids as $blog_id) {
                switch_to_blog($blog_id);
                self::createTable();
                restore_current_blog();
            }
        } else {
            self::createTable();
        }
    }

    public static function drop(): void
    {
        // Drop for all sites in the network
        if (is_multisite()) {
            global $wpdb;

            $blog_ids = $wpdb->get_col("SELECT blog_id FROM $wpdb->blogs");
            foreach ($blog_ids as $blog_id) {
                switch_to_blog($blog_id);
                self::dropTable();
                restore_current_blog();
            }
        } else {
            self::dropTable();
        }
    }

    private static function dropTable(): void
    {
        global $wpdb;

        $table_name = $wpdb->prefix . 'qr_code_redirects';
        $wpdb->query("DROP TABLE IF EXISTS $table_name");
    }

    private static function createTable(): void
    {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();
        $table_name = $wpdb->prefix . 'qr_code_redirects';

        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            post_id bigint(20) NOT NULL,
            created_at datetime NOT NULL,
            ip_address varchar(255) NOT NULL,
            user_agent varchar(255) NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}
