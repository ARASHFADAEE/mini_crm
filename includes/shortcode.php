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
        $wpdb->insert( $table, [
            'first_name'   => sanitize_text_field( $_POST['first_name'] ),
            'last_name'    => sanitize_text_field( $_POST['last_name'] ),
            'phone'        => sanitize_text_field( $_POST['phone'] ),
            'status'       => 'new',
            'source'       => sanitize_text_field( $_POST['source'] ),
            'submitted_at' => current_time( 'mysql' ),
        ]);
        echo '<div class="notice success">درخواست شما با موفقیت ارسال شد.</div>';
    }
}
?>