<?php
/*
Plugin Name: Mini CRM
Description: A simple CRM plugin for WordPress with form, admin panel, SMS (MeliPayamak BodyID), and tracking.
Version: 2.6
Author: Your Name
Text Domain: mini-crm
Domain Path: /languages
*/

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

define('MINI_CRM_VERSION', '2.6');
define('MINI_CRM_SETTINGS_SLUG', 'mini_crm_settings_options');

// Load plugin textdomain for internationalization
function mini_crm_load_textdomain() {
    load_plugin_textdomain('mini-crm', false, dirname(plugin_basename(__FILE__)) . '/languages/');
}
add_action('plugins_loaded', 'mini_crm_load_textdomain');


// Enqueue admin styles and scripts
function mini_crm_enqueue_admin_assets($hook) {
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

    if (isset($_GET['page']) && $_GET['page'] === MINI_CRM_SETTINGS_SLUG) {
        wp_enqueue_script('jquery-ui-accordion');
    }

    wp_enqueue_script(
        'mini-crm-admin-script',
        plugin_dir_url(__FILE__) . 'assets/js/admin-script.js',
        ['jquery', 'jquery-ui-accordion','persian-datepicker-script'],
        MINI_CRM_VERSION,
        true
    );


    // Enqueue Persian Datepicker assets
    wp_enqueue_style(
        'persian-datepicker-style',
        plugin_dir_url(__FILE__) . 'assets/css/persian-datepicker.min.css', // مسیر صحیح فایل CSS
        [],
        '1.2.0' // نسخه کتابخانه ای که استفاده می کنید
    );
    wp_enqueue_script(
        'persian-datepicker-script',
        plugin_dir_url(__FILE__) . 'assets/js/persian-datepicker.min.js', // مسیر صحیح فایل JS
        ['jquery'], // persian-datepicker به jQuery نیاز دارد
        '1.2.0', // نسخه کتابخانه
        true
    );

    wp_localize_script('mini-crm-admin-script', 'miniCrmAdminAjax', [
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce_update' => wp_create_nonce('mini_crm_update_contact_details'),
        'nonce_filter' => wp_create_nonce('mini_crm_filter_search_contacts'),
        'nonce_send_manual_sms' => wp_create_nonce('mini_crm_send_manual_sms_action'),
        'text' => [
            'confirm_sms' => __('آیا از ارسال این پیامک مطمئن هستید؟', 'mini-crm'),
            'sms_sent_success' => __('پیامک با موفقیت ارسال شد.', 'mini-crm'),
            'sms_sent_failed' => __('خطا در ارسال پیامک.', 'mini-crm'),
            'loading' => __('در حال بارگذاری...', 'mini-crm'),
            'error_loading' => __('خطا در بارگذاری اطلاعات.', 'mini-crm'),
            'error_server' => __('خطای ارتباط با سرور.', 'mini-crm'),
            'error_update' => __('خطا در به‌روزرسانی', 'mini-crm'),
            'error_sending_sms' => __('خطای ارتباط با سرور هنگام ارسال پیامک.', 'mini-crm'),
            'sending' => __('در حال ارسال...', 'mini-crm'),
        ]
    ]);
}
add_action('admin_enqueue_scripts', 'mini_crm_enqueue_admin_assets');


// Enqueue frontend styles and scripts (No changes from previous version)
function mini_crm_enqueue_frontend_styles() {
    wp_enqueue_style(
        'mini-crm-frontend-style',
        plugin_dir_url(__FILE__) . 'assets/css/front-style.css',
        [],
        '1.3'
    );
    wp_enqueue_script(
        'mini-crm-frontend-script',
        plugin_dir_url(__FILE__) . 'assets/js/frontend-script.js',
        ['jquery'],
        '1.1',
        true
    );
    wp_localize_script('mini-crm-frontend-script', 'miniCrmAjax', [
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('mini_crm_frontend_form_nonce')
    ]);
}
add_action('wp_enqueue_scripts', 'mini_crm_enqueue_frontend_styles');

// Create database table on plugin activation and handle updates (No changes from previous version)
function mini_crm_install_and_migrate() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'mini_crm_contacts';
    $charset_collate = $wpdb->get_charset_collate();
    $current_db_version = get_option("mini_crm_db_version");
    $target_db_version = '1.2';

    if ($current_db_version != $target_db_version) {
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            full_name varchar(255) NOT NULL,
            phone varchar(20) NOT NULL,
            status varchar(50) DEFAULT 'registered' NOT NULL,
            channel varchar(50) NOT NULL,
            sub_status varchar(50) DEFAULT '' NOT NULL,
            visit_date datetime,
            visit_end_time time,
            visit_note text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id),
            KEY phone_idx (phone),
            KEY status_idx (status),
            KEY created_at_idx (created_at)
        ) $charset_collate;";
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
        mini_crm_run_specific_migrations($current_db_version, $target_db_version);
        update_option("mini_crm_db_version", $target_db_version);
    }
    update_option("mini_crm_plugin_version", MINI_CRM_VERSION);
}
register_activation_hook(__FILE__, 'mini_crm_install_and_migrate');
add_action('plugins_loaded', 'mini_crm_install_and_migrate');

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
}

// Shortcode for contact form (No changes from previous version)
function mini_crm_contact_form_shortcode($atts) {
    $atts = shortcode_atts(['channel' => 'website_form'], $atts, 'contact_form');
    ob_start();
    ?>
    <form method="post" action="" class="mini-crm-contact-form" id="mini-crm-contact-form">
        <input type="hidden" name="form_channel" value="<?php echo esc_attr($atts['channel']); ?>">
        <p>
            <label for="mini_crm_full_name"><?php _e('نام و نام خانوادگی:', 'mini-crm'); ?></label>
            <input type="text" name="full_name" id="mini_crm_full_name" required>
        </p>
        <p>
            <label for="mini_crm_phone"><?php _e('شماره تلفن:', 'mini-crm'); ?></label>
            <input type="tel" name="phone" id="mini_crm_phone" pattern="[0-9۰-۹]{10,11}" title="<?php esc_attr_e('شماره تلفن باید 10 یا 11 رقم باشد', 'mini-crm'); ?>" required>
        </p>
        <?php wp_nonce_field('mini_crm_frontend_form_nonce', 'mini_crm_nonce_field'); ?>
        <p>
            <input type="submit" name="mini_crm_submit" value="<?php esc_attr_e('ثبت و ادامه', 'mini-crm'); ?>">
            <div class="mini-crm-message mini-crm-success" id="mini-crm-success-message" style="display:none;"></div>
            <div class="mini-crm-message mini-crm-error" id="mini-crm-error-message" style="display:none;"></div>
        </p>
    </form>
    <?php
    return ob_get_clean();
}
add_shortcode('contact_form', 'mini_crm_contact_form_shortcode');

// Handle form submission (AJAX) (No changes from previous version for this part)
function mini_crm_handle_form_submission_ajax() {
    check_ajax_referer('mini_crm_frontend_form_nonce', 'mini_crm_nonce_field');
    global $wpdb;
    $table_name = $wpdb->prefix . 'mini_crm_contacts';
    $full_name = isset($_POST['full_name']) ? sanitize_text_field(trim($_POST['full_name'])) : '';
    $phone = isset($_POST['phone']) ? sanitize_text_field(trim($_POST['phone'])) : '';
    $channel = isset($_POST['form_channel']) ? sanitize_text_field($_POST['form_channel']) : 'website_form';

    if (empty($full_name) || empty($phone)) {
        wp_send_json_error(['message' => __('نام و شماره تلفن نمی‌توانند خالی باشند.', 'mini-crm')]);
    }
    $phone = str_replace(['۰','۱','۲','۳','۴','۵','۶','۷','۸','۹'], ['0','1','2','3','4','5','6','7','8','9'], $phone);
    if (!preg_match('/^(09|9)[0-9]{9}$/', $phone)) {
        wp_send_json_error(['message' => __('شماره تلفن نامعتبر است. باید با 09 یا 9 شروع شود و 11 یا 10 رقم باشد.', 'mini-crm')]);
    }

    $result = $wpdb->insert($table_name, [
        'full_name' => $full_name,
        'phone' => $phone,
        'status' => 'registered',
        'channel' => $channel,
        'created_at' => current_time('mysql', 1)
    ]);

    if ($result !== false) {
        $contact_id = $wpdb->insert_id;
        mini_crm_trigger_sms_notifications($contact_id, 'form_submission', ['channel' => $channel]);
        wp_send_json_success(['message' => __('اطلاعات شما با موفقیت ثبت شد. به زودی با شما تماس خواهیم گرفت.', 'mini-crm')]);
    } else {
        wp_send_json_error(['message' => __('خطا در ثبت اطلاعات. لطفاً دوباره تلاش کنید یا با ما تماس بگیرید.', 'mini-crm') . ' ' . $wpdb->last_error]);
    }
}
add_action('wp_ajax_mini_crm_handle_form', 'mini_crm_handle_form_submission_ajax');
add_action('wp_ajax_nopriv_mini_crm_handle_form', 'mini_crm_handle_form_submission_ajax');

// Admin Menus (No changes from previous version)
function mini_crm_admin_menus() {
    add_menu_page(
        __('Mini CRM', 'mini-crm'),__('Mini CRM', 'mini-crm'), 'manage_options',
        'mini-crm-main', 'mini_crm_contacts_list_page', 'dashicons-groups', 25
    );
    add_submenu_page(
        'mini-crm-main', __('لیست تماس‌ها', 'mini-crm'), __('لیست تماس‌ها', 'mini-crm'),
        'manage_options', 'mini-crm-main', 'mini_crm_contacts_list_page'
    );
    add_submenu_page(
        'mini-crm-main', __('افزودن تماس جدید', 'mini-crm'), __('افزودن تماس', 'mini-crm'),
        'manage_options', 'mini-crm-add-new', 'mini_crm_add_new_contact_page_callback'
    );
    add_submenu_page(
        'mini-crm-main', __('تنظیمات Mini CRM', 'mini-crm'), __('تنظیمات', 'mini-crm'),
        'manage_options', MINI_CRM_SETTINGS_SLUG, 'mini_crm_settings_page_render_callback'
    );
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
function mini_crm_format_datetime_for_display($datetime_string_gmt, $format = 'Y/m/d H:i') {
    if (empty($datetime_string_gmt) || $datetime_string_gmt === '0000-00-00 00:00:00' || $datetime_string_gmt === null) return '-';
    try {
        // تاریخ در دیتابیس GMT است، آن را به timezone سایت تبدیل می‌کنیم
        $datetime_obj_gmt = new DateTime($datetime_string_gmt, new DateTimeZone('GMT'));
        $datetime_obj_gmt->setTimezone(wp_timezone());
        $timestamp_site_tz = $datetime_obj_gmt->getTimestamp();

        if (function_exists('jdate')) { // اگر wp-parsidate فعال است
            return jdate($format, $timestamp_site_tz, '', wp_timezone_string(), 'fa');
        }
        if (function_exists('wp_date') && (get_locale() === 'fa_IR' || strpos(get_locale(), 'fa_') === 0) ) {
            return wp_date($format, $timestamp_site_tz, wp_timezone());
        }
        // فال‌بک به میلادی با timezone سایت
        return $datetime_obj_gmt->format($format);

    } catch (Exception $e) {
        return $datetime_string_gmt; // در صورت خطا، خود رشته را برگردان
    }
}

function mini_crm_format_time_for_display($time_string_db, $format = 'H:i') { // زمان در دیتابیس بدون timezone خاصی ذخیره شده (نوع TIME)
    if (empty($time_string_db) || $time_string_db === '00:00:00' || $time_string_db === null) return '-';
    try {
        // چون TIME است، مستقیماً فرمت می‌کنیم
        $time_obj = DateTime::createFromFormat('H:i:s', $time_string_db);
        return $time_obj ? $time_obj->format($format) : $time_string_db;
    } catch (Exception $e) {
        return $time_string_db;
    }
}


// Admin page callback (list of contacts) (No changes from previous version)
function mini_crm_contacts_list_page() {
    global $wpdb; $table_name = $wpdb->prefix . 'mini_crm_contacts';
    $initial_contacts = $wpdb->get_results("SELECT * FROM $table_name ORDER BY created_at DESC LIMIT 20");
    $total_contacts = $wpdb->get_var("SELECT COUNT(id) FROM $table_name"); $per_page = 20; $total_pages = ceil($total_contacts / $per_page);
    ?>
    <div class="wrap mini-crm-wrap">
        <h1><?php _e('Mini CRM - لیست تماس‌ها', 'mini-crm'); ?> <a href="<?php echo admin_url('admin.php?page=mini-crm-add-new'); ?>" class="page-title-action"><?php _e('افزودن جدید', 'mini-crm'); ?></a></h1>
        <div class="mini-crm-controls">
            <div class="mini-crm-search"><input type="text" id="mini-crm-search-input" placeholder="<?php esc_attr_e('جستجو در نام، تلفن...', 'mini-crm'); ?>"><button type="button" class="button" id="mini-crm-search-button"><span class="dashicons dashicons-search"></span> <?php _e('جستجو', 'mini-crm'); ?></button></div>
            <div class="mini-crm-filter"><label for="mini-crm-filter-status"><?php _e('فیلتر بر اساس وضعیت:', 'mini-crm'); ?></label><select id="mini-crm-filter-status"><option value="all"><?php _e('همه وضعیت‌ها', 'mini-crm'); ?></option><option value="registered"><?php echo esc_html(mini_crm_get_status_label('registered')); ?></option><option value="REJECT"><?php echo esc_html(mini_crm_get_status_label('REJECT')); ?></option><option value="PEND"><?php echo esc_html(mini_crm_get_status_label('PEND')); ?></option><option value="ACCEPT"><?php echo esc_html(mini_crm_get_status_label('ACCEPT')); ?></option></select><button type="button" class="button" id="mini-crm-filter-button"><?php _e('اعمال فیلتر', 'mini-crm'); ?></button></div>
        </div>
        <div class="mini-crm-table-wrap"><table class="wp-list-table widefat fixed striped contacts-table"><thead><tr><th scope="col" class="manage-column column-cb check-column"><input type="checkbox" /></th><th scope="col"><?php _e('نام کامل', 'mini-crm'); ?></th><th scope="col"><?php _e('شماره تلفن', 'mini-crm'); ?></th><th scope="col"><?php _e('وضعیت', 'mini-crm'); ?></th><th scope="col"><?php _e('وضعیت فرعی', 'mini-crm'); ?></th><th scope="col"><?php _e('کانال', 'mini-crm'); ?></th><th scope="col" style="width:15%;"><?php _e('بازدید (شروع)', 'mini-crm'); ?></th><th scope="col" style="width:10%;"><?php _e('بازدید (پایان)', 'mini-crm'); ?></th><th scope="col"><?php _e('یادداشت بازدید', 'mini-crm'); ?></th><th scope="col"><?php _e('تاریخ ثبت', 'mini-crm'); ?></th><th scope="col" style="width:18%;"><?php _e('عملیات پیامک', 'mini-crm'); ?></th></tr></thead><tbody id="mini-crm-contacts-tbody"><?php if ($initial_contacts) { foreach ($initial_contacts as $contact) echo mini_crm_render_contact_row_html($contact); } else { echo '<tr><td colspan="11">' . __('هیچ تماسی یافت نشد.', 'mini-crm') . '</td></tr>'; } ?></tbody></table></div>
        <div class="mini-crm-pagination" id="mini-crm-pagination-container"><?php echo paginate_links(['total' => $total_pages, 'current' => 1, 'format' => '?paged=%#%', 'prev_text' => __('&laquo; قبلی', 'mini-crm'), 'next_text' => __('بعدی &raquo;', 'mini-crm'),]); ?></div>
        <div id="mini-crm-ajax-message-global" class="mini-crm-ajax-message" style="display:none;"></div>
    </div><?php
}

// Function to render a single contact row HTML (No changes from previous version)
function mini_crm_render_contact_row_html($contact) {
    ob_start();
    $contact_id = $contact->id;
    $can_edit_visit = $contact->status === 'ACCEPT';
    $can_edit_sub_status = $contact->status === 'PEND';

    // تاریخ و زمان بازدید را برای استفاده در فیلدها آماده می‌کنیم
    $visit_datetime_obj = null;
    $gregorian_date_for_altfield = '';
    $time_for_input = '';
    if ($contact->visit_date && $contact->visit_date !== '0000-00-00 00:00:00') {
        try {
            // تاریخ بازدید در دیتابیس به وقت GMT ذخیره شده (با current_time('mysql', 1))
            // برای نمایش و مقداردهی اولیه، آن را به timezone سایت تبدیل می‌کنیم
            $visit_datetime_obj = new DateTime($contact->visit_date, new DateTimeZone('GMT'));
            $visit_datetime_obj->setTimezone(wp_timezone());
            $gregorian_date_for_altfield = $visit_datetime_obj->format('Y-m-d'); // برای altField تاریخ
            $time_for_input = $visit_datetime_obj->format('H:i');          // برای input type="time"
        } catch (Exception $e) {
            // در صورت خطا، مقادیر خالی می‌مانند
        }
    }
    ?>
    <tr id="contact-row-<?php echo esc_attr($contact_id); ?>" data-contact-id="<?php echo esc_attr($contact_id); ?>">
        <th scope="row" class="check-column"><input type="checkbox" name="contact_ids[]" value="<?php echo esc_attr($contact_id); ?>" /></th>
        <td><?php echo esc_html($contact->full_name); ?></td>
        <td><?php echo esc_html($contact->phone); ?></td>
        <td>
            <select class="contact-dynamic-update" data-field="status" data-original-value="<?php echo esc_attr($contact->status); ?>">
                <option value="registered" <?php selected($contact->status, 'registered'); ?>><?php echo esc_html(mini_crm_get_status_label('registered')); ?></option>
                <option value="REJECT" <?php selected($contact->status, 'REJECT'); ?>><?php echo esc_html(mini_crm_get_status_label('REJECT')); ?></option>
                <option value="PEND" <?php selected($contact->status, 'PEND'); ?>><?php echo esc_html(mini_crm_get_status_label('PEND')); ?></option>
                <option value="ACCEPT" <?php selected($contact->status, 'ACCEPT'); ?>><?php echo esc_html(mini_crm_get_status_label('ACCEPT')); ?></option>
            </select>
        </td>
        <td class="sub-status-cell">
            <?php if ($can_edit_sub_status): ?>
                <select class="contact-dynamic-update" data-field="sub_status" data-original-value="<?php echo esc_attr($contact->sub_status); ?>">
                    <?php foreach (['', 'project_close', 'project_not_suitable'] as $sub_key): ?>
                    <option value="<?php echo esc_attr($sub_key); ?>" <?php selected($contact->sub_status, $sub_key); ?>><?php echo esc_html(mini_crm_get_pend_sub_status_label($sub_key)); ?></option>
                    <?php endforeach; ?>
                </select>
            <?php else: echo '-'; endif; ?>
        </td>
        <td><?php echo esc_html(mini_crm_get_channel_label($contact->channel)); ?></td>
        <td class="visit-date-cell">
            <?php if ($can_edit_visit): ?>
                <input type="text"
                       class="contact-dynamic-update mini-crm-persian-datepicker-input"
                       data-field="visit_date_display" data-gregorian-date-init="<?php echo esc_attr($gregorian_date_for_altfield); ?>" value="<?php echo esc_attr($visit_datetime_obj ? $visit_datetime_obj->format('Y/m/d') : ''); // نمایش اولیه شمسی فقط تاریخ ?>"
                       placeholder="<?php esc_attr_e('انتخاب تاریخ', 'mini-crm'); ?>"
                       style="width: calc(60% - 5px); margin-right: 5px; display: inline-block; vertical-align: middle;">
                <input type="hidden"
                       class="contact-visit-date-gregorian-alt"
                       data-field="visit_date_gregorian_alt" value="<?php echo esc_attr($gregorian_date_for_altfield); ?>">
                <input type="time"
                       class="contact-dynamic-update mini-crm-visit-time-input"
                       data-field="visit_time_start"
                       value="<?php echo esc_attr($time_for_input); ?>"
                       style="width: 35%; display: inline-block; vertical-align: middle;">
                <small><?php echo esc_html(mini_crm_format_datetime_for_display($contact->visit_date)); // نمایش کامل شمسی و زمان ?></small>
            <?php else: echo esc_html(mini_crm_format_datetime_for_display($contact->visit_date)); endif; ?>
        </td>
        <td class="visit-end-time-cell">
             <?php if ($can_edit_visit): ?>
                <input type="time" class="contact-dynamic-update" data-field="visit_end_time" value="<?php echo esc_attr($contact->visit_end_time ? gmdate('H:i', strtotime('1970-01-01 ' . $contact->visit_end_time . ' GMT')) : ''); // زمان پایان بازدید ?>">
                <small><?php echo esc_html(mini_crm_format_time_for_display($contact->visit_end_time)); ?></small>
            <?php else: echo esc_html(mini_crm_format_time_for_display($contact->visit_end_time)); endif; ?>
        </td>
        <td class="visit-note-cell">
            <?php if ($can_edit_visit): ?>
                <textarea class="contact-dynamic-update" data-field="visit_note" rows="2"><?php echo esc_textarea($contact->visit_note); ?></textarea>
            <?php else: echo '-'; endif; ?>
        </td>
        <td><?php echo esc_html(mini_crm_format_datetime_for_display($contact->created_at, 'Y/m/d')); ?></td>
        <td class="manual-sms-actions">
            <?php /* ... دکمه های پیامک دستی مانند قبل ... */
            $manual_sms_buttons = [ /* ... */ ];
            foreach ($manual_sms_buttons as $sms_key => $label) echo '<button type="button" class="button button-secondary button-small mini-crm-send-manual-sms" data-sms-type="' . esc_attr($sms_key) . '">' . esc_html($label) . '</button>';
            ?>
        </td>
    </tr>
    <?php
    return ob_get_clean();
}

// Add new contact page callback (No changes from previous version)
function mini_crm_add_new_contact_page_callback() {
    // ... (بخش پردازش فرم POST) ...
    if (isset($_POST['mini_crm_admin_add_submit']) && check_admin_referer('mini_crm_add_contact_nonce', 'mini_crm_add_contact_nonce_field')) {
        // ... (سایر فیلدها مانند قبل) ...
        $visit_date_gregorian_from_alt = isset($_POST['visit_date_gregorian_alt']) ? sanitize_text_field($_POST['visit_date_gregorian_alt']) : null; // YYYY-MM-DD
        $visit_time_start_str = isset($_POST['visit_time_start']) ? sanitize_text_field($_POST['visit_time_start']) : null; // HH:MM

        if ($status === 'ACCEPT') {
            if (!empty($visit_date_gregorian_from_alt) && !empty($visit_time_start_str)) {
                $datetime_str_to_save = $visit_date_gregorian_from_alt . ' ' . $visit_time_start_str . ':00'; // Add seconds
                // Convert to GMT for storage
                try {
                    $dt_site_tz = new DateTime($datetime_str_to_save, wp_timezone());
                    $dt_site_tz->setTimezone(new DateTimeZone('GMT'));
                    $data_to_insert['visit_date'] = $dt_site_tz->format('Y-m-d H:i:s');
                } catch (Exception $e) {
                    // Handle invalid date/time format if necessary
                    $data_to_insert['visit_date'] = null;
                }
            } else {
                $data_to_insert['visit_date'] = null;
            }
            // ... (بقیه فیلدهای visit_end_time و visit_note مانند قبل) ...
             $visit_end_time_raw = isset($_POST['visit_end_time']) ? sanitize_text_field($_POST['visit_end_time']) : null;
             if (!empty($visit_end_time_raw)) {
                 $time_obj = DateTime::createFromFormat('H:i', $visit_end_time_raw); // Time is timezone-agnostic in this context
                 if ($time_obj) $data_to_insert['visit_end_time'] = $time_obj->format('H:i:s');
             }
             $data_to_insert['visit_note'] = isset($_POST['visit_note']) ? sanitize_textarea_field($_POST['visit_note']) : null;
        }
        // ... (بخش insert و trigger SMS مانند قبل) ...
    }
    ?>
    <div class="wrap mini-crm-wrap">
        <h1><?php _e('افزودن تماس جدید', 'mini-crm'); ?></h1>
        <form method="post" action="" class="mini-crm-form">
            <?php wp_nonce_field('mini_crm_add_contact_nonce', 'mini_crm_add_contact_nonce_field'); ?>
            <table class="form-table">
                <tr valign="top"><th scope="row"><label for="add_full_name"><?php _e('نام کامل:', 'mini-crm'); ?></label></th><td><input type="text" name="full_name" id="add_full_name" class="regular-text" value="<?php echo isset($_POST['full_name']) ? esc_attr($_POST['full_name']) : ''; ?>" required></td></tr>
                <tr valign="top"><th scope="row"><label for="add_phone"><?php _e('شماره تلفن:', 'mini-crm'); ?></label></th><td><input type="tel" name="phone" id="add_phone" class="regular-text" pattern="[0-9۰-۹]{10,11}" title="<?php esc_attr_e('شماره تلفن باید 10 یا 11 رقم باشد', 'mini-crm'); ?>" value="<?php echo isset($_POST['phone']) ? esc_attr($_POST['phone']) : ''; ?>" required></td></tr>
                <tr valign="top"><th scope="row"><label for="add_status"><?php _e('وضعیت:', 'mini-crm'); ?></label></th><td><select name="status" id="add_contact_status"><?php foreach (['registered', 'REJECT', 'PEND', 'ACCEPT'] as $s_key): ?><option value="<?php echo esc_attr($s_key); ?>" <?php selected(isset($_POST['status']) ? $_POST['status'] : 'registered', $s_key); ?>><?php echo esc_html(mini_crm_get_status_label($s_key)); ?></option><?php endforeach; ?></select></td></tr>
                <tr valign="top"><th scope="row"><label for="add_channel"><?php _e('کانال ورودی:', 'mini-crm'); ?></label></th><td><select name="channel" id="add_channel" required><?php foreach (['manual_add', 'direct_call', 'website_form', 'instagram', 'telegram'] as $c_key): ?><option value="<?php echo esc_attr($c_key); ?>" <?php selected(isset($_POST['channel']) ? $_POST['channel'] : 'manual_add', $c_key); ?>><?php echo esc_html(mini_crm_get_channel_label($c_key)); ?></option><?php endforeach; ?></select></td></tr>

                <tbody id="add-visit-details-section" style="<?php echo (isset($_POST['status']) && $_POST['status'] === 'ACCEPT') || (empty($_POST) && 'ACCEPT' === 'registered') ? '' : 'display:none;'; ?>">
                    <tr valign="top">
                        <th scope="row"><label for="add_visit_date_display_field"><?php _e('تاریخ شروع بازدید (شمسی):', 'mini-crm'); ?></label></th>
                        <td>
                            <input type="text" id="add_visit_date_display_field" name="visit_date_display" class="regular-text mini-crm-persian-datepicker-input" value="<?php echo isset($_POST['visit_date_display']) ? esc_attr($_POST['visit_date_display']) : ''; ?>" placeholder="<?php esc_attr_e('مثال: ۱۴۰۳/۰۳/۱۰', 'mini-crm'); ?>">
                            <input type="hidden" name="visit_date_gregorian_alt" id="add_visit_date_gregorian_alt_field" value="<?php echo isset($_POST['visit_date_gregorian_alt']) ? esc_attr($_POST['visit_date_gregorian_alt']) : ''; ?>">
                            <p class="description"><?php _e('تاریخ را از تقویم شمسی انتخاب کنید.', 'mini-crm'); ?></p>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><label for="add_visit_time_start_field"><?php _e('ساعت شروع بازدید:', 'mini-crm'); ?></label></th>
                        <td><input type="time" name="visit_time_start" id="add_visit_time_start_field" class="regular-text" value="<?php echo isset($_POST['visit_time_start']) ? esc_attr($_POST['visit_time_start']) : ''; ?>"></td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><label for="add_visit_end_time_field"><?php _e('ساعت پایان بازدید:', 'mini-crm'); ?></label></th>
                        <td><input type="time" name="visit_end_time" id="add_visit_end_time_field" class="regular-text" value="<?php echo isset($_POST['visit_end_time']) ? esc_attr($_POST['visit_end_time']) : ''; ?>"></td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><label for="add_visit_note_field"><?php _e('یادداشت بازدید:', 'mini-crm'); ?></label></th>
                        <td><textarea name="visit_note" id="add_visit_note_field" rows="3" class="large-text"><?php echo isset($_POST['visit_note']) ? esc_textarea($_POST['visit_note']) : ''; ?></textarea></td>
                    </tr>
                </tbody>
            </table>
            <p class="submit"><input type="submit" name="mini_crm_admin_add_submit" class="button button-primary" value="<?php esc_attr_e('افزودن تماس', 'mini-crm'); ?>"></p>
        </form>
    </div>
    <script type="text/javascript">
        jQuery(document).ready(function($) {
            function toggleVisitDetails() {
                if ($('#add_contact_status').val() === 'ACCEPT') {
                    $('#add-visit-details-section').slideDown();
                } else {
                    $('#add-visit-details-section').slideUp();
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
    // ... (بررسی دسترسی کاربر مانند قبل) ...
    if (!current_user_can('manage_options')) wp_send_json_error(['message' => __('شما دسترسی لازم برای این کار را ندارید.', 'mini-crm')]);

    global $wpdb;
    $table_name = $wpdb->prefix . 'mini_crm_contacts';
    $contact_id = isset($_POST['contact_id']) ? intval($_POST['contact_id']) : 0;
    $field_to_update = isset($_POST['field']) ? sanitize_key($_POST['field']) : '';
    $value = isset($_POST['value']) ? stripslashes_deep($_POST['value']) : ''; // مقدار اصلی از فیلد تغییر یافته
    
    // مقادیر اضافی که ممکن است برای تاریخ و زمان ارسال شوند
    $gregorian_date_alt = isset($_POST['gregorian_date_alt']) ? sanitize_text_field($_POST['gregorian_date_alt']) : null; // YYYY-MM-DD
    $time_start_val = isset($_POST['time_start_val']) ? sanitize_text_field($_POST['time_start_val']) : null;       // HH:MM

    // ... (بررسی contact_id و field_to_update مانند قبل) ...
    if (!$contact_id || empty($field_to_update)) wp_send_json_error(['message' => __('اطلاعات ناقص برای به‌روزرسانی.', 'mini-crm')]);
    $update_data = []; $event_type = null;
    $contact_before_update = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $contact_id));
    if (!$contact_before_update) wp_send_json_error(['message' => __('تماس یافت نشد.', 'mini-crm')]);


    switch ($field_to_update) {
        // ... (case های status, sub_status, visit_note, visit_end_time مانند قبل) ...
        case 'status': /* ... as before ... */ break;
        case 'sub_status': /* ... as before ... */ break;
        case 'visit_note': /* ... as before ... */ break;
        case 'visit_end_time': /* ... as before, ensure it saves H:i:s ... */
             if ($contact_before_update->status !== 'ACCEPT') wp_send_json_error(['message' => __('ساعت پایان بازدید فقط برای وضعیت "پذیرفته شده" قابل تنظیم است.', 'mini-crm')]);
             $time_val = sanitize_text_field($value); $time_obj = DateTime::createFromFormat('H:i', $time_val); $update_data['visit_end_time'] = $time_obj ? $time_obj->format('H:i:s') : null;
             if ($update_data['visit_end_time'] && $contact_before_update->visit_date && $contact_before_update->visit_date !== '0000-00-00 00:00:00') $event_type = 'visit_scheduled';
             break;

        // مورد مهم: مدیریت تاریخ و زمان شروع بازدید که اکنون جدا شده‌اند
        case 'visit_date_gregorian_alt': // اگر تاریخ از datepicker (altField) تغییر کرد
        case 'visit_time_start':         // اگر زمان از input type="time" تغییر کرد
            if ($contact_before_update->status !== 'ACCEPT') {
                wp_send_json_error(['message' => __('تاریخ و زمان بازدید فقط برای وضعیت "پذیرفته شده" قابل تنظیم است.', 'mini-crm')]);
            }

            // از مقادیر POST که در JS ارسال کردیم استفاده می‌کنیم
            $gregorian_date_to_save = $gregorian_date_alt; // این باید YYYY-MM-DD باشد
            $time_start_to_save = $time_start_val;       // این باید HH:MM باشد

            if (empty($gregorian_date_to_save) || empty($time_start_to_save)) {
                // اگر یکی از آن‌ها خالی است، کل visit_date را null می‌کنیم یا خطا می‌دهیم
                // فعلاً اگر یکی خالی باشد، کلش را null در نظر میگیریم مگر اینکه منطق دیگری بخواهید
                 // $update_data['visit_date'] = null;
                 // برای جلوگیری از پاک شدن ناخواسته، اگر یکی خالی بود، از مقدار قبلی دیتابیس استفاده می‌کنیم
                if (empty($gregorian_date_to_save) && $contact_before_update->visit_date) {
                    $dt_old = new DateTime($contact_before_update->visit_date, new DateTimeZone('GMT'));
                    $dt_old->setTimezone(wp_timezone());
                    $gregorian_date_to_save = $dt_old->format('Y-m-d');
                }
                if (empty($time_start_to_save) && $contact_before_update->visit_date) {
                     if (!isset($dt_old)) {
                         $dt_old = new DateTime($contact_before_update->visit_date, new DateTimeZone('GMT'));
                         $dt_old->setTimezone(wp_timezone());
                     }
                     $time_start_to_save = $dt_old->format('H:i');
                }
            }
            
            if (!empty($gregorian_date_to_save) && !empty($time_start_to_save)) {
                 $datetime_str_to_save = $gregorian_date_to_save . ' ' . $time_start_to_save . ':00'; // افزودن ثانیه
                // تبدیل به GMT برای ذخیره در دیتابیس
                try {
                    $dt_site_tz = new DateTime($datetime_str_to_save, wp_timezone());
                    $dt_site_tz->setTimezone(new DateTimeZone('GMT'));
                    $update_data['visit_date'] = $dt_site_tz->format('Y-m-d H:i:s');
                    $event_type = 'visit_scheduled';
                } catch (Exception $e) {
                    // ارسال خطا اگر فرمت تاریخ/زمان ورودی اشتباه است
                    wp_send_json_error(['message' => __('فرمت تاریخ یا زمان شروع بازدید نامعتبر است.', 'mini-crm') . $e->getMessage()]);
                }
            } else {
                 // اگر تاریخ یا زمان معتبر نبود، مقدار قبلی را نگه دار یا null کن
                 // $update_data['visit_date'] = $contact_before_update->visit_date; // یا null
            }
            break;
        default:
            // wp_send_json_error(['message' => __('فیلد نامعتبر برای به‌روزرسانی.', 'mini-crm')]);
            // اگر فیلد یکی از موارد بالا نبود، به پردازش قبلی برگرد
            if (empty($update_data)) { /* ... مانند قبل ... */ }
    }

    if (empty($update_data) && $field_to_update !== 'visit_date_gregorian_alt' && $field_to_update !== 'visit_time_start' ) {
         wp_send_json_error(['message' => __('داده ای برای بروزرسانی وجود ندارد یا مقدار بدون تغییر است.', 'mini-crm') . " Field: $field_to_update"]);
    }
    
    // اگر هیچ داده‌ای برای آپدیت نبود (مثلاً فقط فیلدهای نامرتبط تغییر کرده‌اند)
    if (empty($update_data)) {
        // فقط ردیف را با داده‌های فعلی بازگردان تا UI هماهنگ بماند
        $current_contact_data = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $contact_id));
        wp_send_json_success([
            'message' => __('تغییری برای ذخیره وجود نداشت.', 'mini-crm'),
            'new_row_html' => mini_crm_render_contact_row_html($current_contact_data)
        ]);
        return;
    }


    $updated_rows = $wpdb->update($table_name, $update_data, ['id' => $contact_id]);

    if ($updated_rows !== false) {
        if ($event_type) {
            mini_crm_trigger_sms_notifications($contact_id, $event_type);
        }
        $new_contact_data = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $contact_id));
        wp_send_json_success([
            'message' => __('اطلاعات با موفقیت به‌روزرسانی شد.', 'mini-crm'),
            'new_row_html' => mini_crm_render_contact_row_html($new_contact_data)
        ]);
    } else {
        wp_send_json_error(['message' => __('خطا در به‌روزرسانی اطلاعات یا داده‌ای تغییر نکرده است.', 'mini-crm') . ' ' . $wpdb->last_error]);
    }
}
add_action('wp_ajax_mini_crm_update_contact_details', 'mini_crm_update_contact_details_ajax');

// AJAX handler for filtering/searching contacts (No changes from previous version)
function mini_crm_filter_search_contacts_ajax() {
    check_ajax_referer('mini_crm_filter_search_contacts', 'nonce');
    if (!current_user_can('manage_options')) wp_send_json_error(['message' => __('شما دسترسی لازم برای این کار را ندارید.', 'mini-crm')]);
    global $wpdb; $table_name = $wpdb->prefix . 'mini_crm_contacts';
    $status_filter = isset($_POST['status_filter']) ? sanitize_text_field($_POST['status_filter']) : 'all';
    $search_term = isset($_POST['search_term']) ? sanitize_text_field(trim($_POST['search_term'])) : '';
    $per_page = 20; $current_page = isset($_POST['paged']) ? max(1, intval($_POST['paged'])) : 1; $offset = ($current_page - 1) * $per_page;
    $sql_conditions = []; $sql_params = [];
    if ($status_filter !== 'all' && !empty($status_filter)) { $sql_conditions[] = "status = %s"; $sql_params[] = $status_filter; }
    if (!empty($search_term)) { $search_like = '%' . $wpdb->esc_like($search_term) . '%'; $sql_conditions[] = "(full_name LIKE %s OR phone LIKE %s)"; $sql_params[] = $search_like; $sql_params[] = $search_like; }
    $where_clause = !empty($sql_conditions) ? "WHERE " . implode(" AND ", $sql_conditions) : "";
    $count_query = "SELECT COUNT(id) FROM $table_name $where_clause";
    $total_contacts = empty($sql_params) ? $wpdb->get_var($count_query) : $wpdb->get_var($wpdb->prepare($count_query, $sql_params));
    $total_pages = ceil($total_contacts / $per_page);
    $data_query = "SELECT * FROM $table_name $where_clause ORDER BY created_at DESC LIMIT %d OFFSET %d";
    $final_sql_params = array_merge($sql_params, [$per_page, $offset]); $contacts = $wpdb->get_results($wpdb->prepare($data_query, $final_sql_params));
    $html_rows = ''; if ($contacts) { foreach ($contacts as $contact) $html_rows .= mini_crm_render_contact_row_html($contact); } else $html_rows = '<tr><td colspan="11">' . __('هیچ تماسی با این مشخصات یافت نشد.', 'mini-crm') . '</td></tr>';
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
        'links' => [ // These links can be used as arguments for some bodyIds
            'tehranidea_instagram_link' => 'http://instagram.com/tehranidea',
            'tehranidea_telegram_link' => 't.me/Tehran_idea',
            'tehranidea_website_link' => $base_url,
            'survey_link' => $base_url . 'survey-page/',
            'tehranidea_telegram_videoupload_link' => 't.me/your_tehranidea_contact_for_video',
            'tehranidea_whatsapp_videoupload_link' => 'https://wa.me/YOURPHONENUMBER?text=VideoUpload',
            'tehranidea_telegram_location_link' => 't.me/your_tehranidea_contact_for_location',
            'tehranidea_whatsapp_location_link' => 'https://wa.me/YOURPHONENUMBER?text=Location',
            'tehranidea_projects_link' => $base_url . 'projects/',
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
        'visit_scheduled' => ['', __('{0}: نام مشتری, {1}: تاریخ (Y/m/d), {2}: ساعت شروع (H:i), {3}: ساعت پایان (H:i)', 'mini-crm'), true], // SMS 5
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
 * Get SMS settings from options, falling back to defaults. (MODIFIED for body_id)
 */
function mini_crm_get_sms_settings() {
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
            $arguments[] = $contact_object->full_name ?? '';
            $arguments[] = ($contact_object->visit_date && $contact_object->visit_date !== '0000-00-00 00:00:00') ? mini_crm_format_datetime_for_display($contact_object->visit_date, 'Y/m/d') : __('?', 'mini-crm');
            $arguments[] = ($contact_object->visit_date && $contact_object->visit_date !== '0000-00-00 00:00:00') ? mini_crm_format_datetime_for_display($contact_object->visit_date, 'H:i') : __('?', 'mini-crm');
            $arguments[] = ($contact_object->visit_end_time && $contact_object->visit_end_time !== '00:00:00') ? mini_crm_format_time_for_display($contact_object->visit_end_time, 'H:i') : __('?', 'mini-crm');
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
        case 'custom_sms_10':$arguments[] = $sms_settings['links']['tehranidea_instagram_link'] ?? ''; break; // فقط بازسازی - وبسایت -> اینستا
        case 'custom_sms_11':$arguments[] = $sms_settings['links']['tehranidea_website_link'] ?? ''; break;    // فقط بازسازی - اینستا -> وبسایت
        
        case 'custom_sms_survey':
            $arguments[] = $contact_object->full_name ?? '';
            $arguments[] = $sms_settings['links']['survey_link'] ?? '';
            break;
    }
    // Sanitize arguments to prevent issues with semicolons or special chars if API is sensitive
    return array_map('sanitize_text_field', $arguments);
}


/**
 * Sends SMS using MeliPayamak API with BodyID and Arguments. (MODIFIED)
 */
function mini_crm_send_sms_via_melipayamak($phone_number, $body_id, $arguments_array = []) {
    $settings = mini_crm_get_sms_settings();
    $username = $settings['api_username'];
    $password = $settings['api_password'];
    // sender_number might not be used by SendByBaseNumber3, but keep it in settings

    if (empty($username) || empty($password)) {
        error_log("Mini CRM: MeliPayamak API credentials not set.");
        return ['success' => false, 'message' => 'API credentials not set.'];
    }
    if (empty($body_id)) {
        error_log("Mini CRM: Body ID is empty for sending SMS.");
        return ['success' => false, 'message' => 'Body ID cannot be empty.'];
    }
    
    $normalized_phone = preg_replace('/^(?:\+98|0)?/', '', $phone_number);
    $normalized_phone = str_replace(['۰','۱','۲','۳','۴','۵','۶','۷','۸','۹'], ['0','1','2','3','4','5','6','7','8','9'], $normalized_phone);

    if (!class_exists('SoapClient')) {
        error_log("Mini CRM: SoapClient class not found. PHP SOAP extension is required.");
        return ['success' => false, 'message' => 'PHP SOAP extension not installed.'];
    }

    // Construct the 'text' payload for MeliPayamak
    // Format: @BodyID@Arg1;Arg2;Arg3...
    // If no arguments, format should be @BodyID@
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

        $response_object = $sms_client->SendByBaseNumber3($data_to_send_api); // As per user's original code snippet
        $send_result_code = $response_object->SendByBaseNumber3Result;

        if (is_string($send_result_code) && strlen($send_result_code) > 10) { // Heuristic for a UID
             error_log("Mini CRM: SMS (BodyID: $body_id) sent successfully to $normalized_phone. Result (UID): $send_result_code. Payload: $text_payload");
            return ['success' => true, 'result_code' => $send_result_code, 'message' => __('پیامک ارسال شد.', 'mini-crm')];
        } elseif (is_numeric($send_result_code) && intval($send_result_code) > 0 && intval($send_result_code) < 100) { // Some APIs return small positive for success
             error_log("Mini CRM: SMS (BodyID: $body_id) sent successfully to $normalized_phone. Result code: $send_result_code. Payload: $text_payload");
            return ['success' => true, 'result_code' => $send_result_code, 'message' => __('پیامک ارسال شد. کد نتیجه: ', 'mini-crm') . $send_result_code];
        } else {
            $error_message_from_api = mini_crm_get_melipayamak_error_message($send_result_code);
            error_log("Mini CRM: Failed to send SMS (BodyID: $body_id) to $normalized_phone. API Response Code: $send_result_code. Error: $error_message_from_api. Payload: $text_payload");
            return ['success' => false, 'result_code' => $send_result_code, 'message' => __("خطا در ارسال پیامک: ", 'mini-crm') . "$error_message_from_api (" . __('کد:', 'mini-crm') . " $send_result_code)"];
        }

    } catch (SoapFault $e) {
        error_log("Mini CRM: SOAP Fault while sending SMS (BodyID: $body_id) to $normalized_phone - " . $e->getMessage() . " | Request: " . htmlentities($sms_client->__getLastRequest()) . " | Response: " . htmlentities($sms_client->__getLastResponse()));
        return ['success' => false, 'message' => __('خطای SOAP:', 'mini-crm') . ' ' . $e->getMessage()];
    } catch (Exception $e) {
        error_log("Mini CRM: General Exception while sending SMS (BodyID: $body_id) to $normalized_phone - " . $e->getMessage());
        return ['success' => false, 'message' => __('خطای عمومی:', 'mini-crm') . ' ' . $e->getMessage()];
    }
}

// Helper to interpret MeliPayamak error codes (No changes from previous version)
function mini_crm_get_melipayamak_error_message($code) { /* ... same as before ... */ return __('خطای ناشناخته از سرویس پیامک', 'mini-crm'); }


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
    
    $send_attempt_result = mini_crm_send_sms_via_melipayamak($contact->phone, $body_id, $arguments);
    
    if (!$send_attempt_result['success']) {
        error_log("Mini CRM: Failed to send SMS for event '$event_type' (BodyID: $body_id) to contact ID $contact_id. Reason: " . $send_attempt_result['message']);
    } else {
        error_log("Mini CRM: SMS for event '$event_type' (BodyID: $body_id) sent to contact ID $contact_id. Result: " . ($send_attempt_result['result_code'] ?? 'OK'));
    }
}

// AJAX handler for sending manual SMS (MODIFIED for body_id)
function mini_crm_send_manual_sms_ajax() {
    check_ajax_referer('mini_crm_send_manual_sms_action', 'nonce');
    if (!current_user_can('manage_options')) wp_send_json_error(['message' => __('شما دسترسی لازم برای این کار را ندارید.', 'mini-crm')]);

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
    $send_result = mini_crm_send_sms_via_melipayamak($contact->phone, $body_id, $arguments);

    if ($send_result['success']) {
        wp_send_json_success(['message' => $send_result['message'] . (isset($send_result['result_code']) ? " (کد: {$send_result['result_code']})" : '') ]);
    } else {
        wp_send_json_error(['message' => $send_result['message']]);
    }
}
add_action('wp_ajax_mini_crm_send_manual_sms', 'mini_crm_send_manual_sms_ajax');


// ----- Settings Page (MODIFIED for BodyID and Arg Guide) -----
function mini_crm_settings_page_render_callback() {
    if (!current_user_can('manage_options')) return;

    if (isset($_POST['mini_crm_save_settings_submit']) && check_admin_referer('mini_crm_settings_nonce_action', 'mini_crm_settings_nonce_field')) {
        $options_to_save = mini_crm_get_default_sms_configs(); // Start with defaults

        $options_to_save['api_username'] = isset($_POST[MINI_CRM_SETTINGS_SLUG]['api_username']) ? sanitize_text_field($_POST[MINI_CRM_SETTINGS_SLUG]['api_username']) : '';
        $options_to_save['api_password'] = isset($_POST[MINI_CRM_SETTINGS_SLUG]['api_password']) ? sanitize_text_field($_POST[MINI_CRM_SETTINGS_SLUG]['api_password']) : ''; // Consider not re-displaying password
        $options_to_save['sender_number'] = isset($_POST[MINI_CRM_SETTINGS_SLUG]['sender_number']) ? sanitize_text_field($_POST[MINI_CRM_SETTINGS_SLUG]['sender_number']) : '';

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
        update_option(MINI_CRM_SETTINGS_SLUG, $options_to_save);
        echo '<div id="setting-error-settings_updated" class="updated settings-error notice is-dismissible"><p><strong>' . __('تنظیمات با موفقیت ذخیره شد.', 'mini-crm') . '</strong></p></div>';
    }

    $current_settings = mini_crm_get_sms_settings();
    ?>
    <div class="wrap mini-crm-wrap">
        <h1><?php _e('تنظیمات Mini CRM', 'mini-crm'); ?></h1>
        <form method="post" action="">
            <?php wp_nonce_field('mini_crm_settings_nonce_action', 'mini_crm_settings_nonce_field'); ?>
            <h2 class="nav-tab-wrapper"><a href="#api-settings" class="nav-tab nav-tab-active"><?php _e('API ملی پیامک', 'mini-crm'); ?></a><a href="#link-settings" class="nav-tab"><?php _e('لینک‌های ثابت', 'mini-crm'); ?></a><a href="#template-settings" class="nav-tab"><?php _e('کدهای پترن پیامک', 'mini-crm'); ?></a></h2>

            <div id="api-settings" class="settings-tab-content">
                <h3><?php _e('تنظیمات اتصال به API ملی پیامک', 'mini-crm'); ?></h3>
                <table class="form-table">
                    <tr valign="top"><th scope="row"><label for="mini_crm_api_username_field"><?php _e('نام کاربری API:', 'mini-crm'); ?></label></th><td><input type="text" id="mini_crm_api_username_field" name="<?php echo MINI_CRM_SETTINGS_SLUG; ?>[api_username]" value="<?php echo esc_attr($current_settings['api_username']); ?>" class="regular-text" /></td></tr>
                    <tr valign="top"><th scope="row"><label for="mini_crm_api_password_field"><?php _e('رمز عبور API:', 'mini-crm'); ?></label></th><td><input type="password" id="mini_crm_api_password_field" name="<?php echo MINI_CRM_SETTINGS_SLUG; ?>[api_password]" value="" class="regular-text" placeholder="<?php _e('در صورت عدم تغییر خالی بگذارید', 'mini-crm'); ?>" /></td></tr>
                    <tr valign="top"><th scope="row"><label for="mini_crm_sender_number_field"><?php _e('شماره خط ارسال کننده:', 'mini-crm'); ?></label></th><td><input type="text" id="mini_crm_sender_number_field" name="<?php echo MINI_CRM_SETTINGS_SLUG; ?>[sender_number]" value="<?php echo esc_attr($current_settings['sender_number']); ?>" class="regular-text" placeholder="<?php esc_attr_e('مثال: 3000XXXX', 'mini-crm'); ?>" /><p class="description"><?php _e('این شماره ممکن است برای برخی متدهای ارسال ملی پیامک نیاز باشد.', 'mini-crm'); ?></p></td></tr>
                </table>
            </div>

            <div id="link-settings" class="settings-tab-content" style="display:none;">
                 <h3><?php _e('لینک‌های ثابت (برای استفاده در آرگومان‌های پیامک)', 'mini-crm'); ?></h3>
                <table class="form-table">
                    <?php foreach ($current_settings['links'] as $key => $value): ?>
                    <tr valign="top"><th scope="row"><label for="mini_crm_link_<?php echo esc_attr($key); ?>"><?php echo esc_html(ucwords(str_replace('_', ' ', str_replace('tehranidea_', '', $key)))); ?>:</label></th><td><input type="url" id="mini_crm_link_<?php echo esc_attr($key); ?>" name="<?php echo MINI_CRM_SETTINGS_SLUG; ?>[links][<?php echo esc_attr($key); ?>]" value="<?php echo esc_attr($value); ?>" class="large-text" /></td></tr>
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
            <script type="text/javascript"> /* JS for tabs and accordion, same as before */
                jQuery(document).ready(function($){ $('.nav-tab-wrapper a').click(function(e){e.preventDefault(); $('.nav-tab-wrapper a').removeClass('nav-tab-active'); $(this).addClass('nav-tab-active'); $('.settings-tab-content').hide(); $($(this).attr('href')).show();}); var hash=window.location.hash; if(hash && $('.nav-tab-wrapper a[href="'+hash+'"]').length){$('.nav-tab-wrapper a[href="'+hash+'"]').click();}else{$('.nav-tab-wrapper a:first').click();} if(typeof $.fn.accordion==='function'){$("#sms-templates-accordion").accordion({heightStyle:"content",collapsible:true,active:false});}else{$("#sms-templates-accordion > div").show();}});
            </script>
            <?php submit_button(__('ذخیره تنظیمات', 'mini-crm'), 'primary', 'mini_crm_save_settings_submit', true, ['id' => 'submit-button-sticky']); ?>
        </form>
    </div>
    <style> /* Styles for settings page, same as before */
        .nav-tab-wrapper{margin-bottom:0;padding-bottom:0;} .settings-tab-content{padding:20px;border:1px solid #ccd0d4;border-top:none;background:#fff;margin-bottom:20px;} .settings-tab-content h3{margin-top:0;padding-bottom:.5em;border-bottom:1px solid #eee;font-size:1.3em;} #sms-templates-accordion .ui-accordion-header{background-color:#fdfdfd;border:1px solid #ddd;padding:10px 15px;margin-top:8px;cursor:pointer;font-weight:bold;border-radius:3px;} #sms-templates-accordion .ui-accordion-header:hover{background-color:#f5f5f5;} #sms-templates-accordion .ui-accordion-header.ui-state-active{background-color:#f0f0f0;border-bottom-left-radius:0;border-bottom-right-radius:0;} #sms-templates-accordion .ui-accordion-content{border:1px solid #ddd;border-top:none;padding:20px;background-color:#fff;border-bottom-left-radius:3px;border-bottom-right-radius:3px;} #sms-templates-accordion .form-table th{width:200px;} #submit-button-sticky{margin-top:25px;padding:10px 15px !important;font-size:1.1em !important;}
    </style>
    <?php
}
?>