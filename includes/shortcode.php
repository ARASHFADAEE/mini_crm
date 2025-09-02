<?php
/**
 * Render form
 */
function mini_crm_render_form( $atts ) {
    $atts = shortcode_atts( [ 'source' => 'unknown' ], $atts, 'mini_crm_form' );
    ob_start();
    ?>
    <form method="post">
      <label>نام: <input type="text" name="first_name" required></label><br>
      <label>نام خانوادگی: <input type="text" name="last_name" required></label><br>
      <label>شماره تلفن: <input type="tel" name="phone" required></label><br>
      <input type="hidden" name="source" value="<?php echo esc_attr($atts['source']); ?>">
      <button type="submit" name="mini_crm_submit">ارسال</button>
    </form>
    <?php
    return ob_get_clean();
}

/**
 * Handle submission
 */
function mini_crm_handle_submission() {
    if ( isset( $_POST['mini_crm_submit'] ) ) {
        global $wpdb;
        $table = $wpdb->prefix . 'mini_crm_contacts';
        
        // Prepare contact data
        $first_name = sanitize_text_field( $_POST['first_name'] );
        $last_name = sanitize_text_field( $_POST['last_name'] );
        $full_name = trim($first_name . ' ' . $last_name);
        $phone = sanitize_text_field( $_POST['phone'] );
        $source = sanitize_text_field( $_POST['source'] );
        
        // Insert contact data
        $result = $wpdb->insert( $table, [
            'full_name'    => $full_name,
            'phone'        => $phone,
            'status'       => 'PEND',
            'channel'      => $source,
            'created_at'   => current_time( 'mysql' ),
        ]);
        
        error_log("Mini CRM Shortcode: Inserting contact - Name: $full_name, Phone: $phone, Source: $source");
        
        if ($result !== false) {
            $contact_id = $wpdb->insert_id;
            
            // Send email notification to admin(s)
            if (function_exists('mini_crm_send_admin_notification_email')) {
                mini_crm_send_admin_notification_email($full_name, $phone, $source);
            }
            
            // Trigger SMS notification for form submission
            if (function_exists('mini_crm_trigger_sms_notifications')) {
                $source = sanitize_text_field( $_POST['source'] );
                mini_crm_trigger_sms_notifications($contact_id, 'form_submission', ['channel' => $source]);
                error_log("Mini CRM Shortcode: SMS triggered for contact ID: $contact_id, source: $source");
            }
            
            echo '<div class="notice success">درخواست شما با موفقیت ارسال شد.</div>';
        } else {
            echo '<div class="notice error">خطا در ثبت اطلاعات. لطفاً مجدداً تلاش کنید.</div>';
            error_log("Mini CRM Shortcode: Database insert failed. Error: " . $wpdb->last_error);
        }
    }
}
?>