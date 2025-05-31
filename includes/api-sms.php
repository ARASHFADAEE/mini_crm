<?php
/**
 * Send SMS via external API
 */
function mini_crm_send_sms( $contact_id, $message ) {
    global $wpdb;
    $table = $wpdb->prefix . 'mini_crm_contacts';
    $contact = $wpdb->get_row( $wpdb->prepare("SELECT * FROM $table WHERE id=%d", $contact_id) );
    if ( ! $contact ) return;

    $api_url = 'https://your-sms-api.com/send';
    $payload = [
        'to'      => $contact->phone,
        'message' => $message,
        'api_key' => 'YOUR_API_KEY',
    ];
    $response = wp_remote_post( $api_url, [ 'body' => $payload ] );
    // Log success/fail
}

/**
 * Status update handler
 */
/**
 * Status update handler
 */
add_action( 'admin_post_update_minicrm', 'mini_crm_status_update' );
function mini_crm_status_update() {
    // 1. مطمئن شوید شناسه تماس ارسال شده
    if ( empty( $_POST['contact_id'] ) || ! isset( $_POST['status'][ $_POST['contact_id'] ] ) ) {
        wp_die( 'درخواست نامعتبر است.' );
    }

    $id = intval( $_POST['contact_id'] );
    $new_status = sanitize_text_field( $_POST['status'][ $id ] );

    // 2. اعتبارسنجی nonce
    $nonce_field = 'mini_crm_nonce_' . $id;
    $nonce_action = 'update_minicrm_' . $id;
    if ( ! isset( $_POST[ $nonce_field ] )
      || ! wp_verify_nonce( $_POST[ $nonce_field ], $nonce_action ) ) {
        wp_die( 'بررسی امنیتی انجام نشد.' );
    }

    // 3. دریافت وضعیت قبلی
    global $wpdb;
    $table = $wpdb->prefix . 'mini_crm_contacts';
    $old_status = $wpdb->get_var( $wpdb->prepare(
        "SELECT status FROM {$table} WHERE id = %d",
        $id
    ) );

    // 4. اگر تغییر کرده، آپدیت و ارسال SMS
    if ( $old_status !== $new_status ) {
        $wpdb->update(
            $table,
            [ 'status' => $new_status ],
            [ 'id'     => $id ],
            [ '%s' ],
            [ '%d' ]
        );

        // ساخت پیامک بر اساس قالب‌ها
        $message = mini_crm_get_template( $new_status, $id );
        mini_crm_send_sms( $id, $message );
    }

    // 5. بازگشت به لیست با پیغام (می‌توانید پارامتر پیام بفرستید)
    wp_redirect( admin_url( 'admin.php?page=mini-crm' ) );
    exit;
}


/**
 * Message templates
 */
function mini_crm_get_template($status, $contact_id) {
    global $wpdb;
    $table = $wpdb->prefix . 'mini_crm_contacts';
    $c = $wpdb->get_row($wpdb->prepare("SELECT first_name FROM $table WHERE id=%d", $contact_id));
    switch($status) {
        case 'new':
            return "سلام {$c->first_name} عزیز
از ثبت درخواست شما سپاسگزاریم. در روزهای آینده با شما تماس خواهیم گرفت.
Domain.com
لغو 11";
        case 'no_answer':
            return "سلام {$c->first_name} عزیز
تلاش کردیم با شما تماس بگیریم اما موفق نشدیم. در زمان مناسب‌تر دوباره تماس خواهیم گرفت. اگر سوالی دارید، خوشحال می‌شویم پاسخگو باشیم.
Domain.com
لغو 11";
        default:
            return "سلام {$c->first_name} عزیز";
    }
}




?>



