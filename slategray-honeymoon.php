<?php
/**
 * Plugin Name: Slategray Honeymoon
 * Description: This is the HoneyMoon manage plugin registration form [honeymoon_registration], [honeymooon_dashboard]
 * Version: 1.0
 * Author: Kanai
 * Author URI: https://www.facebook.com/proshanto.das.176081/
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; 
}


// css Js load 
function honeymoon_css_js_load() {
   
    wp_enqueue_style(
        'honeymoon',
        plugin_dir_url(__FILE__).'assets/css/style.css',
        '1.0.0'
    );

    wp_enqueue_script(
        'honeymoon',
        plugin_dir_url(__FILE__).'assets/js/scripts.js',
        array('jquery'),
        '1.0.0',
        true
    );
    wp_localize_script(
        'honeymoon',
        'honeymoon_ajax',
        array(
            'ajax_url' => admin_url('admin-ajax.php')
        )
    );
}
add_action('wp_enqueue_scripts','honeymoon_css_js_load');
add_action('admin_enqueue_scripts','honeymoon_css_js_load');


// Registration Form connect

function honeymoon_registration_form() {
    ob_start();
    include_once(plugin_dir_path(__FILE__).'/user-panel/registration.php');
    return ob_get_clean();
}

add_shortcode('honeymoon_registration','honeymoon_registration_form');
// User Dashboard 

function honeymoon_user_dashboard() {
    ob_start();
    include_once(plugin_dir_path(__FILE__).'/user-panel/honeymoon-dashboard.php');
    return ob_get_clean();
}

add_shortcode('honeymooon_dashboard','honeymoon_user_dashboard');

// Registration submit 

function honeymoon_register_user() {

    // 🔹 Sanitize inputs
    $husband_first_name = sanitize_text_field($_POST['husband_first_name'] ?? '');
    $husband_last_name  = sanitize_text_field($_POST['husband_last_name'] ?? '');
    $wife_first_name    = sanitize_text_field($_POST['wife_first_name'] ?? '');
    $wife_last_name     = sanitize_text_field($_POST['wife_last_name'] ?? '');
    $email              = sanitize_email($_POST['email'] ?? '');
    $password           = sanitize_text_field($_POST['password'] ?? '');
    $confirm_password   = sanitize_text_field($_POST['confirm_password'] ?? '');

    // 🔴 1. Check all fields required
    if(
        empty($husband_first_name) ||
        empty($husband_last_name)  ||
        empty($wife_first_name)    ||
        empty($wife_last_name)     ||
        empty($email)              ||
        empty($password)           ||
        empty($confirm_password)
    ){
        wp_send_json_error('All fields are required');
    }

    // 🔴 2. Password match check
    if($password !== $confirm_password){
        wp_send_json_error('Password not match');
    }

    // 🟢 If everything ok
    
    // 🔹 Email valid check
    if(!is_email($email)){
        wp_send_json_error('Invalid email address');
    }

    // 🔹 Email already exists check
    if(email_exists($email)){
        wp_send_json_error('Email already registered');
    }

    // 🔹 Create user
    $user_id = wp_create_user($email, $password, $email);

    if(is_wp_error($user_id)){
        wp_send_json_error($user_id->get_error_message());
    }


    // 🔥 Unique 8 char code generate
    function generate_unique_code() {

        do {
            $code = strtoupper(wp_generate_password(8, false, false)); // e.g. A8D2K9LQ
            $exists = get_users([
                'meta_key'   => 'honeymoon_code',
                'meta_value' => $code,
                'number'     => 1,
                'fields'     => 'ID'
            ]);
        } while(!empty($exists));

        return $code;
    }

    $unique_code = generate_unique_code();


    // 🔹 Save user meta
    update_user_meta($user_id, 'husband_first_name', $husband_first_name);
    update_user_meta($user_id, 'husband_last_name', $husband_last_name);
    update_user_meta($user_id, 'wife_first_name', $wife_first_name);
    update_user_meta($user_id, 'wife_last_name', $wife_last_name);
    update_user_meta($user_id, 'honeymoon_code', $unique_code);

      /* =========================
       🔥 AUTO LOGIN
    ========================= */
    wp_set_current_user($user_id);
    wp_set_auth_cookie($user_id, true);

    // optional: trigger login hook
    do_action('wp_login', $email, get_user_by('id', $user_id));

    // 🟢 Success response
    wp_send_json_success([
        'message' => 'Registration successful',
        'code'    => $unique_code
    ]);

}

add_action('wp_ajax_honeymoon_register_user', 'honeymoon_register_user');
add_action('wp_ajax_nopriv_honeymoon_register_user', 'honeymoon_register_user');


// Update Profile 

function update_user_profile(){

    if(!is_user_logged_in()){
        wp_send_json_error('Not logged in');
    }

    $user_id = get_current_user_id();

    // sanitize
    $husband_first_name = sanitize_text_field($_POST['husband_first_name']);
    $husband_last_name  = sanitize_text_field($_POST['husband_last_name']);
    $wife_first_name    = sanitize_text_field($_POST['wife_first_name']);
    $wife_last_name     = sanitize_text_field($_POST['wife_last_name']);
    $email              = sanitize_email($_POST['email']);

    // update meta
    update_user_meta($user_id, 'husband_first_name', $husband_first_name);
    update_user_meta($user_id, 'husband_last_name', $husband_last_name);
    update_user_meta($user_id, 'wife_first_name', $wife_first_name);
    update_user_meta($user_id, 'wife_last_name', $wife_last_name);

    // update email
    wp_update_user([
        'ID' => $user_id,
        'user_email' => $email
    ]);

    wp_send_json_success('Profile updated');
}
add_action('wp_ajax_update_user_profile', 'update_user_profile');
add_action('wp_ajax_nopriv_update_user_profile', 'update_user_profile');


// Checkout field add for unice code 
add_filter('woocommerce_checkout_fields', function($fields) {

    $fields['billing']['referral_code'] = array(
        'type'        => 'text',
        'label'       => 'Código de regalo',
        'placeholder' => 'Introduce tu código de regalo',
        'required'    => false,
        'class'       => array('form-row-wide'),
        'priority'    => 120,
    );

    return $fields;
});

add_action('woocommerce_checkout_create_order', function($order, $data) {

    // referral code
    if (!empty($_POST['referral_code'])) {
        $order->update_meta_data('_referral_code', sanitize_text_field($_POST['referral_code']));
    }

    // guest email (actually billing email, works for both guest + user)
    if (!empty($data['billing_email'])) {
        $order->update_meta_data('_referral_used_by_email', sanitize_email($data['billing_email']));
    }

}, 20, 2);

/// Admin menu Register 

add_action('admin_menu', function () {

    add_menu_page(
        'Couple Manager',
        'Couple Manager',
        'manage_options',
        'hm-users',
        'hm_users_page',
        'dashicons-heart',
        6
    );

    add_submenu_page(
        'hm-users',
        'Withdraw Requests',
        'Withdraw Requests',
        'manage_options',
        'hm-withdraw',
        'hm_withdraw_page'
    );
});



/* =========================
   USERS PAGE
========================= */
function hm_users_page()
{
    $search = $_GET['s'] ?? '';

    $users = get_users();

    echo '<div class="wrap"><h1>Parejas</h1>';

    echo '<form method="get">
            <input type="hidden" name="page" value="hm-users">
            <input class="hm-search" type="text" name="s" placeholder="Buscar por correo o código..." value="' . esc_attr($search) . '">
            <button class="button">Buscar</button>
        </form>';
    echo "<br>";
    echo '<div class="hm-card">';
    echo '<table class="widefat hm-table"><thead>
    <tr>
        <th>Marido</th>
        <th>Esposa</th>
        <th>Correo electrónico</th>
        <th>Código de regalo</th>
        <th>Total de regalos</th>
        <th>Saldo total</th>
        <th>Acción</th>
    </tr></thead><tbody>';

    foreach ($users as $user) {

        $code = get_user_meta($user->ID, 'honeymoon_code', true);
        if (empty($code)) continue;

        if ($search && !str_contains($user->user_email . $code, $search)) continue;

        $orders = wc_get_orders([
            'limit' => -1,
            'meta_query' => [
                [
                    'key' => '_referral_code',
                    'value' => $code
                ]
            ]
        ]);

        $count = count($orders);
        $total = 0;

        foreach ($orders as $order) {
            $total += $order->get_total();
        }

        $husband_first = get_user_meta($user->ID, 'husband_first_name', true);
        $wife_first    = get_user_meta($user->ID, 'wife_first_name', true);

        echo "<tr>
            <td>{$husband_first} </td>
            <td> {$wife_first}</td>
            <td>{$user->user_email}</td>
            <td><strong>{$code}</strong></td>
            <td>{$count}</td>
            <td>" . wc_price($total) . "</td>
            <td>
                <a class='button' href='?page=hm-view&user_id={$user->ID}'>Ver</a>
            </td>
        </tr>";
    }

    echo '</tbody></table></div></div>';
}


/* =========================
   USER DETAILS PAGE
========================= */
add_action('admin_menu', function () {
    add_submenu_page(null, 'User Details', 'User Details', 'manage_options', 'hm-view', 'hm_user_view');
});

function hm_user_view()
{
    $user_id = $_GET['user_id'];
    $user = get_user_by('id', $user_id);

    $code = get_user_meta($user_id, 'honeymoon_code', true);

    echo "<div class='wrap'><h1>Detalles del usuario</h1>";
    echo '<a href="' . admin_url('admin.php?page=hm-users') . '" class="hm-back-btn">← Volver</a>';
    echo "<div class='hm-card'>";

    echo "<p><strong>Regalador:</strong> {$user->display_name}</p>";
    echo "<p><strong>Correo electrónico:</strong> {$user->user_email}</p>";
    echo "<p><strong>Código de regalo:</strong> {$code}</p>";

    $orders = wc_get_orders([
        'limit' => 10,
        'meta_query' => [
            [
                'key' => '_referral_code',
                'value' => $code
            ]
        ]
    ]);

    echo "<h3>Pedidos recientes</h3>";
    
    echo "<table class='widefat'><tr>
        <th>Artículo de regalo</th>
        <th>Total</th>
        <th>Estado</th>
    </tr>";

    foreach ($orders as $order) {

        $items = $order->get_items();
        $product = '';

        foreach ($items as $item) {
            $product = $item->get_name();
            break;
        }

        echo "<tr>
            <td>{$product}</td>
            <td>" . wc_price($order->get_total()) . "</td>
            <td>{$order->get_status()}</td>
        </tr>";
    }

    echo "</table>";
    echo "</div></div>";
}


/* =========================
   WITHDRAW PAGE
========================= */

function hm_withdraw_page()
{
    $users = get_users();

    echo '<div class="wrap"><h1>Solicitudes de retiro</h1>';

    echo '<table class="widefat hm-table">
        <thead>
            <tr>
                <th>Usuario</th>
                <th>Correo electrónico</th>
                <th>Código</th>
                <th>Cantidad</th>
                <th>Estado</th>
                <th>Acción</th>
            </tr>
        </thead>
        <tbody>';

    foreach ($users as $user) {

        $status = get_user_meta($user->ID, 'withdraw_request', true);
        $amount = get_user_meta($user->ID, 'withdraw_amount', true);
        $code   = get_user_meta($user->ID, 'honeymoon_code', true);

        // only pending
        if ($status !== 'pending') continue;

        echo '<tr>
            <td>' . esc_html( get_user_meta($user->ID, 'husband_first_name', true) ?: $user->display_name ) . '</td>
            <td>' . esc_html($user->user_email) . '</td>
            <td>' . esc_html($code) . '</td>
            <td>' . wc_price($amount) . '</td>
            <td><span class="hm-badge pending">Pendiente</span></td>
            <td>
                <a class="hm-btn" href="' . admin_url('admin.php?page=hm-withdraw&approve=' . $user->ID . '&reset=1') . '">Aprobar + Reset</a>
                <a class="hm-btn" href="' . admin_url('admin.php?page=hm-withdraw&approve=' . $user->ID . '&reset=0') . '">Solo Aprobar</a>
                <a class="hm-btn" href="' . admin_url('admin.php?page=hm-withdraw&reject=' . $user->ID) . '">Rechazar</a>
            </td>
        </tr>';
    }

    echo '</tbody></table></div>';
}


/* =========================
   APPROVE / REJECT LOGIC
========================= */
add_action('admin_init', function () {

    // APPROVE
    if (isset($_GET['approve'])) {

        $user_id = intval($_GET['approve']);
        $reset   = isset($_GET['reset']) ? intval($_GET['reset']) : 0;

        update_user_meta($user_id, 'withdraw_request', 'approved');

        if ($reset === 1) {
            // 🔥 balance reset
            update_user_meta($user_id, 'withdraw_amount', 0);

            // flag for dashboard
            update_user_meta($user_id, 'balance_reset', 1);
        }
    }

    // REJECT
    if (isset($_GET['reject'])) {

        $user_id = intval($_GET['reject']);

        update_user_meta($user_id, 'withdraw_request', 'rejected');
        update_user_meta($user_id, 'withdraw_time', time());
    }

});

// Mail SEND 

add_action('woocommerce_thankyou', 'hm_send_gift_notification');

function hm_send_gift_notification($order_id){

    if(!$order_id) return;

    $order = wc_get_order($order_id);

    // referral code
    $code = $order->get_meta('_referral_code');

    if(empty($code)) return;

    // find user by code
    $users = get_users([
        'meta_key'   => 'honeymoon_code',
        'meta_value' => $code,
        'number'     => 1
    ]);

    if(empty($users)) return;

    $user = $users[0];

    // user email
    $to = $user->user_email;

    // buyer info
    $buyer_name = $order->get_billing_first_name();
    if(empty($buyer_name)){
        $buyer_name = 'Invitado';
    }

    // product
    $items = $order->get_items();
    $product_name = '';

    foreach($items as $item){
        $product_name = $item->get_name();
        break;
    }

    // price
    $price = wc_price($order->get_total());

    // 📨 Email Subject
    $subject = '🎁 Has recibido un regalo';

    // 📩 Email Body (Spanish Table, clean)
    $message = '
    <h2>¡Has recibido un regalo!</h2>

    <table style="border-collapse: collapse; width: 100%; border:1px solid #ddd;">
        <tr>
            <th style="border:1px solid #ddd; padding:10px; background:#f5f5f5;">Nombre</th>
            <td style="border:1px solid #ddd; padding:10px;">'.$buyer_name.'</td>
        </tr>
        <tr>
            <th style="border:1px solid #ddd; padding:10px; background:#f5f5f5;">Gift</th>
            <td style="border:1px solid #ddd; padding:10px;">'.$product_name.'</td>
        </tr>
        <tr>
            <th style="border:1px solid #ddd; padding:10px; background:#f5f5f5;">Monto</th>
            <td style="border:1px solid #ddd; padding:10px;">'.$price.'</td>
        </tr>
    </table>

    <p style="margin-top:15px;">Gracias por usar nuestro sistema 💝</p>
    ';

    // headers (HTML mail)
    $headers = ['Content-Type: text/html; charset=UTF-8'];

    wp_mail($to, $subject, $message, $headers);
}