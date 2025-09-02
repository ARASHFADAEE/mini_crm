<?php
/*
Plugin Name: پلاگین سی ار ام تهران ایده
Description: پلاگین تخصصی سی ار ام با تنظیمات اختصاصی
Version: 3.1
Author: آرش فدایی
Text Domain: mini-crm
Domain Path: /languages
*/

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

define('MINI_CRM_VERSION', '3.1');
define('MINI_CRM_SETTINGS_SLUG', 'mini_crm_settings_options');

// Load plugin textdomain for internationalization
function mini_crm_load_textdomain() {
    load_plugin_textdomain('mini-crm', false, dirname(plugin_basename(__FILE__)) . '/languages/');
}
add_action('plugins_loaded', 'mini_crm_load_textdomain');

add_action('plugins_loaded', 'mini_crm_create_table');

// Enqueue admin styles and scripts
function mini_crm_enqueue_admin_assets($hook) {
    // Performance: Early exit if user can't access Mini CRM
    if (!mini_crm_user_can_access()) {
        return;
    }
    
    $is_plugin_page = false;
    if (strpos($hook, 'mini-crm') !== false) {
        $is_plugin_page = true;
    } elseif (isset($_GET['page']) && $_GET['page'] === MINI_CRM_SETTINGS_SLUG) {
        $is_plugin_page = true;
    }

    if (!$is_plugin_page) {
        return;
    }

    wp_enqueue_style(
        'mini-crm-admin-style',
        plugin_dir_url(__FILE__) . 'assets/css/admin-style.css',
        [],
        MINI_CRM_VERSION
    );

    // Add Persian Datepicker CSS
    wp_enqueue_style(
        'persian-datepicker-css',
        plugin_dir_url(__FILE__) . 'assets/css/persian-datepicker.css',
        [],
        MINI_CRM_VERSION
    );

    if (isset($_GET['page']) && $_GET['page'] === MINI_CRM_SETTINGS_SLUG) {
        wp_enqueue_script('jquery-ui-accordion');
    }

    // Add Persian Datepicker JS
    wp_enqueue_script(
        'persian-date-js',
        plugin_dir_url(__FILE__) . 'assets/js/persian-date.js',
        ['jquery'],
        MINI_CRM_VERSION,
        true
    );

    wp_enqueue_script(
        'persian-datepicker-js',
        plugin_dir_url(__FILE__) . 'assets/js/persian-datepicker.js',
        ['jquery', 'persian-date-js'],
        MINI_CRM_VERSION,
        true
    );

    wp_enqueue_script(
        'mini-crm-admin-script',
        plugin_dir_url(__FILE__) . 'assets/js/admin-script.js',
        ['jquery', 'jquery-ui-accordion', 'persian-datepicker-js'],
        MINI_CRM_VERSION,
        true
    );

    wp_localize_script('mini-crm-admin-script', 'miniCrmAdminAjax', [
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce_update' => wp_create_nonce('mini_crm_update_contact_details'),
        'nonce_filter' => wp_create_nonce('mini_crm_filter_search_contacts'),
        'nonce_send_manual_sms' => wp_create_nonce('mini_crm_send_manual_sms_action'),
        'nonce_confirm_visit_sms' => wp_create_nonce('mini_crm_confirm_visit_sms'),
        'nonce_increment_call' => wp_create_nonce('mini_crm_increment_call_attempts'),
        'nonce_delete_contact' => wp_create_nonce('mini_crm_delete_contact'),
        'nonce_edit_contact_basic' => wp_create_nonce('mini_crm_edit_contact_basic'),
        'nonce_view_sms_history' => wp_create_nonce('mini_crm_view_sms_history'),
        'nonce_test_email' => wp_create_nonce('mini_crm_test_email_nonce'),
        'text' => [
            'confirm_sms' => __('آیا از ارسال این پیامک مطمئن هستید؟', 'mini-crm'),
            'confirm_visit_sms' => __('آیا از ارسال پیامک تایید قرار بازدید مطمئن هستید؟', 'mini-crm'),
            'confirm_delete' => __('آیا از حذف این مخاطب مطمئن هستید؟ این عمل قابل بازگشت نیست.', 'mini-crm'),
            'sms_sent_success' => __('پیامک با موفقیت ارسال شد.', 'mini-crm'),
            'sms_sent_failed' => __('خطا در ارسال پیامک.', 'mini-crm'),
            'loading' => __('در حال بارگذاری...', 'mini-crm'),
            'error_loading' => __('خطا در بارگذاری اطلاعات.', 'mini-crm'),
            'error_server' => __('خطای ارتباط با سرور.', 'mini-crm'),
            'error_update' => __('خطا در به‌روزرسانی', 'mini-crm'),
            'error_sending_sms' => __('خطای ارتباط با سرور هنگام ارسال پیامک.', 'mini-crm'),
            'sending' => __('در حال ارسال...', 'mini-crm'),
            'deleting' => __('در حال حذف...', 'mini-crm'),
            'editing' => __('در حال ویرایش...', 'mini-crm'),
            'select_date_time' => __('لطفاً تاریخ و ساعت بازدید را انتخاب کنید.', 'mini-crm'),
        ]
    ]);
}
add_action('admin_enqueue_scripts', 'mini_crm_enqueue_admin_assets');


// Enqueue frontend styles and scripts (Optimized)
function mini_crm_enqueue_frontend_styles() {
    // Performance: Only load if form shortcode is present

    
    wp_enqueue_style(
        'mini-crm-frontend-style',
        plugin_dir_url(__FILE__) . 'assets/css/front-style.css',
        [],
        MINI_CRM_VERSION
    );
    wp_enqueue_script(
        'mini-crm-frontend-script',
        plugin_dir_url(__FILE__) . 'assets/js/frontend-script.js',
        ['jquery'],
        MINI_CRM_VERSION,
        true
    );
    wp_localize_script('mini-crm-frontend-script', 'miniCrmAjax', [
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('mini_crm_frontend_form_nonce')
    ]);
}
add_action('wp_enqueue_scripts', 'mini_crm_enqueue_frontend_styles');

// Create database table on plugin activation and handle updates (No changes from previous version)
register_activation_hook(__FILE__, 'mini_crm_create_table_on_activation');
register_deactivation_hook(__FILE__, 'mini_crm_deactivate_plugin');

function mini_crm_create_table_on_activation() {
    mini_crm_create_table();
    mini_crm_add_contact_support_role();
}

function mini_crm_deactivate_plugin() {
    mini_crm_remove_contact_support_role();
}

/**
 * Create database table and handle migrations
 */
function mini_crm_create_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'mini_crm_contacts';
    $sms_log_table = $wpdb->prefix . 'mini_crm_sms_log';
    $charset_collate = $wpdb->get_charset_collate();
    $current_db_version = get_option("mini_crm_db_version");
    $target_db_version = '1.4';

    // Performance: Only run if version is actually different
    if ($current_db_version === $target_db_version) {
        return;
    }

    if ($current_db_version != $target_db_version) {
        // Create contacts table
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            full_name varchar(255) NOT NULL,
            phone varchar(20) NOT NULL,
            status varchar(50) DEFAULT 'registered' NOT NULL,
            channel varchar(50) NOT NULL,
            sub_status varchar(50) DEFAULT '' NOT NULL,
            call_status varchar(50) DEFAULT 'pending' NOT NULL,
            call_attempts int DEFAULT 0 NOT NULL,
            last_call_date datetime,
            call_notes text,
            visit_date varchar(255),
            visit_persian_date varchar(20),
            visit_start_time varchar(10),
            visit_end_time varchar(10),
            visit_note text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id),
            KEY phone_idx (phone),
            KEY status_idx (status),
            KEY call_status_idx (call_status),
            KEY created_at_idx (created_at)
        ) $charset_collate;";
        
        // Create SMS log table
        $sms_sql = "CREATE TABLE $sms_log_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            contact_id mediumint(9) NOT NULL,
            phone varchar(20) NOT NULL,
            sms_type varchar(50) NOT NULL,
            body_id varchar(100) NOT NULL,
            arguments text,
            status varchar(20) DEFAULT 'pending' NOT NULL,
            result_code varchar(100),
            error_message text,
            sent_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id),
            KEY contact_id_idx (contact_id),
            KEY phone_idx (phone),
            KEY status_idx (status),
            KEY sent_at_idx (sent_at),
            FOREIGN KEY (contact_id) REFERENCES $table_name(id) ON DELETE CASCADE
        ) $charset_collate;";
        
        // Create activity log table for debugging
        $activity_log_table = $wpdb->prefix . 'mini_crm_activity_log';
        $activity_sql = "CREATE TABLE $activity_log_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            action varchar(100) NOT NULL,
            contact_id mediumint(9),
            user_id mediumint(9),
            details text,
            ip_address varchar(45),
            user_agent text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id),
            KEY action_idx (action),
            KEY contact_id_idx (contact_id),
            KEY user_id_idx (user_id),
            KEY created_at_idx (created_at)
        ) $charset_collate;";
        
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
        dbDelta($sms_sql);
        dbDelta($activity_sql);
        mini_crm_run_specific_migrations($current_db_version, $target_db_version);
        update_option("mini_crm_db_version", $target_db_version);
    }
    update_option("mini_crm_plugin_version", MINI_CRM_VERSION);
}

/**
 * Force database migration (for manual execution)
 */
function mini_crm_force_migration() {
    // Reset version to force migration
    update_option("mini_crm_db_version", '1.0');
    // Run migration
    mini_crm_create_table();
    return 'Migration completed successfully!';
}

/**
 * AJAX handler for manual migration
 */
function mini_crm_force_migration_ajax() {
    if (!current_user_can('manage_options')) {
        wp_die('Unauthorized');
    }
    
    $result = mini_crm_force_migration();
    wp_send_json_success(['message' => $result]);
}
add_action('wp_ajax_mini_crm_force_migration', 'mini_crm_force_migration_ajax');

/**
 * Add custom user role for Mini CRM
 */
function mini_crm_add_contact_support_role() {
    // Add custom role with Mini CRM capabilities
    add_role(
        'mini_crm_contact_support',
        __('پشتیبان تماس', 'mini-crm'),
        array(
            'read' => true,
            'mini_crm_access' => true,
            'mini_crm_manage_contacts' => true,
            'mini_crm_send_sms' => true,
            'mini_crm_view_settings' => false, // Cannot access settings by default
        )
    );
    
    // Add Mini CRM capabilities to administrator
    $admin_role = get_role('administrator');
    if ($admin_role) {
        $admin_role->add_cap('mini_crm_access');
        $admin_role->add_cap('mini_crm_manage_contacts');
        $admin_role->add_cap('mini_crm_send_sms');
        $admin_role->add_cap('mini_crm_view_settings');
        $admin_role->add_cap('mini_crm_manage_settings');
    }
}

/**
 * Remove custom user role on deactivation
 */
function mini_crm_remove_contact_support_role() {
    remove_role('mini_crm_contact_support');
    
    // Remove Mini CRM capabilities from administrator
    $admin_role = get_role('administrator');
    if ($admin_role) {
        $admin_role->remove_cap('mini_crm_access');
        $admin_role->remove_cap('mini_crm_manage_contacts');
        $admin_role->remove_cap('mini_crm_send_sms');
        $admin_role->remove_cap('mini_crm_view_settings');
        $admin_role->remove_cap('mini_crm_manage_settings');
    }
}

/**
 * Check if current user has Mini CRM access
 */
function mini_crm_user_can_access() {
    return current_user_can('mini_crm_access') || current_user_can('manage_options');
}

/**
 * Check if current user can manage contacts
 */
function mini_crm_user_can_manage_contacts() {
    return current_user_can('mini_crm_manage_contacts') || current_user_can('manage_options');
}

/**
 * Check if current user can send SMS
 */
function mini_crm_user_can_send_sms() {
    return current_user_can('mini_crm_send_sms') || current_user_can('manage_options');
}

/**
 * Check if current user can access settings
 */
function mini_crm_user_can_access_settings() {
    return current_user_can('mini_crm_manage_settings') || current_user_can('manage_options');
}

/**
 * Display role management instructions
 */
function mini_crm_display_role_management_info() {
    if (!mini_crm_user_can_access_settings()) return;
    
    echo '<div class="notice notice-info">';
    echo '<h3>' . __('راهنمای مدیریت دسترسی‌ها', 'mini-crm') . '</h3>';
    echo '<p>' . __('برای دسترسی به Mini CRM، کاربران باید یکی از این نقش‌ها را داشته باشند:', 'mini-crm') . '</p>';
    echo '<ul>';
    echo '<li><strong>' . __('مدیر (Administrator)', 'mini-crm') . '</strong>: ' . __('دسترسی کامل به همه قسمت‌ها', 'mini-crm') . '</li>';
    echo '<li><strong>' . __('پشتیبان تماس', 'mini-crm') . '</strong>: ' . __('دسترسی به مدیریت تماس‌ها و ارسال پیامک (بدون دسترسی به تنظیمات)', 'mini-crm') . '</li>';
    echo '</ul>';
    echo '<p>' . __('برای اختصاص نقش "پشتیبان تماس" به کاربران، به صفحه کاربران وردپرس مراجعه کنید.', 'mini-crm') . '</p>';
    echo '</div>';
}

/**
 * Update role capabilities on plugin update (run only once)
 */
function mini_crm_update_role_capabilities() {
    // Only run if capabilities haven't been updated yet
    if (get_option('mini_crm_roles_updated', false)) {
        return;
    }
    
    $admin_role = get_role('administrator');
    if ($admin_role) {
        $admin_role->add_cap('mini_crm_access');
        $admin_role->add_cap('mini_crm_manage_contacts');
        $admin_role->add_cap('mini_crm_send_sms');
        $admin_role->add_cap('mini_crm_view_settings');
        $admin_role->add_cap('mini_crm_manage_settings');
    }
    
    // Ensure the custom role exists
    if (!get_role('mini_crm_contact_support')) {
        mini_crm_add_contact_support_role();
    }
    
    // Mark as updated to prevent running again
    update_option('mini_crm_roles_updated', true);
}
add_action('init', 'mini_crm_update_role_capabilities');

function mini_crm_run_specific_migrations($old_db_ver, $new_db_ver) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'mini_crm_contacts';
    if (version_compare($old_db_ver, '1.0', '<')) {
        if ($wpdb->get_var("SHOW COLUMNS FROM $table_name LIKE 'source'") && !$wpdb->get_var("SHOW COLUMNS FROM $table_name LIKE 'channel'")) {
            $wpdb->query("ALTER TABLE $table_name CHANGE source channel VARCHAR(50) NOT NULL");
        }
        if ($wpdb->get_var("SHOW COLUMNS FROM $table_name LIKE 'first_name'") && $wpdb->get_var("SHOW COLUMNS FROM $table_name LIKE 'last_name'")) {
            if (!$wpdb->get_var("SHOW COLUMNS FROM $table_name LIKE 'full_name'")) {
                 $wpdb->query("ALTER TABLE $table_name ADD full_name varchar(255) NOT NULL AFTER id");
            }
            $wpdb->query("UPDATE $table_name SET full_name = CONCAT(first_name, ' ', last_name) WHERE (full_name = '' OR full_name IS NULL) AND first_name IS NOT NULL AND last_name IS NOT NULL");
        }
    }
    if (version_compare($old_db_ver, '1.1', '<')) {
        if (!$wpdb->get_var("SHOW COLUMNS FROM $table_name LIKE 'sub_status'")) {
            $wpdb->query("ALTER TABLE $table_name ADD sub_status varchar(50) DEFAULT '' NOT NULL AFTER channel");
        }
    }
     if (version_compare($old_db_ver, '1.2', '<')) {
        if (!$wpdb->get_var("SHOW COLUMNS FROM $table_name LIKE 'visit_end_time'")) {
            $wpdb->query("ALTER TABLE $table_name ADD visit_end_time TIME DEFAULT NULL AFTER visit_date");
        }
         if (!$wpdb->get_var("SHOW COLUMNS FROM $table_name LIKE 'visit_note'")) {
            $wpdb->query("ALTER TABLE $table_name MODIFY visit_note TEXT AFTER visit_end_time");
        }
    }
    
    // Migration for version 1.4 - Add new visit fields
    if (version_compare($old_db_ver, '1.4', '<')) {
        // Change visit_date to varchar
        if ($wpdb->get_var("SHOW COLUMNS FROM $table_name LIKE 'visit_date'")) {
            $wpdb->query("ALTER TABLE $table_name MODIFY visit_date varchar(255)");
        }
        
        // Add visit_persian_date field
        if (!$wpdb->get_var("SHOW COLUMNS FROM $table_name LIKE 'visit_persian_date'")) {
            $wpdb->query("ALTER TABLE $table_name ADD visit_persian_date varchar(20) AFTER visit_date");
        }
        
        // Add visit_start_time field
        if (!$wpdb->get_var("SHOW COLUMNS FROM $table_name LIKE 'visit_start_time'")) {
            $wpdb->query("ALTER TABLE $table_name ADD visit_start_time varchar(10) AFTER visit_persian_date");
        }
        
        // Change visit_end_time to varchar
        if ($wpdb->get_var("SHOW COLUMNS FROM $table_name LIKE 'visit_end_time'")) {
            $wpdb->query("ALTER TABLE $table_name MODIFY visit_end_time varchar(10)");
        }
    }
}

// Shortcode for contact form (No changes from previous version)
function mini_crm_contact_form_shortcode($atts) {
    $atts = shortcode_atts(['channel' => 'website_form'], $atts, 'contact_form');
    ob_start();
    ?>
    <form method="post" action="" class="mini-crm-contact-form" id="mini-crm-contact-form">
        <input type="hidden" name="form_channel" value="<?php echo esc_attr($atts['channel']); ?>">
        <p>
            <label style="color:#ffff !important;" for="mini_crm_full_name"><?php _e('نام و نام خانوادگی:', 'mini-crm'); ?></label>
            <input type="text" name="full_name" id="mini_crm_full_name" required>
        </p>
        <p>
            <label style="color:#ffff !important;" for="mini_crm_phone"><?php _e('شماره تلفن:', 'mini-crm'); ?></label>
            <input type="tel" name="phone" id="mini_crm_phone" pattern="[0-9۰-۹]{10,11}" title="<?php esc_attr_e('شماره تلفن باید 10 یا 11 رقم باشد', 'mini-crm'); ?>" required>
        </p>
        <?php wp_nonce_field('mini_crm_frontend_form_nonce', 'mini_crm_nonce_field'); ?>
        <p>
            <input  style="color:#ffff !important;" type="submit" name="mini_crm_submit" value="<?php esc_attr_e('ثبت و ادامه', 'mini-crm'); ?>">
            <div class="mini-crm-message mini-crm-success" id="mini-crm-success-message" style="display:none;"></div>
            <div class="mini-crm-message mini-crm-error" id="mini-crm-error-message" style="display:none;"></div>
        </p>
    </form>
    <?php
    return ob_get_clean();
}
// Include shortcode functionality
require_once plugin_dir_path(__FILE__) . 'includes/shortcode.php';

// Register shortcodes
add_shortcode('contact_form', 'mini_crm_contact_form_shortcode');
add_shortcode('mini_crm_form', 'mini_crm_render_form');

// Handle shortcode form submission
add_action('init', 'mini_crm_handle_submission');

// Handle form submission (AJAX) - Enhanced with better error handling and logging
function mini_crm_handle_form_submission_ajax() {
    check_ajax_referer('mini_crm_frontend_form_nonce', 'mini_crm_nonce_field');
    global $wpdb;
    $table_name = $wpdb->prefix . 'mini_crm_contacts';
    $full_name = isset($_POST['full_name']) ? sanitize_text_field(trim($_POST['full_name'])) : '';
    $phone = isset($_POST['phone']) ? sanitize_text_field(trim($_POST['phone'])) : '';
    $channel = isset($_POST['form_channel']) ? sanitize_text_field($_POST['form_channel']) : 'website_form';

    // Enhanced validation
    if (empty($full_name) || empty($phone)) {
        error_log("Mini CRM Form Submission Error: Empty fields - Name: '$full_name', Phone: '$phone'");
        wp_send_json_error(['message' => __('نام و شماره تلفن نمی‌توانند خالی باشند.', 'mini-crm')]);
    }
    
    // Convert Persian numbers to English
    $phone = str_replace(['۰','۱','۲','۳','۴','۵','۶','۷','۸','۹'], ['0','1','2','3','4','5','6','7','8','9'], $phone);
    
    // Enhanced phone validation
    if (!preg_match('/^(09|9)[0-9]{9}$/', $phone)) {
        error_log("Mini CRM Form Submission Error: Invalid phone format - Phone: '$phone'");
        wp_send_json_error(['message' => __('شماره تلفن نامعتبر است. باید با 09 یا 9 شروع شود و 11 یا 10 رقم باشد.', 'mini-crm')]);
    }
    
    // Check for duplicate phone numbers
    $existing_contact = $wpdb->get_row($wpdb->prepare("SELECT id FROM $table_name WHERE phone = %s", $phone));
    if ($existing_contact) {
        error_log("Mini CRM Form Submission: Duplicate phone number detected - Phone: '$phone', Existing ID: {$existing_contact->id}");
        wp_send_json_error(['message' => __('این شماره تلفن قبلاً ثبت شده است.', 'mini-crm')]);
    }

    $result = $wpdb->insert($table_name, [
        'full_name' => $full_name,
        'phone' => $phone,
        'status' => 'registered',
        'channel' => $channel,
        'created_at' => current_time('mysql')
    ]);
    
    // Enhanced error logging
    if ($result === false) {
        error_log("Mini CRM Form Submission Error: Database insert failed - Error: " . $wpdb->last_error . " - Data: Name='$full_name', Phone='$phone', Channel='$channel'");
    } else {
        error_log("Mini CRM Form Submission Success: Contact inserted - ID: {$wpdb->insert_id}, Name='$full_name', Phone='$phone', Channel='$channel'");
    }
	
    // ارسال ایمیل به ادمین(ها)
    mini_crm_send_admin_notification_email($full_name, $phone, $channel);

    if ($result !== false) {
        $contact_id = $wpdb->insert_id;
        mini_crm_log_activity('contact_created', $contact_id, ['name' => $full_name, 'phone' => $phone, 'channel' => $channel]);
        mini_crm_trigger_sms_notifications($contact_id, 'form_submission', ['channel' => $channel]);
        wp_send_json_success(['message' => __('اطلاعات شما با موفقیت ثبت شد. به زودی با شما تماس خواهیم گرفت.', 'mini-crm')]);
    } else {
        mini_crm_log_activity('contact_creation_failed', null, ['name' => $full_name, 'phone' => $phone, 'channel' => $channel, 'error' => $wpdb->last_error]);
        wp_send_json_error(['message' => __('خطا در ثبت اطلاعات. لطفاً دوباره تلاش کنید یا با ما تماس بگیرید.', 'mini-crm') . ' ' . $wpdb->last_error]);
    }
}
add_action('wp_ajax_mini_crm_handle_form', 'mini_crm_handle_form_submission_ajax');
add_action('wp_ajax_nopriv_mini_crm_handle_form', 'mini_crm_handle_form_submission_ajax');

/**
 * Send email notification to admin(s) for new contact
 */
function mini_crm_send_admin_notification_email($full_name, $phone, $channel) {
    $settings = mini_crm_get_sms_settings();
    $admin_emails = $settings['admin_emails'] ?? '';
    
    // اگر ایمیل ادمین تنظیم نشده، از ایمیل پیش‌فرض وردپرس استفاده کن
    if (empty($admin_emails)) {
        $admin_emails = get_option('admin_email');
        if (empty($admin_emails)) {
            error_log("Mini CRM: No admin emails configured and WordPress admin email is empty");
            return false;
        }
    }
    
    // Parse multiple emails separated by comma
    $email_list = array_map('trim', explode(',', $admin_emails));
    $email_list = array_filter($email_list, 'is_email'); // Remove invalid emails
    
    if (empty($email_list)) {
        error_log("Mini CRM: No valid admin emails configured. Provided emails: " . $admin_emails);
        return false;
    }
    
    // Create email content
    $message = '
    <html>
    <body style="font-family: Arial, sans-serif; direction: rtl; text-align: right;">
        <h2>ثبت فرم مشاوره رایگان</h2>
        <p>یک فرم جدید در سایت ثبت شده است. جزئیات به شرح زیر است:</p>
        <table style="border-collapse: collapse; width: 100%; max-width: 600px;">
            <tr>
                <td style="border: 1px solid #ddd; padding: 8px; font-weight: bold;">نام کامل:</td>
                <td style="border: 1px solid #ddd; padding: 8px;">' . esc_html($full_name) . '</td>
            </tr>
            <tr>
                <td style="border: 1px solid #ddd; padding: 8px; font-weight: bold;">شماره تلفن:</td>
                <td style="border: 1px solid #ddd; padding: 8px;">' . esc_html($phone) . '</td>
            </tr>
            <tr>
                <td style="border: 1px solid #ddd; padding: 8px; font-weight: bold;">کانال:</td>
                <td style="border: 1px solid #ddd; padding: 8px;">' . esc_html($channel) . '</td>
            </tr>
            <tr>
                <td style="border: 1px solid #ddd; padding: 8px; font-weight: bold;">زمان ثبت:</td>
                <td style="border: 1px solid #ddd; padding: 8px;">' . esc_html(current_time('mysql')) . '</td>
            </tr>
        </table>
        <p>لطفاً با این کاربر تماس بگیرید.</p>
    </body>
    </html>';

    $subject = 'فرم پذیرش مشاوره رایگان';
    $headers = array(
        'Content-Type: text/html; charset=UTF-8',
        'From: تهران ایده <info@tehranidea.com>',
    );

    $success_count = 0;
    $total_emails = count($email_list);
    
    // بررسی تنظیمات ایمیل قبل از ارسال
    $email_config_issues = mini_crm_check_email_configuration();
    if (!empty($email_config_issues)) {
        error_log("Mini CRM: Email configuration issues detected before sending: " . implode(', ', $email_config_issues));
    }
    
    // Send email to each admin with retry mechanism
    foreach ($email_list as $admin_email) {
        $mail_sent = false;
        $retry_count = 0;
        $max_retries = 2;
        
        // تلاش برای ارسال با مکانیزم تکرار
         while (!$mail_sent && $retry_count <= $max_retries) {
             // لاگ کردن جزئیات قبل از ارسال
             if ($retry_count == 0) {
                 mini_crm_log_email_details($admin_email, $subject, $message, $headers);
             }
             
             $mail_sent = wp_mail($admin_email, $subject, $message, $headers);
            
            if (!$mail_sent) {
                $retry_count++;
                if ($retry_count <= $max_retries) {
                    error_log("Mini CRM: Email send attempt $retry_count failed for $admin_email, retrying...");
                    sleep(1); // کمی صبر قبل از تلاش مجدد
                }
            }
        }
        
        if ($mail_sent) {
            $success_count++;
            error_log("Mini CRM: Email sent successfully to $admin_email for contact: $full_name ($phone)" . ($retry_count > 0 ? " (after $retry_count retries)" : ""));
        } else {
            error_log("Mini CRM: Failed to send email to $admin_email for contact: $full_name ($phone) after $max_retries retries");
            
            // لاگ اطلاعات اضافی برای تشخیص مشکل
            global $phpmailer;
            if (isset($phpmailer) && is_object($phpmailer)) {
                error_log("Mini CRM: PHPMailer error info: " . $phpmailer->ErrorInfo);
            }
        }
    }
    
    // Log overall result
    if ($success_count > 0) {
        error_log("Mini CRM: Email notifications sent to $success_count/$total_emails admin(s) for contact: $full_name ($phone)");
        return true;
    } else {
        error_log("Mini CRM: Failed to send email to any admin for contact: $full_name ($phone). Total emails attempted: $total_emails");
        return false;
    }
}

/**
 * بررسی تنظیمات ایمیل وردپرس و نمایش هشدارهای مربوطه
 */
function mini_crm_check_email_configuration() {
    $issues = [];
    
    // بررسی تابع wp_mail
    if (!function_exists('wp_mail')) {
        $issues[] = 'تابع wp_mail در دسترس نیست';
    }
    
    // بررسی ایمیل مدیر
    $admin_email = get_option('admin_email');
    if (empty($admin_email)) {
        $issues[] = 'ایمیل مدیر وردپرس تنظیم نشده است';
    }
    
    // بررسی تنظیمات PHP mail
    if (!ini_get('sendmail_path') && !ini_get('SMTP')) {
        $issues[] = 'تنظیمات PHP mail یا SMTP در سرور پیکربندی نشده';
    }
    
    // بررسی تنظیمات SMTP (اگر پلاگین SMTP نصب باشد)
    $smtp_plugins = [
        'WP Mail SMTP' => class_exists('WPMailSMTP\\Core'),
        'Easy WP SMTP' => class_exists('EasyWPSMTP\\Core'),
        'Post SMTP' => class_exists('PostmanOptions'),
        'WP SMTP' => defined('WPMS_ON')
    ];
    
    $smtp_detected = false;
    foreach ($smtp_plugins as $plugin_name => $is_active) {
        if ($is_active) {
            $smtp_detected = true;
            error_log("Mini CRM: SMTP plugin detected: $plugin_name");
            break;
        }
    }
    
    if (!$smtp_detected) {
        $issues[] = 'پلاگین SMTP تنظیم نشده - ممکن است ایمیل‌ها در اسپم قرار بگیرند یا ارسال نشوند';
    }
    
    // بررسی محدودیت‌های سرور
    $max_execution_time = ini_get('max_execution_time');
    if ($max_execution_time > 0 && $max_execution_time < 30) {
        $issues[] = 'زمان اجرای PHP کم است - ممکن است ارسال ایمیل قطع شود';
    }
    
    // تست ارسال ایمیل ساده (فقط یک بار در روز)
    $last_test = get_option('mini_crm_last_email_test', 0);
    $current_time = time();
    
    // اگر 24 ساعت از آخرین تست گذشته باشد و ایمیل ادمین موجود باشد
    if (!empty($admin_email) && ($current_time - $last_test) > 86400) {
        $test_result = wp_mail($admin_email, 'تست ایمیل Mini CRM', 'این یک ایمیل تست است.', ['Content-Type: text/html; charset=UTF-8']);
        if (!$test_result) {
            $issues[] = 'تست ارسال ایمیل ناموفق بود';
            
            // بررسی خطاهای PHPMailer
            global $phpmailer;
            if (isset($phpmailer) && is_object($phpmailer) && !empty($phpmailer->ErrorInfo)) {
                $issues[] = 'خطای PHPMailer: ' . $phpmailer->ErrorInfo;
            }
        }
        // ذخیره زمان آخرین تست
        update_option('mini_crm_last_email_test', $current_time);
    }
    
    if (!empty($issues)) {
        error_log('Mini CRM: Email configuration issues: ' . implode(', ', $issues));
        return $issues;
    }
    
    return [];
}

/**
 * نمایش هشدار در پنل مدیریت در صورت وجود مشکل در تنظیمات ایمیل
 */
function mini_crm_admin_email_notice() {
    if (!mini_crm_user_can_access()) return;
    
    $issues = mini_crm_check_email_configuration();
    if ($issues !== true && !empty($issues)) {
        echo '<div class="notice notice-warning">';
        echo '<h3>هشدار تنظیمات ایمیل Mini CRM</h3>';
        echo '<p>مشکلات زیر در تنظیمات ایمیل شناسایی شد:</p>';
        echo '<ul>';
        foreach ($issues as $issue) {
            echo '<li>' . esc_html($issue) . '</li>';
        }
        echo '</ul>';
        echo '<p>برای حل این مشکلات، لطفاً:</p>';
        echo '<ol>';
        echo '<li>تنظیمات ایمیل وردپرس را بررسی کنید</li>';
        echo '<li>یک پلاگین SMTP معتبر نصب کنید (مثل WP Mail SMTP)</li>';
        echo '<li>تنظیمات سرور ایمیل را بررسی کنید</li>';
        echo '</ol>';
        echo '</div>';
    }
}
add_action('admin_notices', 'mini_crm_admin_email_notice');

/**
 * Hook برای گرفتن خطاهای wp_mail
 */
function mini_crm_wp_mail_failed($wp_error) {
    error_log('Mini CRM: wp_mail failed with error: ' . $wp_error->get_error_message());
}
add_action('wp_mail_failed', 'mini_crm_wp_mail_failed');

/**
 * AJAX handler برای تست ایمیل
 */
function mini_crm_test_email_ajax() {
    // بررسی nonce
    if (!wp_verify_nonce($_POST['nonce'], 'mini_crm_test_email_nonce')) {
        wp_die('خطای امنیتی');
    }
    
    // بررسی دسترسی
    if (!current_user_can('manage_options')) {
        wp_die('عدم دسترسی');
    }
    
    $settings = mini_crm_get_sms_settings();
    $admin_emails = $settings['admin_emails'] ?? '';
    
    if (empty($admin_emails)) {
        $admin_emails = get_option('admin_email');
    }
    
    if (empty($admin_emails)) {
        wp_send_json_error('هیچ ایمیل ادمینی تنظیم نشده است.');
        return;
    }
    
    // تست ارسال ایمیل
    $test_subject = 'تست ایمیل Mini CRM - ' . current_time('Y-m-d H:i:s');
    $test_message = '
    <html>
    <body style="font-family: Arial, sans-serif; direction: rtl; text-align: right;">
        <h2>تست ایمیل Mini CRM</h2>
        <p>این یک ایمیل تست است که از پلاگین Mini CRM ارسال شده.</p>
        <p><strong>زمان ارسال:</strong> ' . current_time('Y-m-d H:i:s') . '</p>
        <p><strong>وضعیت:</strong> ایمیل با موفقیت ارسال شد.</p>
    </body>
    </html>';
    
    $headers = array(
        'Content-Type: text/html; charset=UTF-8',
        'From: تهران ایده <info@tehranidea.com>',
    );
    
    // لاگ کردن جزئیات
    mini_crm_log_email_details($admin_emails, $test_subject, $test_message, $headers);
    
    $result = wp_mail($admin_emails, $test_subject, $test_message, $headers);
    
    if ($result) {
        wp_send_json_success('ایمیل تست با موفقیت ارسال شد. لطفاً صندوق ورودی خود را بررسی کنید.');
    } else {
        wp_send_json_error('خطا در ارسال ایمیل تست. لطفاً لاگ‌های سایت را بررسی کنید.');
    }
}
add_action('wp_ajax_mini_crm_test_email', 'mini_crm_test_email_ajax');

/**
 * تابع لاگ کردن جزئیات ایمیل برای تشخیص مشکلات
 */
function mini_crm_log_email_details($to, $subject, $message, $headers) {
    $log_data = [
        'timestamp' => current_time('mysql'),
        'to' => is_array($to) ? implode(', ', $to) : $to,
        'subject' => $subject,
        'headers' => is_array($headers) ? implode('; ', $headers) : $headers,
        'message_length' => strlen($message),
        'server_info' => [
            'php_version' => PHP_VERSION,
            'wp_version' => get_bloginfo('version'),
            'sendmail_path' => ini_get('sendmail_path'),
            'smtp_server' => ini_get('SMTP'),
            'max_execution_time' => ini_get('max_execution_time')
        ]
    ];
    
    error_log('Mini CRM: Email attempt details: ' . json_encode($log_data, JSON_UNESCAPED_UNICODE));
}

// Admin Menus with Role-based Access Control
function mini_crm_admin_menus() {
    // Only show menu if user has Mini CRM access
    if (!mini_crm_user_can_access()) {
        return;
    }
    
    add_menu_page(
        __('Mini CRM', 'mini-crm'),__('Mini CRM', 'mini-crm'), 'mini_crm_access',
        'mini-crm-main', 'mini_crm_contacts_list_page', 'dashicons-groups', 25
    );
    add_submenu_page(
        'mini-crm-main', __('لیست تماس‌ها', 'mini-crm'), __('لیست تماس‌ها', 'mini-crm'),
        'mini_crm_access', 'mini-crm-main', 'mini_crm_contacts_list_page'
    );
    add_submenu_page(
        'mini-crm-main', __('افزودن تماس جدید', 'mini-crm'), __('افزودن تماس', 'mini-crm'),
        'mini_crm_manage_contacts', 'mini-crm-add-new', 'mini_crm_add_new_contact_page_callback'
    );
    
    // Settings menu only for administrators or users with settings permission
    if (mini_crm_user_can_access_settings()) {
        add_submenu_page(
            'mini-crm-main', __('تنظیمات Mini CRM', 'mini-crm'), __('تنظیمات', 'mini-crm'),
            'mini_crm_manage_settings', MINI_CRM_SETTINGS_SLUG, 'mini_crm_settings_page_render_callback'
        );
    }
}
add_action('admin_menu', 'mini_crm_admin_menus');

// Helper functions for labels and date formatting (No changes from previous version)
function mini_crm_get_channel_label($channel_key) {
    $channels = ['direct_call' => __('تماس مستقیم', 'mini-crm'), 'website_form' => __('فرم وبسایت', 'mini-crm'), 'instagram' => __('اینستاگرام', 'mini-crm'), 'telegram' => __('تلگرام', 'mini-crm'), 'manual_add' => __('افزودن دستی', 'mini-crm'),];
    return isset($channels[$channel_key]) ? $channels[$channel_key] : ucfirst(str_replace('_', ' ', $channel_key));
}
function mini_crm_get_status_label($status_key) {
    $statuses = ['registered' => __('ثبت‌شده', 'mini-crm'), 'REJECT' => __('رد شده', 'mini-crm'), 'PEND' => __('نیاز به بررسی', 'mini-crm'), 'ACCEPT' => __('پذیرفته شده', 'mini-crm'),];
    return isset($statuses[$status_key]) ? $statuses[$status_key] : ucfirst(strtolower($status_key));
}
function mini_crm_get_pend_sub_status_label($sub_status_key) {
    $sub_statuses = ['' => __('- انتخاب کنید -', 'mini-crm'), 'project_close' => __('پروژه نزدیک', 'mini-crm'), 'project_not_suitable' => __('پروژه نامناسب', 'mini-crm'),];
    return isset($sub_statuses[$sub_status_key]) ? $sub_statuses[$sub_status_key] : '-';
}
function mini_crm_format_datetime_for_display($datetime_string, $format = 'Y/m/d H:i') {
    if (empty($datetime_string) || $datetime_string === '0000-00-00 00:00:00' || $datetime_string === null) return '-';
    try {
        $datetime_obj = new DateTime($datetime_string, wp_timezone());
        if (function_exists('wp_date') && get_locale() === 'fa_IR') return wp_date($format, $datetime_obj->getTimestamp(), $datetime_obj->getTimezone());
        return $datetime_obj->format($format);
    } catch (Exception $e) { return $datetime_string; }
}
function mini_crm_format_time_for_display($time_string, $format = 'H:i') {
    if (empty($time_string) || $time_string === '00:00:00' || $time_string === null) return '-';
    try {
        // Handle different time formats
        if (preg_match('/^\d{2}:\d{2}:\d{2}$/', $time_string)) {
            // Format: HH:MM:SS
            $time_obj = DateTime::createFromFormat('H:i:s', $time_string);
        } elseif (preg_match('/^\d{2}:\d{2}$/', $time_string)) {
            // Format: HH:MM
            $time_obj = DateTime::createFromFormat('H:i', $time_string);
        } else {
            // Try to parse as is
            $time_obj = DateTime::createFromFormat('H:i:s', $time_string . ':00');
        }
        
        if ($time_obj && $time_obj->format('H:i') !== '00:00') {
            return $time_obj->format($format);
        } else {
            return '-';
        }
    } catch (Exception $e) {
        error_log("Mini CRM: Error formatting time '$time_string': " . $e->getMessage());
        return '-';
    }
}

/**
 * Convert Gregorian date to Persian date for SMS display
 */
function mini_crm_format_datetime_for_sms($datetime_string, $format = 'Y/m/d H:i') {
    if (empty($datetime_string) || $datetime_string === '0000-00-00 00:00:00' || $datetime_string === null) return '-';
    
    try {
        $datetime_obj = new DateTime($datetime_string, wp_timezone());
        
        // Extract Gregorian date components
        $gregorian_year = (int)$datetime_obj->format('Y');
        $gregorian_month = (int)$datetime_obj->format('m');
        $gregorian_day = (int)$datetime_obj->format('d');
        $time_part = $datetime_obj->format('H:i');
        
        // Convert to Persian date
        $persian_date = mini_crm_gregorian_to_persian($gregorian_year, $gregorian_month, $gregorian_day);
        
        // Format based on requested format
        if ($format === 'H:i') {
            // Time only
            return $time_part;
        } elseif (strpos($format, 'H:i') !== false) {
            // Include time with date
            return $persian_date . ' ' . $time_part;
        } else {
            // Date only
            return $persian_date;
        }
        
    } catch (Exception $e) {
        return $datetime_string;
    }
}

/**
 * Convert Gregorian date to Persian date (1403/10/15 format)
 */
function mini_crm_gregorian_to_persian($gy, $gm, $gd) {
    // Validate input
    if ($gy < 1 || $gm < 1 || $gm > 12 || $gd < 1 || $gd > 31) {
        error_log("Mini CRM: Invalid Gregorian date: $gy/$gm/$gd");
        return date('Y/m/d'); // Fallback to current date
    }
    
    // More accurate Gregorian to Persian conversion algorithm
    $jy = ($gy <= 1600) ? 0 : 979;
    $gy -= ($gy <= 1600) ? 621 : 1600;
    
    if ($gm > 2) {
        $gy2 = $gy + 1;
    } else {
        $gy2 = $gy;
    }
    
    $days = (365 * $gy) + floor(($gy2 + 3) / 4) - floor(($gy2 + 99) / 100) + floor(($gy2 + 399) / 400) - 80 + $gd;
    
    // Add days for months
    $g_d_m = [0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334];
    $days += $g_d_m[$gm - 1];
    
    // Check for leap year
    if ($gm > 2 && (($gy2 % 4 == 0 && $gy2 % 100 != 0) || $gy2 % 400 == 0)) {
        $days++;
    }
    
    $jy += 33 * floor($days / 12053);
    $days %= 12053;
    
    $jy += 4 * floor($days / 1461);
    $days %= 1461;
    
    if ($days >= 366) {
        $jy += floor(($days - 1) / 365);
        $days = ($days - 1) % 365;
    }
    
    if ($days < 186) {
        $jm = 1 + floor($days / 31);
        $jd = 1 + ($days % 31);
    } else {
        $jm = 7 + floor(($days - 186) / 30);
        $jd = 1 + (($days - 186) % 30);
    }
    
    // Ensure valid Persian date
    if ($jm < 1) $jm = 1;
    if ($jm > 12) $jm = 12;
    if ($jd < 1) $jd = 1;
    if ($jd > 31) $jd = 31;
    
    return sprintf('%04d/%02d/%02d', $jy, $jm, $jd);
}

// Admin page callback (list of contacts) (No changes from previous version)
function mini_crm_contacts_list_page() {
    if (!mini_crm_user_can_access()) {
        wp_die(__('شما دسترسی لازم برای مشاهده این صفحه را ندارید.', 'mini-crm'));
    }
    
    global $wpdb; $table_name = $wpdb->prefix . 'mini_crm_contacts';
    $initial_contacts = $wpdb->get_results("SELECT * FROM $table_name ORDER BY created_at DESC LIMIT 20");
    $total_contacts = $wpdb->get_var("SELECT COUNT(id) FROM $table_name"); $per_page = 20; $total_pages = ceil($total_contacts / $per_page);
    ?>
    <div class="wrap mini-crm-wrap">
        <h1><?php _e('Mini CRM - لیست تماس‌ها', 'mini-crm'); ?> <a href="<?php echo admin_url('admin.php?page=mini-crm-add-new'); ?>" class="page-title-action"><?php _e('افزودن جدید', 'mini-crm'); ?></a></h1>
        <div class="mini-crm-controls">
            <div class="mini-crm-search"><input type="text" id="mini-crm-search-input" placeholder="<?php esc_attr_e('جستجو در نام، تلفن...', 'mini-crm'); ?>"><button type="button" class="button" id="mini-crm-search-button"><span class="dashicons dashicons-search"></span> <?php _e('جستجو', 'mini-crm'); ?></button></div>
            <div class="mini-crm-filter">
                <label for="mini-crm-filter-status"><?php _e('وضعیت کلی:', 'mini-crm'); ?></label>
                <select id="mini-crm-filter-status">
                    <option value="all"><?php _e('همه وضعیت‌ها', 'mini-crm'); ?></option>
                    <option value="registered"><?php echo esc_html(mini_crm_get_status_label('registered')); ?></option>
                    <option value="REJECT"><?php echo esc_html(mini_crm_get_status_label('REJECT')); ?></option>
                    <option value="PEND"><?php echo esc_html(mini_crm_get_status_label('PEND')); ?></option>
                    <option value="ACCEPT"><?php echo esc_html(mini_crm_get_status_label('ACCEPT')); ?></option>
                </select>
                
                <label for="mini-crm-filter-call-status"><?php _e('وضعیت تماس:', 'mini-crm'); ?></label>
                <select id="mini-crm-filter-call-status">
                    <option value="all"><?php _e('همه وضعیت‌های تماس', 'mini-crm'); ?></option>
                    <option value="pending"><?php echo esc_html(mini_crm_get_call_status_label('pending')); ?></option>
                    <option value="attempted"><?php echo esc_html(mini_crm_get_call_status_label('attempted')); ?></option>
                    <option value="successful"><?php echo esc_html(mini_crm_get_call_status_label('successful')); ?></option>
                    <option value="failed"><?php echo esc_html(mini_crm_get_call_status_label('failed')); ?></option>
                    <option value="no_answer"><?php echo esc_html(mini_crm_get_call_status_label('no_answer')); ?></option>
                    <option value="busy"><?php echo esc_html(mini_crm_get_call_status_label('busy')); ?></option>
                    <option value="not_reachable"><?php echo esc_html(mini_crm_get_call_status_label('not_reachable')); ?></option>
                </select>
                
                <button type="button" class="button" id="mini-crm-filter-button"><?php _e('اعمال فیلتر', 'mini-crm'); ?></button>
            </div>
        </div>
        <div class="mini-crm-table-wrap"><table class="wp-list-table widefat fixed striped contacts-table"><thead><tr><th scope="col" class="manage-column column-cb check-column"><input type="checkbox" /></th><th scope="col"><?php _e('نام کامل', 'mini-crm'); ?></th><th scope="col"><?php _e('شماره تلفن', 'mini-crm'); ?></th><th scope="col"><?php _e('وضعیت', 'mini-crm'); ?></th><th scope="col"><?php _e('وضعیت فرعی', 'mini-crm'); ?></th><th scope="col"><?php _e('کانال', 'mini-crm'); ?></th><th scope="col"><?php _e('وضعیت تماس', 'mini-crm'); ?></th><th scope="col"><?php _e('تعداد تماس', 'mini-crm'); ?></th><th scope="col" style="width:10%;"><?php _e('بازدید (شروع)', 'mini-crm'); ?></th><th scope="col" style="width:10%;"><?php _e('بازدید (پایان)', 'mini-crm'); ?></th><th scope="col"><?php _e('یادداشت بازدید', 'mini-crm'); ?></th><th scope="col"><?php _e('تاریخ ثبت', 'mini-crm'); ?></th><th scope="col" style="width:10%;"><?php _e('عملیات پیامک', 'mini-crm'); ?></th><th scope="col" style="width:10%;"><?php _e('مدیریت', 'mini-crm'); ?></th></tr></thead><tbody id="mini-crm-contacts-tbody"><?php if ($initial_contacts) { foreach ($initial_contacts as $contact) echo mini_crm_render_contact_row_html($contact); } else { echo '<tr><td colspan="14">' . __('هیچ تماسی یافت نشد.', 'mini-crm') . '</td></tr>'; } ?></tbody></table></div>
        <div class="mini-crm-pagination" id="mini-crm-pagination-container"><?php echo paginate_links(['total' => $total_pages, 'current' => 1, 'format' => '?paged=%#%', 'prev_text' => __('&laquo; قبلی', 'mini-crm'), 'next_text' => __('بعدی &raquo;', 'mini-crm'),]); ?></div>
        <div id="mini-crm-ajax-message-global" class="mini-crm-ajax-message" style="display:none;"></div>
    </div><?php
}

// Helper functions for call status labels
function mini_crm_get_call_status_label($call_status) {
    $labels = [
        'pending' => __('در انتظار تماس', 'mini-crm'),
        'attempted' => __('تماس تلاش شده', 'mini-crm'),
        'successful' => __('تماس موفق', 'mini-crm'),
        'failed' => __('تماس ناموفق', 'mini-crm'),
        'no_answer' => __('پاسخ نداد', 'mini-crm'),
        'busy' => __('خط اشغال', 'mini-crm'),
        'not_reachable' => __('در دسترس نیست', 'mini-crm')
    ];
    return isset($labels[$call_status]) ? $labels[$call_status] : $call_status;
}

function mini_crm_get_call_status_color($call_status) {
    $colors = [
        'pending' => '#ffc107',      // زرد
        'attempted' => '#6c757d',    // خاکستری  
        'successful' => '#28a745',   // سبز
        'failed' => '#dc3545',       // قرمز
        'no_answer' => '#fd7e14',    // نارنجی
        'busy' => '#e83e8c',         // صورتی
        'not_reachable' => '#6f42c1' // بنفش
    ];
    return isset($colors[$call_status]) ? $colors[$call_status] : '#6c757d';
}

// Function to render a single contact row HTML (MODIFIED for call tracking)
function mini_crm_render_contact_row_html($contact) {
    ob_start(); $contact_id = $contact->id; $can_edit_visit = $contact->status === 'ACCEPT'; $can_edit_sub_status = $contact->status === 'PEND';
    ?><tr id="contact-row-<?php echo esc_attr($contact_id); ?>" data-contact-id="<?php echo esc_attr($contact_id); ?>"><th scope="row" class="check-column"><input type="checkbox" name="contact_ids[]" value="<?php echo esc_attr($contact_id); ?>" /></th><td><?php echo esc_html($contact->full_name); ?></td><td><?php echo esc_html($contact->phone); ?></td>
        <td><select class="contact-dynamic-update" data-field="status" data-original-value="<?php echo esc_attr($contact->status); ?>"><option value="registered" <?php selected($contact->status, 'registered'); ?>><?php echo esc_html(mini_crm_get_status_label('registered')); ?></option><option value="REJECT" <?php selected($contact->status, 'REJECT'); ?>><?php echo esc_html(mini_crm_get_status_label('REJECT')); ?></option><option value="PEND" <?php selected($contact->status, 'PEND'); ?>><?php echo esc_html(mini_crm_get_status_label('PEND')); ?></option><option value="ACCEPT" <?php selected($contact->status, 'ACCEPT'); ?>><?php echo esc_html(mini_crm_get_status_label('ACCEPT')); ?></option></select></td>
        <td class="sub-status-cell"><?php if ($can_edit_sub_status): ?><select class="contact-dynamic-update" data-field="sub_status" data-original-value="<?php echo esc_attr($contact->sub_status); ?>"><?php foreach (['', 'project_close', 'project_not_suitable'] as $sub_key): ?><option value="<?php echo esc_attr($sub_key); ?>" <?php selected($contact->sub_status, $sub_key); ?>><?php echo esc_html(mini_crm_get_pend_sub_status_label($sub_key)); ?></option><?php endforeach; ?></select><?php else: echo '-'; endif; ?></td>
        <td><?php echo esc_html(mini_crm_get_channel_label($contact->channel)); ?></td>
        <td class="call-status-cell">
            <select class="contact-dynamic-update call-status-select" data-field="call_status" data-original-value="<?php echo esc_attr($contact->call_status ?? 'pending'); ?>" style="background-color: <?php echo esc_attr(mini_crm_get_call_status_color($contact->call_status ?? 'pending')); ?>; color: white;">
                <option value="pending" <?php selected($contact->call_status ?? 'pending', 'pending'); ?>><?php echo esc_html(mini_crm_get_call_status_label('pending')); ?></option>
                <option value="attempted" <?php selected($contact->call_status ?? 'pending', 'attempted'); ?>><?php echo esc_html(mini_crm_get_call_status_label('attempted')); ?></option>
                <option value="successful" <?php selected($contact->call_status ?? 'pending', 'successful'); ?>><?php echo esc_html(mini_crm_get_call_status_label('successful')); ?></option>
                <option value="failed" <?php selected($contact->call_status ?? 'pending', 'failed'); ?>><?php echo esc_html(mini_crm_get_call_status_label('failed')); ?></option>
                <option value="no_answer" <?php selected($contact->call_status ?? 'pending', 'no_answer'); ?>><?php echo esc_html(mini_crm_get_call_status_label('no_answer')); ?></option>
                <option value="busy" <?php selected($contact->call_status ?? 'pending', 'busy'); ?>><?php echo esc_html(mini_crm_get_call_status_label('busy')); ?></option>
                <option value="not_reachable" <?php selected($contact->call_status ?? 'pending', 'not_reachable'); ?>><?php echo esc_html(mini_crm_get_call_status_label('not_reachable')); ?></option>
            </select>
        </td>
        <td class="call-attempts-cell">
            <div style="display: flex; align-items: center; gap: 5px;">
                <span class="call-attempts-count"><?php echo esc_html($contact->call_attempts ?? 0); ?></span>
                <button type="button" class="button button-small increment-call-attempts" data-contact-id="<?php echo esc_attr($contact_id); ?>" title="افزایش تعداد تماس">+</button>
            </div>
            <?php if (isset($contact->last_call_date) && $contact->last_call_date): ?>
                <small style="display: block; color: #666;"><?php echo esc_html(mini_crm_format_datetime_for_sms($contact->last_call_date, 'Y/m/d H:i')); ?></small>
            <?php endif; ?>
        </td>
        <td class="visit-date-cell">
            <?php if ($can_edit_visit): ?>
                <div class="visit-date-container">
                    <input type="text" class="persian-datepicker contact-visit-date" data-contact-id="<?php echo esc_attr($contact_id); ?>" placeholder="انتخاب تاریخ و ساعت" readonly>
                    <input type="hidden" class="visit-date-hidden" data-field="visit_date" value="<?php echo esc_attr($contact->visit_date ? str_replace(' ', 'T', $contact->visit_date) : ''); ?>">
                    <button type="button" class="button button-small confirm-visit-datetime" data-contact-id="<?php echo esc_attr($contact_id); ?>" style="display:none; margin-top:5px;">
                        <?php _e('تایید و ارسال پیامک', 'mini-crm'); ?>
                    </button>
                    <small class="visit-display"><?php echo esc_html(mini_crm_format_datetime_for_display($contact->visit_date)); ?></small>
                </div>
            <?php else: echo '-'; endif; ?>
        </td>
        <td class="visit-end-time-cell">
            <?php if ($can_edit_visit): ?>
                <input type="time" class="visit-end-time-input" data-field="visit_end_time" value="<?php echo esc_attr($contact->visit_end_time); ?>">
                <small><?php echo esc_html(mini_crm_format_time_for_display($contact->visit_end_time)); ?></small>
            <?php else: echo '-'; endif; ?>
        </td>
        <td class="visit-note-cell"><?php if ($can_edit_visit): ?><textarea class="contact-dynamic-update" data-field="visit_note" rows="2"><?php echo esc_textarea($contact->visit_note); ?></textarea><?php else: echo '-'; endif; ?></td>
        <td><?php echo esc_html(mini_crm_format_datetime_for_sms($contact->created_at, 'Y/m/d')); ?></td>
        <td class="manual-sms-actions"><?php $manual_sms_buttons = ['custom_sms_1' => __('فایل تلگرام', 'mini-crm'), 'custom_sms_2' => __('فایل واتساپ', 'mini-crm'), 'custom_sms_3' => __('دعوت اینستا', 'mini-crm'), 'custom_sms_4' => __('دعوت تلگرام', 'mini-crm'), 'custom_sms_5' => __('تماس ناموفق', 'mini-crm'), 'custom_sms_survey' => __('نظرسنجی بازدید', 'mini-crm'), 'custom_sms_after_visit' => __('پس از بازدید', 'mini-crm'),]; foreach ($manual_sms_buttons as $sms_key => $label) echo '<button type="button" class="button button-secondary button-small mini-crm-send-manual-sms" data-sms-type="' . esc_attr($sms_key) . '">' . esc_html($label) . '</button>'; ?></td>
        <td class="contact-management-actions">
            <button type="button" class="button button-small edit-contact-basic" data-contact-id="<?php echo esc_attr($contact_id); ?>" title="<?php esc_attr_e('ویرایش نام و شماره تلفن', 'mini-crm'); ?>">
                <span class="dashicons dashicons-edit"></span> <?php _e('ویرایش', 'mini-crm'); ?>
            </button>
            <button type="button" class="button button-small view-sms-history" data-contact-id="<?php echo esc_attr($contact_id); ?>" title="<?php esc_attr_e('مشاهده تاریخچه پیامک‌ها', 'mini-crm'); ?>" style="margin-top: 3px;">
                <span class="dashicons dashicons-email-alt"></span> <?php _e('پیامک‌ها', 'mini-crm'); ?>
            </button>
            <button type="button" class="button button-small button-link-delete delete-contact" data-contact-id="<?php echo esc_attr($contact_id); ?>" title="<?php esc_attr_e('حذف مخاطب', 'mini-crm'); ?>" style="color: #a00; margin-top: 3px;">
                <span class="dashicons dashicons-trash"></span> <?php _e('حذف', 'mini-crm'); ?>
            </button>
        </td>
    </tr><?php return ob_get_clean();
}

// Add new contact page callback (No changes from previous version)
function mini_crm_add_new_contact_page_callback() {
    if (!mini_crm_user_can_manage_contacts()) {
        wp_die(__('شما دسترسی لازم برای افزودن تماس را ندارید.', 'mini-crm'));
    }
    
    if (isset($_POST['mini_crm_admin_add_submit']) && check_admin_referer('mini_crm_add_contact_nonce', 'mini_crm_add_contact_nonce_field')) {
        global $wpdb; $table_name = $wpdb->prefix . 'mini_crm_contacts'; $message = ''; $message_type = 'error';
        $full_name = isset($_POST['full_name']) ? sanitize_text_field(trim($_POST['full_name'])) : ''; $phone = isset($_POST['phone']) ? sanitize_text_field(trim($_POST['phone'])) : '';
        $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : 'registered'; $channel = isset($_POST['channel']) ? sanitize_text_field($_POST['channel']) : 'manual_add';
        $visit_date_raw = isset($_POST['visit_date']) ? sanitize_text_field($_POST['visit_date']) : null; $visit_end_time_raw = isset($_POST['visit_end_time']) ? sanitize_text_field($_POST['visit_end_time']) : null;
        $visit_note = isset($_POST['visit_note']) ? sanitize_textarea_field($_POST['visit_note']) : null; $visit_date = null; $visit_end_time = null;
        if ($status === 'ACCEPT') { if (!empty($visit_date_raw)) { $dt = DateTime::createFromFormat('Y-m-d\TH:i', $visit_date_raw); if ($dt) $visit_date = $dt->format('Y-m-d H:i:s');} if (!empty($visit_end_time_raw)) { $time_obj = DateTime::createFromFormat('H:i', $visit_end_time_raw); if ($time_obj) $visit_end_time = $time_obj->format('H:i:s');}}
        $phone = str_replace(['۰','۱','۲','۳','۴','۵','۶','۷','۸','۹'], ['0','1','2','3','4','5','6','7','8','9'], $phone);
        if (empty($full_name) || empty($phone)) $message = __('نام و شماره تلفن نمی‌توانند خالی باشند.', 'mini-crm');
        elseif (!preg_match('/^(09|9)[0-9]{9}$/', $phone)) $message = __('شماره تلفن نامعتبر است.', 'mini-crm');
        else {
            $data_to_insert = ['full_name' => $full_name, 'phone' => $phone, 'status' => $status, 'channel' => $channel, 'created_at' => current_time('mysql'),];
            if ($status === 'ACCEPT') { $data_to_insert['visit_date'] = $visit_date; $data_to_insert['visit_end_time'] = $visit_end_time; $data_to_insert['visit_note'] = $visit_note; }
            $result = $wpdb->insert($table_name, $data_to_insert);
            if ($result !== false) { $contact_id = $wpdb->insert_id; $message = __('تماس با موفقیت افزوده شد.', 'mini-crm'); $message_type = 'updated'; $event_type = null; $sms_additional_data = ['channel' => $channel];
                if ($status === 'registered') $event_type = 'form_submission'; elseif ($status === 'REJECT') $event_type = 'status_reject'; elseif ($status === 'PEND') $event_type = 'status_pend';
                elseif ($status === 'ACCEPT') { $event_type = 'status_accept'; /* Visit SMS will only be sent when user clicks "confirm and send SMS" button */ }
                if($event_type) mini_crm_trigger_sms_notifications($contact_id, $event_type, $sms_additional_data); $_POST = [];
            } else $message = __('خطا در افزودن تماس.', 'mini-crm') . ' ' . $wpdb->last_error;
        } if ($message) echo '<div class="' . esc_attr($message_type) . ' notice is-dismissible"><p>' . esc_html($message) . '</p></div>';
    } ?>
    <div class="wrap mini-crm-wrap"><h1><?php _e('افزودن تماس جدید', 'mini-crm'); ?></h1><form method="post" action="" class="mini-crm-form"><?php wp_nonce_field('mini_crm_add_contact_nonce', 'mini_crm_add_contact_nonce_field'); ?>
        <table class="form-table">
            <tr valign="top"><th scope="row"><label for="add_full_name"><?php _e('نام کامل:', 'mini-crm'); ?></label></th><td><input type="text" name="full_name" id="add_full_name" class="regular-text" value="<?php echo isset($_POST['full_name']) ? esc_attr($_POST['full_name']) : ''; ?>" required></td></tr>
            <tr valign="top"><th scope="row"><label for="add_phone"><?php _e('شماره تلفن:', 'mini-crm'); ?></label></th><td><input type="tel" name="phone" id="add_phone" class="regular-text" pattern="[0-9۰-۹]{10,11}" title="<?php esc_attr_e('شماره تلفن باید 10 یا 11 رقم باشد', 'mini-crm'); ?>" value="<?php echo isset($_POST['phone']) ? esc_attr($_POST['phone']) : ''; ?>" required></td></tr>
            <tr valign="top"><th scope="row"><label for="add_status"><?php _e('وضعیت:', 'mini-crm'); ?></label></th><td><select name="status" id="add_contact_status"><?php foreach (['registered', 'REJECT', 'PEND', 'ACCEPT'] as $s_key): ?><option value="<?php echo esc_attr($s_key); ?>" <?php selected(isset($_POST['status']) ? $_POST['status'] : 'registered', $s_key); ?>><?php echo esc_html(mini_crm_get_status_label($s_key)); ?></option><?php endforeach; ?></select></td></tr>
            <tr valign="top"><th scope="row"><label for="add_channel"><?php _e('کانال ورودی:', 'mini-crm'); ?></label></th><td><select name="channel" id="add_channel" required><?php foreach (['manual_add', 'direct_call', 'website_form', 'instagram', 'telegram'] as $c_key): ?><option value="<?php echo esc_attr($c_key); ?>" <?php selected(isset($_POST['channel']) ? $_POST['channel'] : 'manual_add', $c_key); ?>><?php echo esc_html(mini_crm_get_channel_label($c_key)); ?></option><?php endforeach; ?></select></td></tr>
            <tbody id="add-visit-details-section" style="<?php echo (isset($_POST['status']) && $_POST['status'] === 'ACCEPT') || (empty($_POST) && 'ACCEPT' === 'registered') ? '' : 'display:none;'; ?>">
                <tr valign="top"><th scope="row"><label for="add_visit_date"><?php _e('تاریخ و ساعت شروع بازدید:', 'mini-crm'); ?></label></th><td><input type="datetime-local" name="visit_date" id="add_visit_date" class="regular-text" value="<?php echo isset($_POST['visit_date']) ? esc_attr($_POST['visit_date']) : ''; ?>"><p class="description"><?php _e('فرمت میلادی YYYY-MM-DDTHH:MM.', 'mini-crm'); ?></p></td></tr>
                <tr valign="top"><th scope="row"><label for="add_visit_end_time"><?php _e('ساعت پایان بازدید:', 'mini-crm'); ?></label></th><td><input type="time" name="visit_end_time" id="add_visit_end_time" class="regular-text" value="<?php echo isset($_POST['visit_end_time']) ? esc_attr($_POST['visit_end_time']) : ''; ?>"></td></tr>
                <tr valign="top"><th scope="row"><label for="add_visit_note"><?php _e('یادداشت بازدید:', 'mini-crm'); ?></label></th><td><textarea name="visit_note" id="add_visit_note" rows="3" class="large-text"><?php echo isset($_POST['visit_note']) ? esc_textarea($_POST['visit_note']) : ''; ?></textarea></td></tr>
            </tbody>
        </table><p class="submit"><input type="submit" name="mini_crm_admin_add_submit" class="button button-primary" value="<?php esc_attr_e('افزودن تماس', 'mini-crm'); ?>"></p></form>
    </div>
    <script type="text/javascript">
    jQuery(document).ready(function($){
        function toggleVisitDetails() {
            if ($('#add_contact_status').val() === 'ACCEPT') {
                $('#add-visit-details-section').show();
            } else {
                $('#add-visit-details-section').hide();
            }
        }
        $('#add_contact_status').on('change', toggleVisitDetails);
        toggleVisitDetails();
    });
    </script>
    <?php
}

// AJAX handler for updating contact details (No changes from previous version logic, only SMS trigger might behave differently)
function mini_crm_update_contact_details_ajax() {
    check_ajax_referer('mini_crm_update_contact_details', 'nonce');
    if (!mini_crm_user_can_manage_contacts()) wp_send_json_error(['message' => __('شما دسترسی لازم برای این کار را ندارید.', 'mini-crm')]);
    global $wpdb; $table_name = $wpdb->prefix . 'mini_crm_contacts';
    $contact_id = isset($_POST['contact_id']) ? intval($_POST['contact_id']) : 0; $field_to_update = isset($_POST['field']) ? sanitize_key($_POST['field']) : '';
    $value = isset($_POST['value']) ? stripslashes_deep($_POST['value']) : '';
    if (!$contact_id || empty($field_to_update)) wp_send_json_error(['message' => __('اطلاعات ناقص برای به‌روزرسانی.', 'mini-crm')]);
    $update_data = []; $event_type = null;
    $contact_before_update = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $contact_id));
    if (!$contact_before_update) wp_send_json_error(['message' => __('تماس یافت نشد.', 'mini-crm')]);

    switch ($field_to_update) {
        case 'status':
            $update_data['status'] = sanitize_text_field($value);
            if ($contact_before_update->status === 'PEND' && $update_data['status'] !== 'PEND') $update_data['sub_status'] = '';
            if ($contact_before_update->status === 'ACCEPT' && $update_data['status'] !== 'ACCEPT') { $update_data['visit_date'] = null; $update_data['visit_end_time'] = null; $update_data['visit_note'] = ''; }
            if ($update_data['status'] === 'REJECT') $event_type = 'status_reject'; elseif ($update_data['status'] === 'PEND') $event_type = 'status_pend'; elseif ($update_data['status'] === 'ACCEPT') $event_type = 'status_accept';
            break;
        case 'sub_status':
            if ($contact_before_update->status !== 'PEND') wp_send_json_error(['message' => __('وضعیت فرعی فقط برای وضعیت "نیاز به بررسی" قابل تنظیم است.', 'mini-crm')]);
            $update_data['sub_status'] = sanitize_text_field($value);
            if ($update_data['sub_status'] === 'project_close') $event_type = 'status_pend_project_close'; elseif ($update_data['sub_status'] === 'project_not_suitable') $event_type = 'status_pend_project_not_suitable';
            break;
        case 'visit_date':
            if ($contact_before_update->status !== 'ACCEPT') wp_send_json_error(['message' => __('تاریخ بازدید فقط برای وضعیت "پذیرفته شده" قابل تنظیم است.', 'mini-crm')]);
            $datetime_val = sanitize_text_field($value); $dt = DateTime::createFromFormat('Y-m-d\TH:i', $datetime_val); $update_data['visit_date'] = $dt ? $dt->format('Y-m-d H:i:s') : null;
            // Note: SMS will only be sent when user clicks "confirm and send SMS" button, not on inline edit
            break;
        case 'visit_end_time':
            if ($contact_before_update->status !== 'ACCEPT') wp_send_json_error(['message' => __('ساعت پایان بازدید فقط برای وضعیت "پذیرفته شده" قابل تنظیم است.', 'mini-crm')]);
            $time_val = sanitize_text_field($value); $time_obj = DateTime::createFromFormat('H:i', $time_val); $update_data['visit_end_time'] = $time_obj ? $time_obj->format('H:i:s') : null;
            // Note: SMS will only be sent when user clicks "confirm and send SMS" button, not on inline edit
            break;
        case 'visit_note':
            if ($contact_before_update->status !== 'ACCEPT') wp_send_json_error(['message' => __('یادداشت بازدید فقط برای وضعیت "پذیرفته شده" قابل تنظیم است.', 'mini-crm')]);
            $update_data['visit_note'] = sanitize_textarea_field($value);
            break;
        case 'call_status':
            $allowed_statuses = ['pending', 'attempted', 'successful', 'failed', 'no_answer', 'busy', 'not_reachable'];
            if (!in_array($value, $allowed_statuses)) wp_send_json_error(['message' => __('وضعیت تماس نامعتبر است.', 'mini-crm')]);
            $update_data['call_status'] = sanitize_text_field($value);
            $update_data['last_call_date'] = current_time('mysql');
            // Auto-send "تماس ناموفق" SMS for failed statuses
            if (in_array($value, ['failed', 'no_answer', 'busy', 'not_reachable'])) {
                $event_type = 'custom_sms_5'; // تماس ناموفق
            }
            break;
        default: wp_send_json_error(['message' => __('فیلد نامعتبر برای به‌روزرسانی.', 'mini-crm')]);
    }
    if (empty($update_data)) wp_send_json_error(['message' => __('داده ای برای بروزرسانی وجود ندارد یا مقدار بدون تغییر است.', 'mini-crm')]);
    $updated_rows = $wpdb->update($table_name, $update_data, ['id' => $contact_id]);
    if ($updated_rows !== false) { if ($event_type) mini_crm_trigger_sms_notifications($contact_id, $event_type);
        $new_contact_data = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $contact_id));
        wp_send_json_success(['message' => __('اطلاعات با موفقیت به‌روزرسانی شد.', 'mini-crm'), 'new_row_html' => mini_crm_render_contact_row_html($new_contact_data)]);
    } else wp_send_json_error(['message' => __('خطا در به‌روزرسانی اطلاعات یا داده‌ای تغییر نکرده است.', 'mini-crm') . ' ' . $wpdb->last_error]);
}
add_action('wp_ajax_mini_crm_update_contact_details', 'mini_crm_update_contact_details_ajax');

// AJAX handler for incrementing call attempts
function mini_crm_increment_call_attempts_ajax() {
    check_ajax_referer('mini_crm_increment_call_attempts', 'nonce');
    if (!mini_crm_user_can_manage_contacts()) wp_send_json_error(['message' => __('شما دسترسی لازم برای این کار را ندارید.', 'mini-crm')]);
    
    global $wpdb;
    $table_name = $wpdb->prefix . 'mini_crm_contacts';
    $contact_id = isset($_POST['contact_id']) ? intval($_POST['contact_id']) : 0;
    
    if (!$contact_id) wp_send_json_error(['message' => __('شناسه تماس نامعتبر است.', 'mini-crm')]);
    
    $contact = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $contact_id));
    if (!$contact) wp_send_json_error(['message' => __('تماس یافت نشد.', 'mini-crm')]);
    
    $new_attempts = intval($contact->call_attempts ?? 0) + 1;
    $update_data = [
        'call_attempts' => $new_attempts,
        'last_call_date' => current_time('mysql'),
        'call_status' => 'attempted'
    ];
    
    $updated_rows = $wpdb->update($table_name, $update_data, ['id' => $contact_id]);
    if ($updated_rows !== false) {
        $updated_contact = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $contact_id));
        wp_send_json_success([
            'message' => sprintf(__('تعداد تماس به %d افزایش یافت.', 'mini-crm'), $new_attempts),
            'new_row_html' => mini_crm_render_contact_row_html($updated_contact)
        ]);
    } else {
        wp_send_json_error(['message' => __('خطا در به‌روزرسانی تعداد تماس.', 'mini-crm')]);
    }
}
add_action('wp_ajax_mini_crm_increment_call_attempts', 'mini_crm_increment_call_attempts_ajax');

// AJAX handler for deleting contacts
function mini_crm_delete_contact_ajax() {
    check_ajax_referer('mini_crm_delete_contact', 'nonce');
    if (!mini_crm_user_can_manage_contacts()) {
        wp_send_json_error(['message' => __('شما دسترسی لازم برای این کار را ندارید.', 'mini-crm')]);
    }
    
    global $wpdb;
    $table_name = $wpdb->prefix . 'mini_crm_contacts';
    $contact_id = isset($_POST['contact_id']) ? intval($_POST['contact_id']) : 0;
    
    if (!$contact_id) {
        wp_send_json_error(['message' => __('شناسه تماس نامعتبر است.', 'mini-crm')]);
    }
    
    // Get contact info before deletion for logging
    $contact = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $contact_id));
    if (!$contact) {
        wp_send_json_error(['message' => __('تماس یافت نشد.', 'mini-crm')]);
    }
    
    $deleted_rows = $wpdb->delete($table_name, ['id' => $contact_id], ['%d']);
    
    if ($deleted_rows !== false && $deleted_rows > 0) {
        mini_crm_log_activity('contact_deleted', $contact_id, ['name' => $contact->full_name, 'phone' => $contact->phone]);
        error_log("Mini CRM: Contact deleted - ID: $contact_id, Name: {$contact->full_name}, Phone: {$contact->phone}");
        wp_send_json_success([
            'message' => sprintf(__('مخاطب "%s" با موفقیت حذف شد.', 'mini-crm'), $contact->full_name)
        ]);
    } else {
        mini_crm_log_activity('contact_deletion_failed', $contact_id, ['error' => $wpdb->last_error]);
        error_log("Mini CRM: Failed to delete contact - ID: $contact_id, Error: " . $wpdb->last_error);
        wp_send_json_error(['message' => __('خطا در حذف مخاطب.', 'mini-crm')]);
    }
}
add_action('wp_ajax_mini_crm_delete_contact', 'mini_crm_delete_contact_ajax');

// AJAX handler for editing contact basic info (name and phone)
function mini_crm_edit_contact_basic_info_ajax() {
    check_ajax_referer('mini_crm_edit_contact_basic', 'nonce');
    if (!mini_crm_user_can_manage_contacts()) {
        wp_send_json_error(['message' => __('شما دسترسی لازم برای این کار را ندارید.', 'mini-crm')]);
    }
    
    global $wpdb;
    $table_name = $wpdb->prefix . 'mini_crm_contacts';
    $contact_id = isset($_POST['contact_id']) ? intval($_POST['contact_id']) : 0;
    $full_name = isset($_POST['full_name']) ? sanitize_text_field(trim($_POST['full_name'])) : '';
    $phone = isset($_POST['phone']) ? sanitize_text_field(trim($_POST['phone'])) : '';
    
    if (!$contact_id || empty($full_name) || empty($phone)) {
        wp_send_json_error(['message' => __('اطلاعات ناقص برای ویرایش.', 'mini-crm')]);
    }
    
    // Convert Persian numbers to English
    $phone = str_replace(['۰','۱','۲','۳','۴','۵','۶','۷','۸','۹'], ['0','1','2','3','4','5','6','7','8','9'], $phone);
    
    // Validate phone format
    if (!preg_match('/^(09|9)[0-9]{9}$/', $phone)) {
        wp_send_json_error(['message' => __('شماره تلفن نامعتبر است.', 'mini-crm')]);
    }
    
    $contact_before = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $contact_id));
    if (!$contact_before) {
        wp_send_json_error(['message' => __('تماس یافت نشد.', 'mini-crm')]);
    }
    
    // Check for duplicate phone numbers only if phone number has changed
    if ($phone !== $contact_before->phone) {
        $existing_contact = $wpdb->get_row($wpdb->prepare("SELECT id FROM $table_name WHERE phone = %s AND id != %d", $phone, $contact_id));
        if ($existing_contact) {
            wp_send_json_error(['message' => __('این شماره تلفن برای مخاطب دیگری ثبت شده است.', 'mini-crm')]);
        }
    }
    
    $update_data = [
        'full_name' => $full_name,
        'phone' => $phone
    ];
    
    $updated_rows = $wpdb->update($table_name, $update_data, ['id' => $contact_id], ['%s', '%s'], ['%d']);
    
    if ($updated_rows !== false) {
        $updated_contact = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $contact_id));
        mini_crm_log_activity('contact_basic_info_updated', $contact_id, [
            'old' => ['name' => $contact_before->full_name, 'phone' => $contact_before->phone],
            'new' => ['name' => $full_name, 'phone' => $phone]
        ]);
        error_log("Mini CRM: Contact basic info updated - ID: $contact_id, Old: {$contact_before->full_name}/{$contact_before->phone}, New: $full_name/$phone");
        
        wp_send_json_success([
            'message' => __('اطلاعات مخاطب با موفقیت به‌روزرسانی شد.', 'mini-crm'),
            'new_row_html' => mini_crm_render_contact_row_html($updated_contact)
        ]);
    } else {
        mini_crm_log_activity('contact_basic_info_update_failed', $contact_id, ['error' => $wpdb->last_error]);
        wp_send_json_error(['message' => __('خطا در به‌روزرسانی اطلاعات.', 'mini-crm')]);
    }
}
add_action('wp_ajax_mini_crm_edit_contact_basic_info', 'mini_crm_edit_contact_basic_info_ajax');

// AJAX handler for viewing SMS history
function mini_crm_view_sms_history_ajax() {
    check_ajax_referer('mini_crm_view_sms_history', 'nonce');
    if (!mini_crm_user_can_access()) {
        wp_send_json_error(['message' => __('شما دسترسی لازم برای این کار را ندارید.', 'mini-crm')]);
    }
    
    global $wpdb;
    $sms_log_table = $wpdb->prefix . 'mini_crm_sms_log';
    $contact_id = isset($_POST['contact_id']) ? intval($_POST['contact_id']) : 0;
    
    if (!$contact_id) {
        wp_send_json_error(['message' => __('شناسه تماس نامعتبر است.', 'mini-crm')]);
    }
    
    // Get contact info
    $contacts_table = $wpdb->prefix . 'mini_crm_contacts';
    $contact = $wpdb->get_row($wpdb->prepare("SELECT full_name, phone FROM $contacts_table WHERE id = %d", $contact_id));
    
    if (!$contact) {
        wp_send_json_error(['message' => __('تماس یافت نشد.', 'mini-crm')]);
    }
    
    // Get SMS history
    $sms_history = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $sms_log_table WHERE contact_id = %d ORDER BY sent_at DESC LIMIT 20",
        $contact_id
    ));
    
    $html = '<div class="sms-history-modal">';
    $html .= '<h3>' . sprintf(__('تاریخچه پیامک‌های %s', 'mini-crm'), esc_html($contact->full_name)) . '</h3>';
    $html .= '<p><strong>' . __('شماره تلفن:', 'mini-crm') . '</strong> ' . esc_html($contact->phone) . '</p>';
    
    if (empty($sms_history)) {
        $html .= '<p>' . __('هیچ پیامکی برای این مخاطب ارسال نشده است.', 'mini-crm') . '</p>';
    } else {
        $html .= '<table class="wp-list-table widefat fixed striped">';
        $html .= '<thead><tr>';
        $html .= '<th>' . __('تاریخ ارسال', 'mini-crm') . '</th>';
        $html .= '<th>' . __('نوع پیامک', 'mini-crm') . '</th>';
        $html .= '<th>' . __('کد پترن', 'mini-crm') . '</th>';
        $html .= '<th>' . __('وضعیت', 'mini-crm') . '</th>';
        $html .= '<th>' . __('کد نتیجه', 'mini-crm') . '</th>';
        $html .= '<th>' . __('پیام خطا', 'mini-crm') . '</th>';
        $html .= '</tr></thead><tbody>';
        
        foreach ($sms_history as $sms) {
            $status_class = $sms->status === 'sent' ? 'success' : ($sms->status === 'failed' ? 'error' : 'pending');
            $status_text = $sms->status === 'sent' ? __('ارسال شده', 'mini-crm') : ($sms->status === 'failed' ? __('ناموفق', 'mini-crm') : __('در انتظار', 'mini-crm'));
            
            // Get Persian description for SMS type
            $sms_type_descriptions = [
                'form_submission' => __('پیامک ثبت فرم اولیه', 'mini-crm'),
                'status_reject' => __('پیامک رد درخواست', 'mini-crm'),
                'status_pend' => __('پیامک نیاز به بررسی', 'mini-crm'),
                'status_pend_project_close' => __('پیامک پروژه نزدیک', 'mini-crm'),
                'status_pend_project_not_suitable' => __('پیامک پروژه نامناسب', 'mini-crm'),
                'status_accept' => __('پیامک پذیرش درخواست', 'mini-crm'),
                'visit_scheduled' => __('پیامک تعیین زمان بازدید', 'mini-crm'),
                'custom_sms_1' => __('ارسال فایل تلگرام', 'mini-crm'),
                'custom_sms_2' => __('ارسال فایل واتساپ', 'mini-crm'),
                'custom_sms_3' => __('دعوت به اینستاگرام', 'mini-crm'),
                'custom_sms_4' => __('دعوت به کانال تلگرام', 'mini-crm'),
                'custom_sms_5' => __('پیامک تماس ناموفق', 'mini-crm'),
                'custom_sms_6' => __('معرفی وبسایت', 'mini-crm'),
                'custom_sms_7' => __('ارسال لوکیشن تلگرام', 'mini-crm'),
                'custom_sms_8' => __('ارسال لوکیشن واتساپ', 'mini-crm'),
                'custom_sms_9' => __('دعوت بازدید پروژه‌ها', 'mini-crm'),
                'custom_sms_10' => __('معرفی اینستاگرام (بازسازی کامل)', 'mini-crm'),
                'custom_sms_11' => __('معرفی وبسایت (بازسازی کامل)', 'mini-crm'),
                'custom_sms_after_visit' => __('پیامک پس از بازدید', 'mini-crm'),
                'custom_sms_survey' => __('پیامک نظرسنجی بازدید', 'mini-crm'),
            ];
            
            $sms_description = isset($sms_type_descriptions[$sms->sms_type]) ? $sms_type_descriptions[$sms->sms_type] : $sms->sms_type;
            
            $html .= '<tr>';
            $html .= '<td data-label="تاریخ ارسال">' . esc_html(mini_crm_format_datetime_for_display($sms->sent_at, 'Y/m/d H:i')) . '</td>';
            $html .= '<td data-label="نوع پیامک">' . esc_html($sms_description) . '</td>';
            $html .= '<td data-label="کد پترن">' . esc_html($sms->body_id) . '</td>';
            $html .= '<td data-label="وضعیت"><span class="status-' . $status_class . '">' . $status_text . '</span></td>';
            $html .= '<td data-label="کد نتیجه">' . esc_html($sms->result_code ?: '-') . '</td>';
            $html .= '<td data-label="پیام خطا">' . esc_html($sms->error_message ?: '-') . '</td>';
            $html .= '</tr>';
        }
        
        $html .= '</tbody></table>';
    }
    
    $html .= '<div style="margin-top: 15px; text-align: center;">';
    $html .= '<button type="button" class="button close-sms-history" style="background: #0073aa; color: white; padding: 8px 16px; border-radius: 3px;">' . __('بستن', 'mini-crm') . '</button>';
    $html .= '</div>';
    $html .= '<script>';
    $html .= 'jQuery(document).ready(function($) {';
    $html .= '$(document).on("click", ".close-sms-history", function() {';
    $html .= '$(this).closest(".sms-history-overlay").remove();';
    $html .= '});';
    $html .= '});';
    $html .= '</script>';
    $html .= '<style>';
    $html .= '.sms-history-modal table { width: 100%; border-collapse: collapse; }';
    $html .= '.sms-history-modal th, .sms-history-modal td { padding: 8px; text-align: right; border: 1px solid #ddd; }';
    $html .= '.sms-history-modal .status-success { color: #28a745; font-weight: bold; }';
    $html .= '.sms-history-modal .status-error { color: #dc3545; font-weight: bold; }';
    $html .= '.sms-history-modal .status-pending { color: #ffc107; font-weight: bold; }';
    $html .= '@media (max-width: 768px) {';
    $html .= '.sms-history-modal table, .sms-history-modal thead, .sms-history-modal tbody, .sms-history-modal th, .sms-history-modal td, .sms-history-modal tr {';
    $html .= 'display: block; }';
    $html .= '.sms-history-modal thead tr { position: absolute; top: -9999px; left: -9999px; }';
    $html .= '.sms-history-modal tr { border: 1px solid #ccc; margin-bottom: 10px; padding: 10px; }';
    $html .= '.sms-history-modal td { border: none; position: relative; padding-right: 50%; }';
    $html .= '.sms-history-modal td:before { content: attr(data-label) ": "; position: absolute; right: 6px; width: 45%; padding-left: 10px; white-space: nowrap; font-weight: bold; }';
    $html .= '}';
    $html .= '</style>';
    $html .= '</div>';
    
    wp_send_json_success(['html' => $html]);
}
add_action('wp_ajax_mini_crm_view_sms_history', 'mini_crm_view_sms_history_ajax');

/**
 * Log activity for debugging and monitoring
 */
function mini_crm_log_activity($action, $contact_id = null, $details = null) {
    global $wpdb;
    $activity_log_table = $wpdb->prefix . 'mini_crm_activity_log';
    
    $log_data = [
        'action' => sanitize_text_field($action),
        'contact_id' => $contact_id ? intval($contact_id) : null,
        'user_id' => get_current_user_id(),
        'details' => $details ? wp_json_encode($details, JSON_UNESCAPED_UNICODE) : null,
        'ip_address' => mini_crm_get_client_ip(),
        'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field($_SERVER['HTTP_USER_AGENT']) : null,
        'created_at' => current_time('mysql')
    ];
    
    $wpdb->insert($activity_log_table, $log_data);
    
    // Also log to WordPress error log for immediate debugging
    $log_message = "Mini CRM Activity: {$action}";
    if ($contact_id) $log_message .= " (Contact ID: {$contact_id})";
    if ($details) $log_message .= " - Details: " . wp_json_encode($details);
    error_log($log_message);
}

/**
 * Get client IP address
 */
function mini_crm_get_client_ip() {
    $ip_keys = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR'];
    
    foreach ($ip_keys as $key) {
        if (array_key_exists($key, $_SERVER) === true) {
            $ip = $_SERVER[$key];
            if (strpos($ip, ',') !== false) {
                $ip = explode(',', $ip)[0];
            }
            $ip = trim($ip);
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return $ip;
            }
        }
    }
    
    return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'unknown';
}

// AJAX handler for filtering/searching contacts (No changes from previous version)
function mini_crm_filter_search_contacts_ajax() {
    check_ajax_referer('mini_crm_filter_search_contacts', 'nonce');
    if (!mini_crm_user_can_access()) wp_send_json_error(['message' => __('شما دسترسی لازم برای این کار را ندارید.', 'mini-crm')]);
    global $wpdb; $table_name = $wpdb->prefix . 'mini_crm_contacts';
    $status_filter = isset($_POST['status_filter']) ? sanitize_text_field($_POST['status_filter']) : 'all';
    $call_status_filter = isset($_POST['call_status_filter']) ? sanitize_text_field($_POST['call_status_filter']) : 'all';
    $search_term = isset($_POST['search_term']) ? sanitize_text_field(trim($_POST['search_term'])) : '';
    $per_page = 20; $current_page = isset($_POST['paged']) ? max(1, intval($_POST['paged'])) : 1; $offset = ($current_page - 1) * $per_page;
    $sql_conditions = []; $sql_params = [];
    if ($status_filter !== 'all' && !empty($status_filter)) { $sql_conditions[] = "status = %s"; $sql_params[] = $status_filter; }
    if ($call_status_filter !== 'all' && !empty($call_status_filter)) { $sql_conditions[] = "call_status = %s"; $sql_params[] = $call_status_filter; }
    if (!empty($search_term)) { $search_like = '%' . $wpdb->esc_like($search_term) . '%'; $sql_conditions[] = "(full_name LIKE %s OR phone LIKE %s)"; $sql_params[] = $search_like; $sql_params[] = $search_like; }
    $where_clause = !empty($sql_conditions) ? "WHERE " . implode(" AND ", $sql_conditions) : "";
    $count_query = "SELECT COUNT(id) FROM $table_name $where_clause";
    $total_contacts = empty($sql_params) ? $wpdb->get_var($count_query) : $wpdb->get_var($wpdb->prepare($count_query, $sql_params));
    $total_pages = ceil($total_contacts / $per_page);
    $data_query = "SELECT * FROM $table_name $where_clause ORDER BY created_at DESC LIMIT %d OFFSET %d";
    $final_sql_params = array_merge($sql_params, [$per_page, $offset]); $contacts = $wpdb->get_results($wpdb->prepare($data_query, $final_sql_params));
    $html_rows = ''; if ($contacts) { foreach ($contacts as $contact) $html_rows .= mini_crm_render_contact_row_html($contact); } else $html_rows = '<tr><td colspan="14">' . __('هیچ تماسی با این مشخصات یافت نشد.', 'mini-crm') . '</td></tr>';
    $pagination_html = paginate_links(['total' => $total_pages, 'current' => $current_page, 'format' => '#', 'prev_text' => __('&laquo; قبلی', 'mini-crm'), 'next_text' => __('بعدی &raquo;', 'mini-crm'), 'type' => 'plain',]);
    wp_send_json_success(['html_rows' => $html_rows, 'pagination_html' => $pagination_html, 'total_pages' => $total_pages, 'current_page' => $current_page, 'found_posts' => $total_contacts]);
}
add_action('wp_ajax_mini_crm_filter_search_contacts', 'mini_crm_filter_search_contacts_ajax');

// ----- SMS System (NEW STRUCTURE FOR BODYID) -----
/**
 * Get default SMS configurations.
 * Defines structure for body_id and argument guides.
 */
function mini_crm_get_default_sms_configs() {
    $base_url = home_url('/');
    $default_configs = [
        'api_username' => '',
        'api_password' => '',
        'sender_number' => '',
        'admin_emails' => '',
        'links' => [ // These links can be used as arguments for some bodyIds
            'tehranidea_instagram_link' => 'https://instagram.com/tehranidea',
            'tehranidea_telegram_link' => 'https://t.me/Tehran_idea',
            'tehranidea_website_link' => 'https://tehranidea.com',
            'survey_link' => $base_url . 'survey-page/',
            'tehranidea_telegram_videoupload_link' => 'https://t.me/Tehran_idea',
            'tehranidea_whatsapp_videoupload_link' => 'https://wa.me/989123456789?text=VideoUpload',
            'tehranidea_telegram_location_link' => 'https://t.me/Tehran_idea',
            'tehranidea_whatsapp_location_link' => 'https://wa.me/989123456789?text=Location',
            'tehranidea_projects_link' => 'https://tehranidea.com/projects/',
        ],
        'templates' => [] // Templates defined below
    ];

    // Define templates: key => [ 'body_id_default_empty', 'args_guide_text', 'enabled_default' ]
    $templates_definitions = [
        'form_submission' => ['', __('{0}: نام مشتری', 'mini-crm'), true], // SMS 7
        'status_reject' => ['', __('{0}: نام مشتری', 'mini-crm'), true], // SMS 1
        'status_pend' => ['', __('{0}: نام مشتری', 'mini-crm'), true], // SMS 2
        'status_pend_project_close' => ['', __('{0}: نام مشتری', 'mini-crm'), true], // SMS 3
        'status_pend_project_not_suitable' => ['', __('(بدون آرگومان پویا)', 'mini-crm'), true], // SMS 4
        'status_accept' => ['', __('(بدون آرگومان پویا)', 'mini-crm'), true], // SMS 6
        'visit_scheduled' => ['', __('{0}: نام مشتری, {1}: تاریخ شمسی (1403/10/15), {2}: ساعت شروع (H:i), {3}: ساعت پایان (H:i)', 'mini-crm'), true], // SMS 5
        // Manual SMS
        'custom_sms_1' => ['', __('{0}: لینک آپلود تلگرام (از تنظیمات لینک‌ها)', 'mini-crm'), true],
        'custom_sms_2' => ['', __('{0}: لینک آپلود واتس‌اپ (از تنظیمات لینک‌ها)', 'mini-crm'), true],
        'custom_sms_3' => ['', __('{0}: لینک اینستاگرام (از تنظیمات لینک‌ها)', 'mini-crm'), true],
        'custom_sms_4' => ['', __('{0}: لینک کانال تلگرام (از تنظیمات لینک‌ها)', 'mini-crm'), true],
        'custom_sms_5' => ['', __('{0}: نام مشتری', 'mini-crm'), true], // تماس ناموفق
        'custom_sms_6' => ['', __('{0}: لینک وبسایت (از تنظیمات لینک‌ها)', 'mini-crm'), true],
        'custom_sms_7' => ['', __('{0}: لینک ارسال لوکیشن تلگرام (از تنظیمات لینک‌ها)', 'mini-crm'), true],
        'custom_sms_8' => ['', __('{0}: لینک ارسال لوکیشن واتس‌اپ (از تنظیمات لینک‌ها)', 'mini-crm'), true],
        'custom_sms_9' => ['', __('{0}: لینک صفحه پروژه‌ها (از تنظیمات لینک‌ها)', 'mini-crm'), true],
        'custom_sms_10' => ['', __('{0}: لینک اینستاگرام (از تنظیمات لینک‌ها)', 'mini-crm'), true], // فقط بازسازی کامل - وبسایت -> اینستا
        'custom_sms_11' => ['', __('{0}: لینک وبسایت (از تنظیمات لینک‌ها)', 'mini-crm'), true], // فقط بازسازی کامل - اینستا -> وبسایت
        'custom_sms_after_visit' => ['', __('(بدون آرگومان پویا)', 'mini-crm'), true],
        'custom_sms_survey' => ['', __('{0}: نام مشتری, {1}: لینک نظرسنجی (از تنظیمات لینک‌ها)', 'mini-crm'), true],
    ];

    foreach ($templates_definitions as $key => $def) {
        $default_configs['templates'][$key] = ['body_id' => $def[0], 'args_guide' => $def[1], 'enabled' => $def[2]];
    }
    return $default_configs;
}

/**
 * Fix invalid URLs in existing settings
 */
function mini_crm_fix_invalid_urls() {
    $current_settings = get_option(MINI_CRM_SETTINGS_SLUG, []);
    if (empty($current_settings['links'])) return;
    
    $fixes_needed = false;
    $url_fixes = [
        'tehranidea_telegram_link' => 'https://t.me/Tehran_idea',
        'tehranidea_telegram_videoupload_link' => 'https://t.me/your_tehranidea_contact_for_video',
        'tehranidea_telegram_location_link' => 'https://t.me/your_tehranidea_contact_for_location'
    ];
    
    foreach ($url_fixes as $key => $correct_url) {
        if (isset($current_settings['links'][$key])) {
            $current_value = $current_settings['links'][$key];
            // If current value starts with 't.me/' instead of 'https://t.me/'
            if (strpos($current_value, 't.me/') === 0 && strpos($current_value, 'https://') !== 0) {
                $current_settings['links'][$key] = 'https://' . $current_value;
                $fixes_needed = true;
            }
        }
    }
    
    if ($fixes_needed) {
        update_option(MINI_CRM_SETTINGS_SLUG, $current_settings);
    }
}

/**
 * Export settings as JSON file download
 */
function mini_crm_export_settings() {
    // Security check
    if (!mini_crm_user_can_access_settings()) {
        wp_die(__('شما دسترسی لازم برای این کار را ندارید.', 'mini-crm'));
    }
    
    $settings = get_option(MINI_CRM_SETTINGS_SLUG, []);
    
    // Remove sensitive password from export for security
    if (isset($settings['api_password'])) {
        $settings['api_password'] = '';
    }
    
    $export_data = [
        'version' => '1.0',
        'plugin' => 'Mini CRM',
        'export_date' => current_time('mysql'),
        'settings' => $settings
    ];
    
    $filename = 'mini-crm-settings-' . date('Y-m-d-H-i-s') . '.json';
    $json_content = json_encode($export_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    
    // Clear any previous output
    if (ob_get_level()) {
        ob_end_clean();
    }
    
    // Set headers for JSON download
    header('Content-Type: application/json; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . strlen($json_content));
    header('Cache-Control: no-cache, must-revalidate');
    header('Expires: 0');
    
    // Output JSON and exit
    echo $json_content;
    exit;
}

/**
 * Import settings from uploaded JSON file
 */
function mini_crm_import_settings() {
    if (!isset($_FILES['mini_crm_import_file']) || $_FILES['mini_crm_import_file']['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'لطفاً فایل JSON معتبری انتخاب کنید.'];
    }
    
    $file = $_FILES['mini_crm_import_file'];
    
    // Check file type
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if ($file_extension !== 'json') {
        return ['success' => false, 'message' => 'فقط فایل‌های JSON پذیرفته می‌شوند.'];
    }
    
    // Check file size (max 1MB)
    if ($file['size'] > 1024 * 1024) {
        return ['success' => false, 'message' => 'حجم فایل باید کمتر از 1 مگابایت باشد.'];
    }
    
    // Read and decode JSON
    $json_content = file_get_contents($file['tmp_name']);
    $import_data = json_decode($json_content, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        return ['success' => false, 'message' => 'فایل JSON معتبر نیست.'];
    }
    
    // Validate structure
    if (!isset($import_data['plugin']) || $import_data['plugin'] !== 'Mini CRM') {
        return ['success' => false, 'message' => 'این فایل برای پلاگین Mini CRM نیست.'];
    }
    
    if (!isset($import_data['settings']) || !is_array($import_data['settings'])) {
        return ['success' => false, 'message' => 'ساختار فایل نامعتبر است.'];
    }
    
    // Get current settings to preserve password if not provided in import
    $current_settings = get_option(MINI_CRM_SETTINGS_SLUG, []);
    $import_settings = $import_data['settings'];
    
    // If password is empty in import, keep current password
    if (empty($import_settings['api_password']) && !empty($current_settings['api_password'])) {
        $import_settings['api_password'] = $current_settings['api_password'];
    }
    
    // Merge with defaults to ensure all required fields exist
    $defaults = mini_crm_get_default_sms_configs();
    $final_settings = array_merge($defaults, $import_settings);
    
    // Save settings
    $update_result = update_option(MINI_CRM_SETTINGS_SLUG, $final_settings);
    
    if ($update_result !== false) {
        return ['success' => true, 'message' => 'تنظیمات با موفقیت وارد شد.'];
    } else {
        return ['success' => false, 'message' => 'خطا در ذخیره تنظیمات.'];
    }
}

/**
 * Get SMS settings from options, falling back to defaults. (MODIFIED for body_id)
 */
function mini_crm_get_sms_settings() {
    // Fix invalid URLs in existing settings
    mini_crm_fix_invalid_urls();
    
    $defaults = mini_crm_get_default_sms_configs();
    $saved_settings = get_option(MINI_CRM_SETTINGS_SLUG, []);
    $settings = array_replace_recursive($defaults, $saved_settings);

    foreach ($defaults['templates'] as $key => $default_template_config) {
        if (!isset($settings['templates'][$key])) {
            $settings['templates'][$key] = $default_template_config;
        }
        if (!isset($settings['templates'][$key]['body_id'])) { // Ensure body_id key exists
            $settings['templates'][$key]['body_id'] = $default_template_config['body_id']; // Default to empty or saved
        }
         if (!isset($settings['templates'][$key]['args_guide'])) { // Ensure args_guide key exists
            $settings['templates'][$key]['args_guide'] = $default_template_config['args_guide'];
        }
         if (!isset($settings['templates'][$key]['enabled'])) {
            $settings['templates'][$key]['enabled'] = $default_template_config['enabled'];
        }
    }
    foreach ($defaults['links'] as $key => $default_link_url) {
        if (!isset($settings['links'][$key])) $settings['links'][$key] = $default_link_url;
    }
    return $settings;
}

/**
 * Prepares the array of arguments for a given SMS event type. (NEW FUNCTION)
 */
function mini_crm_prepare_sms_arguments($event_type, $contact_object, $sms_settings, $additional_data = []) {
    if (!$contact_object) return [];
    $arguments = [];

    switch ($event_type) {
        case 'form_submission':
        case 'status_reject':
        case 'status_pend':
        case 'status_pend_project_close':
        case 'custom_sms_5': // تماس ناموفق
            $arguments[] = $contact_object->full_name ?? '';
            break;

        case 'status_pend_project_not_suitable': // No dynamic args
        case 'status_accept': // No dynamic args
        case 'custom_sms_after_visit': // No dynamic args
            // No arguments from contact object needed for these templates themselves
            break;

        case 'visit_scheduled':
            // آرگومان 0: نام مشتری
            $arguments[] = $contact_object->full_name ?? '';
            
            // آرگومان 1: تاریخ شمسی (از فیلد visit_persian_date یا استخراج از visit_date)
            if (!empty($contact_object->visit_persian_date)) {
                $arguments[] = $contact_object->visit_persian_date;
                error_log("Mini CRM: Using visit_persian_date: {$contact_object->visit_persian_date}");
            } elseif (!empty($contact_object->visit_date)) {
                // اگر visit_persian_date موجود نباشد، از visit_date استخراج کن
                $date_parts = explode(' ', $contact_object->visit_date);
                $persian_date = $date_parts[0] ?? '';
                $arguments[] = $persian_date;
                error_log("Mini CRM: Extracted Persian date from visit_date: {$persian_date}");
            } else {
                $arguments[] = __('?', 'mini-crm');
                error_log("Mini CRM: No Persian date found");
            }
            
            // آرگومان 2: ساعت شروع (از فیلد visit_start_time یا استخراج از visit_date)
            if (!empty($contact_object->visit_start_time)) {
                $arguments[] = $contact_object->visit_start_time;
                error_log("Mini CRM: Using visit_start_time: {$contact_object->visit_start_time}");
            } elseif (!empty($contact_object->visit_date)) {
                // اگر visit_start_time موجود نباشد، از visit_date استخراج کن
                $date_parts = explode(' ', $contact_object->visit_date);
                $start_time = $date_parts[1] ?? '';
                $arguments[] = $start_time;
                error_log("Mini CRM: Extracted start time from visit_date: {$start_time}");
            } else {
                $arguments[] = __('?', 'mini-crm');
                error_log("Mini CRM: No start time found");
            }
            
            // آرگومان 3: ساعت پایان
            if (!empty($contact_object->visit_end_time) && $contact_object->visit_end_time !== '00:00:00') {
                // حذف ثانیه اگر وجود داشته باشد
                $end_time_parts = explode(':', $contact_object->visit_end_time);
                $end_time = $end_time_parts[0] . ':' . ($end_time_parts[1] ?? '00');
                $arguments[] = $end_time;
                error_log("Mini CRM: Visit end time found: {$contact_object->visit_end_time}, formatted: {$end_time}");
            } else {
                $arguments[] = __('?', 'mini-crm');
                error_log("Mini CRM: Visit end time not found or empty: {$contact_object->visit_end_time}");
            }
            break;
        
        // Manual SMS that use links from settings
        case 'custom_sms_1': $arguments[] = $sms_settings['links']['tehranidea_telegram_videoupload_link'] ?? ''; break;
        case 'custom_sms_2': $arguments[] = $sms_settings['links']['tehranidea_whatsapp_videoupload_link'] ?? ''; break;
        case 'custom_sms_3': $arguments[] = $sms_settings['links']['tehranidea_instagram_link'] ?? ''; break;
        case 'custom_sms_4': $arguments[] = $sms_settings['links']['tehranidea_telegram_link'] ?? ''; break;
        case 'custom_sms_6': $arguments[] = $sms_settings['links']['tehranidea_website_link'] ?? ''; break;
        case 'custom_sms_7': $arguments[] = $sms_settings['links']['tehranidea_telegram_location_link'] ?? ''; break;
        case 'custom_sms_8': $arguments[] = $sms_settings['links']['tehranidea_whatsapp_location_link'] ?? ''; break;
        case 'custom_sms_9': $arguments[] = $sms_settings['links']['tehranidea_projects_link'] ?? ''; break;
        case 'custom_sms_10':$arguments[] = $sms_settings['links']['tehranidea_instagram_link'] ?? ''; break; // فقط بازسازی کامل - وبسایت -> اینستا
        case 'custom_sms_11':$arguments[] = $sms_settings['links']['tehranidea_website_link'] ?? ''; break;    // فقط بازسازی کامل - اینستا -> وبسایت
        
        case 'custom_sms_survey':
            $arguments[] = $contact_object->full_name ?? '';
            $arguments[] = $sms_settings['links']['survey_link'] ?? '';
            break;
    }
    // Sanitize arguments to prevent issues with semicolons or special chars if API is sensitive
    return array_map('sanitize_text_field', $arguments);
}


/**
 * Sends SMS using MeliPayamak API with BodyID and Arguments and logs to database
 */
function mini_crm_send_sms_via_melipayamak($phone_number, $body_id, $arguments_array = [], $contact_id = 0, $sms_type = '') {
    global $wpdb;
    $sms_log_table = $wpdb->prefix . 'mini_crm_sms_log';
    
    $settings = mini_crm_get_sms_settings();
    $username = $settings['api_username'];
    $password = $settings['api_password'];
    
    $normalized_phone = preg_replace('/^(?:\+98|0)?/', '', $phone_number);
    $normalized_phone = str_replace(['۰','۱','۲','۳','۴','۵','۶','۷','۸','۹'], ['0','1','2','3','4','5','6','7','8','9'], $normalized_phone);
    
    // Log SMS attempt to database
    $log_data = [
        'contact_id' => $contact_id,
        'phone' => $normalized_phone,
        'sms_type' => $sms_type,
        'body_id' => $body_id,
        'arguments' => json_encode($arguments_array, JSON_UNESCAPED_UNICODE),
        'status' => 'pending',
        'sent_at' => current_time('mysql')
    ];
    
    $log_id = $wpdb->insert($sms_log_table, $log_data);
    $log_id = $wpdb->insert_id;

    if (empty($username) || empty($password)) {
        $error_msg = 'API credentials not set.';
        $wpdb->update($sms_log_table, ['status' => 'failed', 'error_message' => $error_msg], ['id' => $log_id]);
        error_log("Mini CRM: MeliPayamak API credentials not set.");
        return ['success' => false, 'message' => $error_msg, 'log_id' => $log_id];
    }
    
    if (empty($body_id)) {
        $error_msg = 'Body ID cannot be empty.';
        $wpdb->update($sms_log_table, ['status' => 'failed', 'error_message' => $error_msg], ['id' => $log_id]);
        error_log("Mini CRM: Body ID is empty for sending SMS.");
        return ['success' => false, 'message' => $error_msg, 'log_id' => $log_id];
    }

    if (!class_exists('SoapClient')) {
        $error_msg = 'PHP SOAP extension not installed.';
        $wpdb->update($sms_log_table, ['status' => 'failed', 'error_message' => $error_msg], ['id' => $log_id]);
        error_log("Mini CRM: SoapClient class not found. PHP SOAP extension is required.");
        return ['success' => false, 'message' => $error_msg, 'log_id' => $log_id];
    }

    // Construct the 'text' payload for MeliPayamak
    $text_payload = "@" . trim($body_id) . "@";
    if (!empty($arguments_array)) {
        $text_payload .= implode(";", $arguments_array);
    }

    try {
        ini_set("soap.wsdl_cache_enabled", "0");
        $sms_client = new SoapClient("http://api.payamak-panel.com/post/Send.asmx?wsdl", ["encoding" => "UTF-8", "trace" => 1, "exceptions" => 1]);
        
        $data_to_send_api = [
            "username" => $username,
            "password" => $password,
            "text" => $text_payload,
            "to" => $normalized_phone,
        ];

        error_log("Mini CRM: Attempting SMS send - Username: $username, To: $normalized_phone, Text: $text_payload");

        $response_object = $sms_client->SendByBaseNumber3($data_to_send_api);
        $send_result_code = $response_object->SendByBaseNumber3Result;

        if (is_string($send_result_code) && strlen($send_result_code) > 10) {
            // Success - UID received
            $wpdb->update($sms_log_table, [
                'status' => 'sent',
                'result_code' => $send_result_code
            ], ['id' => $log_id]);
            
            error_log("Mini CRM: SMS (BodyID: $body_id) sent successfully to $normalized_phone. Result (UID): $send_result_code. Payload: $text_payload");
            return ['success' => true, 'result_code' => $send_result_code, 'message' => __('پیامک ارسال شد.', 'mini-crm'), 'log_id' => $log_id];
            
        } elseif (is_numeric($send_result_code) && intval($send_result_code) > 0 && intval($send_result_code) < 100) {
            // Success - numeric code
            $wpdb->update($sms_log_table, [
                'status' => 'sent',
                'result_code' => $send_result_code
            ], ['id' => $log_id]);
            
            error_log("Mini CRM: SMS (BodyID: $body_id) sent successfully to $normalized_phone. Result code: $send_result_code. Payload: $text_payload");
            return ['success' => true, 'result_code' => $send_result_code, 'message' => __('پیامک ارسال شد. کد نتیجه: ', 'mini-crm') . $send_result_code, 'log_id' => $log_id];
            
        } else {
            // Failed
            $error_message_from_api = mini_crm_get_melipayamak_error_message($send_result_code);
            $wpdb->update($sms_log_table, [
                'status' => 'failed',
                'result_code' => $send_result_code,
                'error_message' => $error_message_from_api
            ], ['id' => $log_id]);
            
            error_log("Mini CRM: Failed to send SMS (BodyID: $body_id) to $normalized_phone. API Response Code: $send_result_code. Error: $error_message_from_api. Payload: $text_payload");
            return ['success' => false, 'result_code' => $send_result_code, 'message' => __("خطا در ارسال پیامک: ", 'mini-crm') . "$error_message_from_api (" . __('کد:', 'mini-crm') . " $send_result_code)", 'log_id' => $log_id];
        }

    } catch (SoapFault $e) {
        $error_msg = 'SOAP Fault: ' . $e->getMessage();
        $wpdb->update($sms_log_table, [
            'status' => 'failed',
            'error_message' => $error_msg
        ], ['id' => $log_id]);
        
        error_log("Mini CRM: SOAP Fault while sending SMS (BodyID: $body_id) to $normalized_phone - " . $e->getMessage());
        return ['success' => false, 'message' => __('خطای SOAP:', 'mini-crm') . ' ' . $e->getMessage(), 'log_id' => $log_id];
        
    } catch (Exception $e) {
        $error_msg = 'General Exception: ' . $e->getMessage();
        $wpdb->update($sms_log_table, [
            'status' => 'failed',
            'error_message' => $error_msg
        ], ['id' => $log_id]);
        
        error_log("Mini CRM: General Exception while sending SMS (BodyID: $body_id) to $normalized_phone - " . $e->getMessage());
        return ['success' => false, 'message' => __('خطای عمومی:', 'mini-crm') . ' ' . $e->getMessage(), 'log_id' => $log_id];
    }
}

// Helper to interpret MeliPayamak error codes
function mini_crm_get_melipayamak_error_message($code) {
    $error_codes = [
        '-1' => __('نام کاربری یا رمز عبور اشتباه است', 'mini-crm'),
        '-2' => __('اعتبار کافی نیست', 'mini-crm'),
        '-3' => __('محدودیت در ارسال روزانه', 'mini-crm'),
        '-4' => __('محدودیت در حجم ارسال', 'mini-crm'),
        '-5' => __('شماره فرستنده معتبر نیست', 'mini-crm'),
        '-6' => __('سامانه در حالت تعمیر است', 'mini-crm'),
        '-7' => __('متن پیامک شامل کلمه فیلتر شده است', 'mini-crm'),
        '-8' => __('شماره گیرنده در لیست سیاه قرار دارد', 'mini-crm'),
        '-9' => __('کد ملی معتبر نیست', 'mini-crm'),
        '-10' => __('ارسال ناموفق به دلیل خاموش یا خارج از دسترس بودن گوشی', 'mini-crm'),
        '-11' => __('ارسال ناموفق به دلیل خطا در شبکه', 'mini-crm'),
        '-12' => __('کد امنیتی heliosms اشتباه است', 'mini-crm'),
        '-13' => __('کاربر مورد نظر فعال نیست', 'mini-crm'),
        '-14' => __('تاریخ ارسال اشتباه است', 'mini-crm'),
        '-15' => __('شماره موبایل معتبر نیست', 'mini-crm'),
        '-16' => __('Body ID پیدا نشد', 'mini-crm'),
    ];
    
    return isset($error_codes[$code]) ? $error_codes[$code] : __('خطای ناشناخته از سرویس پیامک', 'mini-crm') . " (کد: $code)";
}


/**
 * Triggers SMS notifications based on event type. (MODIFIED for body_id)
 */
function mini_crm_trigger_sms_notifications($contact_id, $event_type, $additional_data_for_args = []) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'mini_crm_contacts';
    $contact = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $contact_id));

    if (!$contact) { error_log("Mini CRM: SMS trigger: Non-existent contact ID: $contact_id"); return; }

    $sms_settings = mini_crm_get_sms_settings();

    if (!isset($sms_settings['templates'][$event_type])) { error_log("Mini CRM: SMS template for event '$event_type' not defined."); return; }
    if (empty($sms_settings['templates'][$event_type]['body_id'])) { error_log("Mini CRM: Body ID for event '$event_type' is not set in settings."); return; }
    if (!$sms_settings['templates'][$event_type]['enabled']) { return; /* SMS for this event is disabled */ }

    $body_id = trim($sms_settings['templates'][$event_type]['body_id']);
    
    // Prepare arguments based on event type
    $arguments = mini_crm_prepare_sms_arguments($event_type, $contact, $sms_settings, $additional_data_for_args);
    
    // Debug: Log the prepared arguments
    error_log("Mini CRM: Prepared SMS arguments for event '$event_type': " . json_encode($arguments, JSON_UNESCAPED_UNICODE));
    error_log("Mini CRM: Contact visit_date: {$contact->visit_date}, visit_end_time: {$contact->visit_end_time}");
    
    $send_attempt_result = mini_crm_send_sms_via_melipayamak($contact->phone, $body_id, $arguments, $contact_id, $event_type);
    
    if (!$send_attempt_result['success']) {
        error_log("Mini CRM: Failed to send SMS for event '$event_type' (BodyID: $body_id) to contact ID $contact_id. Reason: " . $send_attempt_result['message']);
    } else {
        error_log("Mini CRM: SMS for event '$event_type' (BodyID: $body_id) sent to contact ID $contact_id. Result: " . ($send_attempt_result['result_code'] ?? 'OK'));
    }
}

// AJAX handler for sending manual SMS (MODIFIED for body_id)
function mini_crm_send_manual_sms_ajax() {
    check_ajax_referer('mini_crm_send_manual_sms_action', 'nonce');
    if (!mini_crm_user_can_send_sms()) wp_send_json_error(['message' => __('شما دسترسی لازم برای این کار را ندارید.', 'mini-crm')]);

    $contact_id = isset($_POST['contact_id']) ? intval($_POST['contact_id']) : 0;
    $sms_type_key = isset($_POST['sms_type']) ? sanitize_key($_POST['sms_type']) : '';

    if (!$contact_id || empty($sms_type_key)) wp_send_json_error(['message' => __('اطلاعات ناقص برای ارسال پیامک.', 'mini-crm')]);

    global $wpdb; $table_name = $wpdb->prefix . 'mini_crm_contacts';
    $contact = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $contact_id));
    if (!$contact) wp_send_json_error(['message' => __('تماس یافت نشد.', 'mini-crm')]);

    $sms_settings = mini_crm_get_sms_settings();

    if (!isset($sms_settings['templates'][$sms_type_key]) || empty($sms_settings['templates'][$sms_type_key]['body_id']) || !$sms_settings['templates'][$sms_type_key]['enabled']) {
        wp_send_json_error(['message' => __('Body ID برای این نوع پیامک تعریف نشده، غیرفعال است یا خالی می‌باشد.', 'mini-crm')]);
    }

    $body_id = trim($sms_settings['templates'][$sms_type_key]['body_id']);
    $arguments = mini_crm_prepare_sms_arguments($sms_type_key, $contact, $sms_settings);
    $send_result = mini_crm_send_sms_via_melipayamak($contact->phone, $body_id, $arguments, $contact_id, $sms_type_key);

    if ($send_result['success']) {
        wp_send_json_success(['message' => $send_result['message'] . (isset($send_result['result_code']) ? " (کد: {$send_result['result_code']})" : '') ]);
    } else {
        wp_send_json_error(['message' => $send_result['message']]);
    }
}
add_action('wp_ajax_mini_crm_send_manual_sms', 'mini_crm_send_manual_sms_ajax');

// AJAX handler for confirming visit appointment and sending SMS
function mini_crm_confirm_visit_sms_ajax() {
    check_ajax_referer('mini_crm_confirm_visit_sms', 'nonce');
    if (!mini_crm_user_can_manage_contacts()) wp_send_json_error(['message' => __('شما دسترسی لازم برای این کار را ندارید.', 'mini-crm')]);

    $contact_id = isset($_POST['contact_id']) ? intval($_POST['contact_id']) : 0;
    $visit_date = isset($_POST['visit_date']) ? sanitize_text_field($_POST['visit_date']) : '';
    $visit_time = isset($_POST['visit_time']) ? sanitize_text_field($_POST['visit_time']) : '';
    $visit_end_time = isset($_POST['visit_end_time']) ? sanitize_text_field($_POST['visit_end_time']) : '';

    // Debug logging
    error_log("Mini CRM: Confirm visit SMS - Contact ID: $contact_id, Date: $visit_date, Time: $visit_time, End Time: $visit_end_time");

    if (!$contact_id || empty($visit_date) || empty($visit_time)) {
        error_log("Mini CRM: Missing data for visit confirmation - Contact ID: $contact_id, Date: '$visit_date', Time: '$visit_time'");
        wp_send_json_error(['message' => __('اطلاعات ناقص برای تایید قرار بازدید.', 'mini-crm')]);
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'mini_crm_contacts';
    $contact = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $contact_id));
    
    if (!$contact) {
        error_log("Mini CRM: Contact not found for ID: $contact_id");
        wp_send_json_error(['message' => __('تماس یافت نشد.', 'mini-crm')]);
    }

    if ($contact->status !== 'ACCEPT') {
        error_log("Mini CRM: Contact status is not ACCEPT for ID: $contact_id, current status: {$contact->status}");
        wp_send_json_error(['message' => __('فقط مشتریان با وضعیت "پذیرفته شده" می‌توانند قرار بازدید داشته باشند.', 'mini-crm')]);
    }

    // Store Persian date and time as strings (simpler approach)
    try {
        // Validate Persian date format
        $date_parts = explode('/', $visit_date);
        if (count($date_parts) !== 3) {
            error_log("Mini CRM: Invalid date format: $visit_date");
            wp_send_json_error(['message' => __('فرمت تاریخ نامعتبر است.', 'mini-crm')]);
        }
        
        // Store as Persian date string with time
        $persian_datetime = $visit_date . ' ' . $visit_time;
        
        error_log("Mini CRM: Storing Persian datetime: $persian_datetime, End time: $visit_end_time");
        
        // Prepare update data - store Persian date as string
        $update_data = [
            'visit_date' => $persian_datetime,
            'visit_persian_date' => $visit_date,  // Store pure Persian date
            'visit_start_time' => $visit_time,    // Store start time
        ];
        
        // Add visit_end_time if provided, otherwise set default
        if (!empty($visit_end_time)) {
            $update_data['visit_end_time'] = $visit_end_time;
            error_log("Mini CRM: Visit end time provided: $visit_end_time");
        } else {
            // Default: 2 hours after start time
            $start_parts = explode(':', $visit_time);
            $start_hour = intval($start_parts[0]);
            $start_minute = intval($start_parts[1] ?? 0);
            $end_hour = $start_hour + 2;
            $visit_end_time = sprintf('%02d:%02d', $end_hour, $start_minute);
            $update_data['visit_end_time'] = $visit_end_time;
            error_log("Mini CRM: Auto-set end time to: $visit_end_time");
        }
        
        // Update visit date and end time in database
        $updated = $wpdb->update(
            $table_name,
            $update_data,
            ['id' => $contact_id]
        );

        if ($updated !== false) {
            error_log("Mini CRM: Database updated successfully for contact ID: $contact_id");
            
            // Trigger SMS notification
            error_log("Mini CRM: Triggering SMS notification for visit_scheduled event");
            mini_crm_trigger_sms_notifications($contact_id, 'visit_scheduled');
            
            // Get updated contact data
            $updated_contact = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $contact_id));
            
            wp_send_json_success([
                'message' => __('قرار بازدید تایید شد و پیامک ارسال گردید.', 'mini-crm'),
                'new_row_html' => mini_crm_render_contact_row_html($updated_contact)
            ]);
        } else {
            error_log("Mini CRM: Database update failed for contact ID: $contact_id. Last error: " . $wpdb->last_error);
            wp_send_json_error(['message' => __('خطا در به‌روزرسانی قرار بازدید.', 'mini-crm')]);
        }
        
    } catch (Exception $e) {
        error_log("Mini CRM: Exception in confirm_visit_sms_ajax: " . $e->getMessage());
        wp_send_json_error(['message' => __('خطا در پردازش تاریخ.', 'mini-crm')]);
    }
}
add_action('wp_ajax_mini_crm_confirm_visit_sms', 'mini_crm_confirm_visit_sms_ajax');

// Helper function to convert Persian date to Gregorian
function mini_crm_persian_to_gregorian($persian_year, $persian_month, $persian_day) {
    $persian_year = intval($persian_year);
    $persian_month = intval($persian_month);
    $persian_day = intval($persian_day);
    
    // Validate input
    if ($persian_year < 1 || $persian_month < 1 || $persian_month > 12 || $persian_day < 1 || $persian_day > 31) {
        error_log("Mini CRM: Invalid Persian date: $persian_year/$persian_month/$persian_day");
        return date('Y-m-d'); // Fallback to today
    }
    
    // Accurate Persian to Gregorian conversion algorithm
    $jy = $persian_year;
    $jm = $persian_month;
    $jd = $persian_day;
    
    // Calculate total days from Persian epoch
    $epyear = $jy - 979;
    $epbase = 0;
    
    if ($epyear >= 0) {
        $aux1 = floor($epyear / 2816);
        $aux2 = $epyear % 2816;
        $epdays = 1029983 * $aux1 + 365 * $aux2 + floor($aux2 / 128) * 683;
    } else {
        $aux1 = floor(-$epyear / 2816);
        $aux2 = (-$epyear) % 2816;
        $epdays = -1029983 * $aux1 - 365 * $aux2 - floor($aux2 / 128) * 683;
    }
    
    // Add days for months
    for ($i = 1; $i < $jm; $i++) {
        if ($i <= 6) {
            $epdays += 31;
        } else {
            $epdays += 30;
        }
    }
    
    // Add days
    $epdays += $jd - 1;
    
    // Convert to Gregorian
    $gregorian_epoch = 1948321; // Persian epoch in Julian days
    $julian_day = $epdays + $gregorian_epoch;
    
    // Convert Julian day to Gregorian date
    $a = $julian_day + 32044;
    $b = floor((4 * $a + 3) / 146097);
    $c = $a - floor((146097 * $b) / 4);
    $d = floor((4 * $c + 3) / 1461);
    $e = $c - floor((1461 * $d) / 4);
    $m = floor((5 * $e + 2) / 153);
    
    $day = $e - floor((153 * $m + 2) / 5) + 1;
    $month = $m + 3 - 12 * floor($m / 10);
    $year = 100 * $b + $d - 4800 + floor($m / 10);
    
    return sprintf('%04d-%02d-%02d', $year, $month, $day);
}


// ----- Settings Page (MODIFIED for BodyID and Arg Guide) -----
function mini_crm_settings_page_render_callback() {
    if (!mini_crm_user_can_access_settings()) return;

    // Handle Export
    if (isset($_POST['mini_crm_export_settings']) && check_admin_referer('mini_crm_export_nonce_action', 'mini_crm_export_nonce_field')) {
        mini_crm_export_settings();
        return;
    }

    // Handle Import
    if (isset($_POST['mini_crm_import_settings']) && check_admin_referer('mini_crm_import_nonce_action', 'mini_crm_import_nonce_field')) {
        $import_result = mini_crm_import_settings();
        if ($import_result['success']) {
            $redirect_url = add_query_arg([
                'page' => MINI_CRM_SETTINGS_SLUG,
                'import-success' => 'true'
            ], admin_url('admin.php'));
            wp_redirect($redirect_url);
            exit;
        } else {
            echo '<div class="notice notice-error"><p><strong>خطا در وارد کردن تنظیمات:</strong> ' . esc_html($import_result['message']) . '</p></div>';
        }
    }

    // Show success messages
    if (isset($_GET['settings-updated']) && $_GET['settings-updated'] === 'true') {
        echo '<div id="setting-error-settings_updated" class="updated settings-error notice is-dismissible"><p><strong>' . __('تنظیمات با موفقیت ذخیره شد.', 'mini-crm') . '</strong></p></div>';
    }
    
    if (isset($_GET['import-success']) && $_GET['import-success'] === 'true') {
        echo '<div id="setting-error-import_success" class="updated settings-error notice is-dismissible"><p><strong>' . __('تنظیمات با موفقیت وارد شد.', 'mini-crm') . '</strong></p></div>';
    }

 

    if (isset($_POST['mini_crm_save_settings_submit']) && wp_verify_nonce($_POST['mini_crm_settings_nonce_field'], 'mini_crm_settings_nonce_action')) {
        $current_settings = mini_crm_get_sms_settings(); // Get current settings first
        $options_to_save = mini_crm_get_default_sms_configs(); // Start with defaults

        $options_to_save['api_username'] = isset($_POST[MINI_CRM_SETTINGS_SLUG]['api_username']) ? sanitize_text_field($_POST[MINI_CRM_SETTINGS_SLUG]['api_username']) : '';
        
        // Handle password: only update if a new password is provided
        if (isset($_POST[MINI_CRM_SETTINGS_SLUG]['api_password']) && !empty(trim($_POST[MINI_CRM_SETTINGS_SLUG]['api_password']))) {
            $options_to_save['api_password'] = sanitize_text_field($_POST[MINI_CRM_SETTINGS_SLUG]['api_password']);
        } else {
            // Keep existing password if field is empty
            $options_to_save['api_password'] = $current_settings['api_password'];
        }
        
        $options_to_save['sender_number'] = isset($_POST[MINI_CRM_SETTINGS_SLUG]['sender_number']) ? sanitize_text_field($_POST[MINI_CRM_SETTINGS_SLUG]['sender_number']) : '';
        $options_to_save['admin_emails'] = isset($_POST[MINI_CRM_SETTINGS_SLUG]['admin_emails']) ? sanitize_text_field($_POST[MINI_CRM_SETTINGS_SLUG]['admin_emails']) : '';

        if (isset($_POST[MINI_CRM_SETTINGS_SLUG]['links']) && is_array($_POST[MINI_CRM_SETTINGS_SLUG]['links'])) {
            foreach ($_POST[MINI_CRM_SETTINGS_SLUG]['links'] as $key => $value) {
                if (array_key_exists($key, $options_to_save['links'])) {
                    $options_to_save['links'][$key] = esc_url_raw(trim($value));
                }
            }
        }

        if (isset($_POST[MINI_CRM_SETTINGS_SLUG]['templates']) && is_array($_POST[MINI_CRM_SETTINGS_SLUG]['templates'])) {
            foreach ($_POST[MINI_CRM_SETTINGS_SLUG]['templates'] as $event_type => $template_data) {
                if (array_key_exists($event_type, $options_to_save['templates'])) {
                    $options_to_save['templates'][$event_type]['enabled'] = isset($template_data['enabled']) ? true : false;
                    $options_to_save['templates'][$event_type]['body_id'] = isset($template_data['body_id']) ? sanitize_text_field(trim($template_data['body_id'])) : '';
                    // args_guide is not saved, it's from defaults
                }
            }
        }
        // Validate required fields
        $validation_errors = [];
        
        if (empty($options_to_save['api_username'])) {
            $validation_errors[] = __('نام کاربری API ضروری است.', 'mini-crm');
        }
        
        if (empty($options_to_save['api_password'])) {
            $validation_errors[] = __('رمز عبور API ضروری است.', 'mini-crm');
        }
        
        if (!empty($validation_errors)) {
            echo '<div id="setting-error-settings_error" class="error settings-error notice is-dismissible"><p><strong>' . __('خطا در ذخیره تنظیمات:', 'mini-crm') . '</strong></p><ul>';
            foreach ($validation_errors as $error) {
                echo '<li>' . esc_html($error) . '</li>';
            }
            echo '</ul></div>';
        } else {
            $update_result = update_option(MINI_CRM_SETTINGS_SLUG, $options_to_save);
            if ($update_result !== false) {
                // Redirect to same page with success message
                $redirect_url = add_query_arg([
                    'page' => MINI_CRM_SETTINGS_SLUG,
                    'settings-updated' => 'true'
                ], admin_url('admin.php'));
                wp_redirect($redirect_url);
                exit;
            } else {
                echo '<div class="notice notice-warning"><p><strong>توجه:</strong> تنظیمات تغییری نکرده یا مشکلی در ذخیره‌سازی وجود دارد.</p></div>';
            }
        }
    }

    $current_settings = mini_crm_get_sms_settings();
    ?>
    <div class="wrap mini-crm-wrap">
        <h1><?php _e('تنظیمات Mini CRM', 'mini-crm'); ?></h1>
        <?php mini_crm_display_role_management_info(); ?>
        <form method="post" action="<?php echo esc_url(admin_url('admin.php?page=' . MINI_CRM_SETTINGS_SLUG)); ?>">
            <?php wp_nonce_field('mini_crm_settings_nonce_action', 'mini_crm_settings_nonce_field'); ?>
            <h2 class="nav-tab-wrapper"><a href="#api-settings" class="nav-tab nav-tab-active"><?php _e('API ملی پیامک', 'mini-crm'); ?></a><a href="#link-settings" class="nav-tab"><?php _e('لینک‌های ثابت', 'mini-crm'); ?></a><a href="#template-settings" class="nav-tab"><?php _e('کدهای پترن پیامک', 'mini-crm'); ?></a><a href="#import-export" class="nav-tab"><?php _e('ورود/خروجی تنظیمات', 'mini-crm'); ?></a></h2>

            <div id="api-settings" class="settings-tab-content">
                <h3><?php _e('تنظیمات اتصال به API ملی پیامک', 'mini-crm'); ?></h3>
                <table class="form-table">
                    <tr valign="top"><th scope="row"><label for="mini_crm_api_username_field"><?php _e('نام کاربری API:', 'mini-crm'); ?></label></th><td><input type="text" id="mini_crm_api_username_field" name="<?php echo MINI_CRM_SETTINGS_SLUG; ?>[api_username]" value="<?php echo esc_attr($current_settings['api_username']); ?>" class="regular-text" /></td></tr>
                    <tr valign="top"><th scope="row"><label for="mini_crm_api_password_field"><?php _e('رمز عبور API:', 'mini-crm'); ?></label></th><td><input type="password" id="mini_crm_api_password_field" name="<?php echo MINI_CRM_SETTINGS_SLUG; ?>[api_password]" value="" class="regular-text" placeholder="<?php _e('در صورت عدم تغییر خالی بگذارید', 'mini-crm'); ?>" /></td></tr>
                    <tr valign="top"><th scope="row"><label for="mini_crm_sender_number_field"><?php _e('شماره خط ارسال کننده:', 'mini-crm'); ?></label></th><td><input type="text" id="mini_crm_sender_number_field" name="<?php echo MINI_CRM_SETTINGS_SLUG; ?>[sender_number]" value="<?php echo esc_attr($current_settings['sender_number']); ?>" class="regular-text" placeholder="<?php esc_attr_e('مثال: 3000XXXX', 'mini-crm'); ?>" /><p class="description"><?php _e('این شماره ممکن است برای برخی متدهای ارسال ملی پیامک نیاز باشد.', 'mini-crm'); ?></p></td></tr>
                    <tr valign="top"><th scope="row"><label for="mini_crm_admin_emails_field"><?php _e('ایمیل‌های ادمین:', 'mini-crm'); ?></label></th><td><input type="text" id="mini_crm_admin_emails_field" name="<?php echo MINI_CRM_SETTINGS_SLUG; ?>[admin_emails]" value="<?php echo esc_attr($current_settings['admin_emails']); ?>" class="large-text" placeholder="<?php esc_attr_e('admin1@example.com, admin2@example.com', 'mini-crm'); ?>" /><p class="description"><?php _e('ایمیل‌های ادمین را با کاما (,) از هم جدا کنید. این ایمیل‌ها هنگام ثبت فرم جدید اطلاع‌رسانی خواهند شد.', 'mini-crm'); ?></p></td></tr>
                    <tr valign="top"><th scope="row"><?php _e('تست ایمیل:', 'mini-crm'); ?></th><td><button type="button" id="mini_crm_test_email" class="button button-secondary"><?php _e('ارسال ایمیل تست', 'mini-crm'); ?></button><p class="description"><?php _e('برای تست عملکرد ارسال ایمیل، این دکمه را کلیک کنید. نتیجه در لاگ‌های سایت قابل مشاهده است.', 'mini-crm'); ?></p><div id="email_test_result" style="margin-top:10px;"></div></td></tr>
                </table>
            </div>

            <div id="link-settings" class="settings-tab-content" style="display:none;">
                 <h3><?php _e('لینک‌های ثابت (برای استفاده در آرگومان‌های پیامک)', 'mini-crm'); ?></h3>
                <table class="form-table">
                    <?php foreach ($current_settings['links'] as $key => $value): ?>
                    <tr valign="top">
                        <th scope="row">
                            <label for="mini_crm_link_<?php echo esc_attr($key); ?>">
                                <?php echo esc_html(ucwords(str_replace('_', ' ', str_replace('tehranidea_', '', $key)))); ?>:
                            </label>
                        </th>
                        <td>
                            <input type="url" id="mini_crm_link_<?php echo esc_attr($key); ?>" name="<?php echo MINI_CRM_SETTINGS_SLUG; ?>[links][<?php echo esc_attr($key); ?>]" value="<?php echo esc_attr($value); ?>" class="large-text" />
                            <p class="description"><?php echo sprintf(__('کلید: %s', 'mini-crm'), '<code>' . $key . '</code>'); ?></p>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </table>
            </div>

            <div id="template-settings" class="settings-tab-content" style="display:none;">
                <h3><?php _e('کدهای پترن (Body ID) و فعال‌سازی پیامک‌ها', 'mini-crm'); ?></h3>
                 <p class="description"><?php _e('برای هر پیامک، کد پترن (Body ID) ثبت شده در سامانه ملی پیامک را وارد کنید. راهنمای آرگومان‌ها نشان می‌دهد که پترن شما در ملی پیامک باید چه متغیرهایی (و به چه ترتیبی) داشته باشد.', 'mini-crm'); ?></p>
                <?php
                $template_event_descriptions = [ /* Same as before */ 
                    'form_submission' => __('پیامک پس از ثبت فرم (پیامک ۷)', 'mini-crm'), 'status_reject' => __('وضعیت: رد شده (پیامک ۱)', 'mini-crm'), /* ... and so on ... */
                    'status_pend' => __('وضعیت: نیاز به بررسی (پیامک ۲)', 'mini-crm'), 'status_pend_project_close' => __('وضعیت فرعی: پروژه نزدیک (پیامک ۳)', 'mini-crm'),
                    'status_pend_project_not_suitable' => __('وضعیت فرعی: پروژه نامناسب (پیامک ۴)', 'mini-crm'), 'status_accept' => __('وضعیت: پذیرفته شده (پیامک ۶)', 'mini-crm'),
                    'visit_scheduled' => __('تنظیم/به‌روزرسانی قرار بازدید (پیامک ۵)', 'mini-crm'), 'custom_sms_1' => __('دستی: ارسال فایل تلگرام', 'mini-crm'),
                    'custom_sms_2' => __('دستی: ارسال فایل واتس‌اپ', 'mini-crm'), 'custom_sms_3' => __('دستی: دعوت به اینستاگرام', 'mini-crm'),
                    'custom_sms_4' => __('دستی: دعوت به کانال تلگرام', 'mini-crm'), 'custom_sms_5' => __('دستی: تماس ناموفق', 'mini-crm'),
                    'custom_sms_6' => __('دستی: معرفی وبسایت', 'mini-crm'), 'custom_sms_7' => __('دستی: لوکیشن تلگرام', 'mini-crm'),
                    'custom_sms_8' => __('دستی: لوکیشن واتس‌اپ', 'mini-crm'), 'custom_sms_9' => __('دستی: دعوت بازدید پروژه‌ها', 'mini-crm'),
                    'custom_sms_10' => __('دستی: فقط بازسازی کامل (وبسایت)', 'mini-crm'), 'custom_sms_11' => __('دستی: فقط بازسازی کامل (اینستاگرام)', 'mini-crm'),
                    'custom_sms_after_visit' => __('دستی: پس از بازدید', 'mini-crm'), 'custom_sms_survey' => __('دستی: نظرسنجی بازدید', 'mini-crm'),
                ];
                ?>
                <div id="sms-templates-accordion">
                <?php foreach ($current_settings['templates'] as $event_type => $template_config): ?>
                    <?php $description = isset($template_event_descriptions[$event_type]) ? $template_event_descriptions[$event_type] : esc_html(ucwords(str_replace('_', ' ', $event_type))); ?>
                    <h3><?php echo $description; ?></h3>
                    <div><table class="form-table">
                        <tr valign="top"><th scope="row"><?php _e('فعال‌سازی:', 'mini-crm'); ?></th>
                            <td><label><input type="checkbox" name="<?php echo MINI_CRM_SETTINGS_SLUG; ?>[templates][<?php echo esc_attr($event_type); ?>][enabled]" value="1" <?php checked(isset($template_config['enabled']) ? $template_config['enabled'] : false, true); ?> /> <?php _e('ارسال این پیامک فعال باشد', 'mini-crm'); ?></label></td></tr>
                        <tr valign="top"><th scope="row"><label for="body_id_<?php echo esc_attr($event_type); ?>"><?php _e('کد پترن (Body ID):', 'mini-crm'); ?></label></th>
                            <td><input type="text" id="body_id_<?php echo esc_attr($event_type); ?>" name="<?php echo MINI_CRM_SETTINGS_SLUG; ?>[templates][<?php echo esc_attr($event_type); ?>][body_id]" value="<?php echo esc_attr(isset($template_config['body_id']) ? $template_config['body_id'] : ''); ?>" class="regular-text" />
                                <p class="description"><?php _e('راهنمای آرگومان‌ها:', 'mini-crm'); ?> <code><?php echo esc_html(isset($template_config['args_guide']) ? $template_config['args_guide'] : __('تعریف نشده', 'mini-crm')); ?></code></p>
                            </td></tr>
                    </table></div>
                <?php endforeach; ?>
                </div>
            </div>

            <div id="import-export" class="settings-tab-content" style="display:none;">
                <h3><?php _e('ورود و خروجی تنظیمات', 'mini-crm'); ?></h3>
                <div class="notice notice-info inline" style="margin: 15px 0; padding: 10px;">
                    <p><strong><?php _e('توجه:', 'mini-crm'); ?></strong> <?php _e('این تب موقتاً غیرفعال است. برای ذخیره تنظیمات API و templates از دکمه پایین صفحه استفاده کنید.', 'mini-crm'); ?></p>
                </div>
                
                <!-- Export Section (DISABLED) -->
                <div style="background: #f9f9f9; padding: 20px; border: 1px solid #ddd; border-radius: 5px; margin-bottom: 30px;">
                    <h4 style="margin-top: 0;"><?php _e('خروجی تنظیمات', 'mini-crm'); ?></h4>
                    <p class="description"><?php _e('فایل JSON حاوی تمام تنظیمات پلاگین (به جز رمز عبور API) دانلود می‌شود.', 'mini-crm'); ?></p>
                    
                    <!-- DISABLED: Method 1: Traditional Form -->
                    <div style="margin-top: 15px; opacity: 0.5;">
                        <input type="button" class="button button-secondary" value="<?php esc_attr_e('دانلود فایل تنظیمات (غیرفعال)', 'mini-crm'); ?>" disabled />
                        <p class="description"><?php _e('موقتاً غیرفعال شده است.', 'mini-crm'); ?></p>
                    </div>
                </div>

                <!-- Import Section -->
                <div style="background: #fff3cd; padding: 20px; border: 1px solid #ffeaa7; border-radius: 5px;">
                    <h4 style="margin-top: 0; color: #856404;"><?php _e('ورود تنظیمات', 'mini-crm'); ?></h4>
                    <div class="notice notice-warning inline" style="margin: 0 0 15px 0; padding: 10px;">
                        <p><strong><?php _e('توجه:', 'mini-crm'); ?></strong> <?php _e('وارد کردن تنظیمات جدید، تمام تنظیمات فعلی را جایگزین می‌کند. پیش از ادامه، حتماً یک نسخه پشتیبان از تنظیمات فعلی تهیه کنید.', 'mini-crm'); ?></p>
                    </div>
                    <div style="margin-top: 15px; opacity: 0.5;">
                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <label><?php _e('انتخاب فایل:', 'mini-crm'); ?></label>
                                </th>
                                <td>
                                    <input type="file" accept=".json" disabled />
                                    <p class="description"><?php _e('موقتاً غیرفعال شده است.', 'mini-crm'); ?></p>
                                </td>
                            </tr>
                        </table>
                        <p class="submit">
                            <input type="button" class="button button-primary" value="<?php esc_attr_e('وارد کردن تنظیمات (غیرفعال)', 'mini-crm'); ?>" disabled />
                        </p>
                    </div>
                </div>
            </div>

            <p class="submit">
                <input type="submit" name="mini_crm_save_settings_submit" id="submit-button-sticky" class="button button-primary" value="<?php echo esc_attr(__('ذخیره تنظیمات', 'mini-crm')); ?>" style="margin-top:25px;padding:10px 15px !important;font-size:1.1em !important;" />
            </p>
        </form>

        <!-- Separate forms for Import/Export (outside main form) -->
        <div style="display: none;">
            <!-- These forms are referenced by JavaScript for the import-export tab -->
        </div>
            
            <script type="text/javascript">
                jQuery(document).ready(function($){ 
                    // Tab functionality
                    $('.nav-tab-wrapper a').click(function(e){
                        e.preventDefault(); 
                        $('.nav-tab-wrapper a').removeClass('nav-tab-active'); 
                        $(this).addClass('nav-tab-active'); 
                        $('.settings-tab-content').hide(); 
                        $($(this).attr('href')).show();
                        
                        // Show/hide save button based on tab
                        if($(this).attr('href') === '#import-export') {
                            $('#submit-button-sticky').hide();
                        } else {
                            $('#submit-button-sticky').show();
                        }
                    }); 
                    
                    // Handle hash in URL
                    var hash=window.location.hash; 
                    if(hash && $('.nav-tab-wrapper a[href="'+hash+'"]').length){
                        $('.nav-tab-wrapper a[href="'+hash+'"]').click();
                    }else{
                        $('.nav-tab-wrapper a:first').click();
                    } 
                    
                    // Accordion functionality
                    if(typeof $.fn.accordion==='function'){
                        $("#sms-templates-accordion").accordion({heightStyle:"content",collapsible:true,active:false});
                    }else{
                        $("#sms-templates-accordion > div").show();
                    }
                    
                    // AJAX Export functionality
                    $('#mini-crm-ajax-export').on('click', function() {
                        var $button = $(this);
                        var originalText = $button.text();
                        var nonce = $button.data('nonce');
                        
                        $button.prop('disabled', true).text('در حال تهیه فایل...');
                        
                        $.ajax({
                            url: ajaxurl,
                            type: 'POST',
                            data: {
                                action: 'mini_crm_export_settings',
                                nonce: nonce
                            },
                            dataType: 'json',
                            success: function(response) {
                                if (response.success) {
                                    // Create and download file
                                    var blob = new Blob([response.data.content], {type: 'application/json'});
                                    var url = window.URL.createObjectURL(blob);
                                    var a = document.createElement('a');
                                    a.href = url;
                                    a.download = response.data.filename;
                                    document.body.appendChild(a);
                                    a.click();
                                    document.body.removeChild(a);
                                    window.URL.revokeObjectURL(url);
                                    
                                    alert('فایل تنظیمات با موفقیت دانلود شد.');
                                } else {
                                    alert('خطا: ' + (response.data.message || 'خطای ناشناخته'));
                                }
                            },
                            error: function() {
                                alert('خطا در ارتباط با سرور.');
                            },
                            complete: function() {
                                $button.prop('disabled', false).text(originalText);
                            }
                        });
                    });
                });
            </script>
        </form>
        

    </div>
    <style> /* Styles for settings page, same as before */
        .nav-tab-wrapper{margin-bottom:0;padding-bottom:0;} .settings-tab-content{padding:20px;border:1px solid #ccd0d4;border-top:none;background:#fff;margin-bottom:20px;} .settings-tab-content h3{margin-top:0;padding-bottom:.5em;border-bottom:1px solid #eee;font-size:1.3em;} #sms-templates-accordion .ui-accordion-header{background-color:#fdfdfd;border:1px solid #ddd;padding:10px 15px;margin-top:8px;cursor:pointer;font-weight:bold;border-radius:3px;} #sms-templates-accordion .ui-accordion-header:hover{background-color:#f5f5f5;} #sms-templates-accordion .ui-accordion-header.ui-state-active{background-color:#f0f0f0;border-bottom-left-radius:0;border-bottom-right-radius:0;} #sms-templates-accordion .ui-accordion-content{border:1px solid #ddd;border-top:none;padding:20px;background-color:#fff;border-bottom-left-radius:3px;border-bottom-right-radius:3px;} #sms-templates-accordion .form-table th{width:200px;} #submit-button-sticky{margin-top:25px;padding:10px 15px !important;font-size:1.1em !important;}
    </style>
    <?php
}

// AJAX Export Handler (Alternative method)
function mini_crm_export_settings_ajax() {
    check_ajax_referer('mini_crm_export_nonce', 'nonce');
    
    if (!mini_crm_user_can_access_settings()) {
        wp_send_json_error(['message' => __('شما دسترسی لازم برای این کار را ندارید.', 'mini-crm')]);
    }
    
    $settings = get_option(MINI_CRM_SETTINGS_SLUG, []);
    
    // Remove sensitive password from export for security
    if (isset($settings['api_password'])) {
        $settings['api_password'] = '';
    }
    
    $export_data = [
        'version' => '1.0',
        'plugin' => 'Mini CRM',
        'export_date' => current_time('mysql'),
        'settings' => $settings
    ];
    
    $filename = 'mini-crm-settings-' . date('Y-m-d-H-i-s') . '.json';
    
    wp_send_json_success([
        'content' => json_encode($export_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
        'filename' => $filename
    ]);
}
add_action('wp_ajax_mini_crm_export_settings', 'mini_crm_export_settings_ajax');
?>