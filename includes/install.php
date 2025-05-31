<?php
/**
 * Create database tables on activation
 */
function mini_crm_install_tables() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();
    $table = $wpdb->prefix . 'mini_crm_contacts';

    $sql = "CREATE TABLE $table (
      id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
      first_name VARCHAR(100) NOT NULL,
      last_name VARCHAR(100) NOT NULL,
      phone VARCHAR(20) NOT NULL,
      status VARCHAR(50) NOT NULL DEFAULT 'new',
      source VARCHAR(50) NOT NULL,
      submitted_at DATETIME NOT NULL,
      PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta( $sql );
}

function mini_crm_deactivate() {
    // Optional: flush rewrite or cleanup
}
?>