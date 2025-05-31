<?php
/**
 * Add admin menu
 */
function mini_crm_add_admin_menu() {
    add_menu_page( 'Mini CRM', 'Mini CRM', 'manage_options', 'mini-crm', 'mini_crm_list_page', 'dashicons-clipboard', 6 );
    add_submenu_page( 'mini-crm', 'افزودن تماس', 'افزودن تماس', 'manage_options', 'mini-crm-add', 'mini_crm_add_page' );
}

/**
 * List contacts table
 */
/**
 * نمایش لیست تماس‌ها با امکان بروزرسانی وضعیت
 */
function mini_crm_list_page() {
    echo '<div class="wrap"><h1>لیست تماس‌ها</h1>';

    global $wpdb;
    $table    = $wpdb->prefix . 'mini_crm_contacts';
    $contacts = $wpdb->get_results( "SELECT * FROM $table ORDER BY submitted_at DESC" );

    // شروع فرم
    echo '<form method="post" action="' . admin_url('admin-post.php') . '">';
    echo '<input type="hidden" name="action" value="update_minicrm">';

    // جدول
    echo '<table class="wp-list-table widefat fixed striped">';
    echo '<thead><tr>'
       . '<th>ID</th>'
       . '<th>نام</th>'
       . '<th>نام خانوادگی</th>'
       . '<th>شماره تلفن</th>'
       . '<th>منبع</th>'
       . '<th>زمان ثبت</th>'
       . '<th>وضعیت</th>'
       . '<th>عملیات</th>'
       . '</tr></thead>';
    echo '<tbody>';

    $statuses = [
        'new'        => 'جدید',
        'in_contact' => 'در تماس',
        'no_answer'  => 'بدون پاسخ',
    ];

    foreach ( $contacts as $c ) {
        echo '<tr>';
        printf(
            '<td>%d</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td>',
            esc_html($c->id),
            esc_html($c->first_name),
            esc_html($c->last_name),
            esc_html($c->phone),
            esc_html($c->source),
            esc_html($c->submitted_at)
        );
        // ستون وضعیت با منوی کشویی
        echo '<td><select name="status[' . intval($c->id) . ']">';
        foreach ( $statuses as $key => $label ) {
            $sel = $c->status === $key ? ' selected' : '';
            echo "<option value='{$key}'{$sel}>{$label}</option>";
        }
        echo '</select></td>';

        // ستون عملیات (دکمه و nonce)
        echo '<td>';
        // nonce برای امنیت
        echo wp_nonce_field( 'update_minicrm_' . $c->id, 'mini_crm_nonce_' . $c->id, true, false );
        // دکمه ارسال
        echo '<button type="submit" name="contact_id" value="' . intval($c->id) . '">بروزرسانی</button>';
        echo '</td>';

        echo '</tr>';
    }

    echo '</tbody>';
    echo '</table>';
    echo '</form>';

    echo '</div>';
}

/**
 * Add contact manually
 */
function mini_crm_add_page() {
    if ( isset($_POST['mini_crm_add']) ) mini_crm_manual_insert();
    ?>
    <h1>افزودن تماس جدید</h1>
    <form method="post">
      <label>نام: <input type="text" name="first_name"></label><br>
      <label>نام خانوادگی: <input type="text" name="last_name"></label><br>
      <label>شماره تلفن: <input type="tel" name="phone"></label><br>
      <label>وضعیت:
        <select name="status">
          <option value="new">جدید</option>
          <option value="in_contact">در تماس</option>
          <option value="no_answer">بدون پاسخ</option>
        </select>
      </label><br>
      <button type="submit" name="mini_crm_add">ذخیره</button>
    </form>
    <?php
}

function mini_crm_manual_insert() {
    global $wpdb;
    $table = $wpdb->prefix . 'mini_crm_contacts';
    $wpdb->insert( $table, [
        'first_name'   => sanitize_text_field($_POST['first_name']),
        'last_name'    => sanitize_text_field($_POST['last_name']),
        'phone'        => sanitize_text_field($_POST['phone']),
        'status'       => sanitize_text_field($_POST['status']),
        'source'       => 'admin',
        'submitted_at' => current_time('mysql'),
    ]);
    echo '<div class="notice success">تماس با موفقیت افزوده شد.</div>';
}
?>