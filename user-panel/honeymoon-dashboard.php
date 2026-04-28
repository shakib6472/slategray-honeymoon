<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}


?>

<div id="serverAlert">
    <div class="notice">
          <span class="icon">ℹ️</span>
          <p class="message">Por favor completa todos los campos</p>
    </div>
    
</div>

<?php
if ( ! is_user_logged_in() ) {
    wp_redirect( home_url('/registro-mi-luna') );
    exit;
}
?>
<div class="account-wrapper">

    <!-- Sidebar -->
    <div class="account-sidebar">
        <h3>Mi cuenta</h3>

        <ul>
            <li class="active" data-tab="dashboard">
                <span class="dashicons dashicons-dashboard"></span>
                Panel
            </li>

            <li data-tab="gifts">
                <span class="dashicons dashicons-tickets-alt"></span>
                Mis Regalos
            </li>

            <li>
                <a class='honey-logout' href="<?php echo wp_logout_url( home_url() ); ?>">
                    <span class="dashicons dashicons-migrate"></span>
                    Cerrar sesión
                </a>
            </li>
        </ul>
    </div>


    <!-- Content -->
    <div class="account-content">

        <!-- Dashboard -->
        <div class="tab active" id="dashboard">

            <h2>
                Bienvenido, 
                <?php 
                    $current_user = wp_get_current_user();
                    echo esc_html( $current_user->user_login );
                ?> 
                👋
            </h2>

            <div class="stats">
                <div class="card">
                    <h4>Total de Regalos</h4>
                    <?php
                        $user_id = get_current_user_id();
                        $honeymoon_code = get_user_meta( $user_id, 'honeymoon_code', true );

                        $total_orders = 0;

                        if ( ! empty( $honeymoon_code ) ) {

                            $orders = wc_get_orders([
                                'limit'      => -1,
                                'status'     => ['wc-completed','wc-processing'],
                                'meta_query' => [
                                    [
                                        'key'   => '_referral_code',
                                        'value' => $honeymoon_code,
                                    ]
                                ]
                            ]);

                            $total_orders = count( $orders );
                        }

                        echo esc_html( $total_orders );
                    ?>
                </div>
                
                <div class="card">
                    <h4>Código Único</h4>
                    <?php  
                      $user_id = get_current_user_id();
                      echo esc_html( get_user_meta( $user_id, 'honeymoon_code', true ) );
                    ?>
                </div>

                <div class="card">
                    <h4>Saldo Total</h4>
                    <?php
                        $user_id = get_current_user_id();
                        $honeymoon_code = get_user_meta( $user_id, 'honeymoon_code', true );

                        $total_balance = 0;

                        if ( ! empty( $honeymoon_code ) ) {

                            $orders = wc_get_orders([
                                'limit'      => -1,
                                'status'     => ['wc-completed','wc-processing'],
                                'meta_query' => [
                                    [
                                        'key'   => '_referral_code', 
                                        'value' => $honeymoon_code,
                                    ]
                                ]
                            ]);

                            foreach ( $orders as $order ) {
                                $total_balance += $order->get_total();
                            }
                        }

                        echo '৳ ' . esc_html( $total_balance );
                    ?>
                    <div>
                        <?php
                            $user_id = get_current_user_id();

                            $status = get_user_meta($user_id, 'withdraw_request', true);
                            $time   = get_user_meta($user_id, 'withdraw_time', true);

                            // reset after 1 day যদি rejected থাকে

                            if ($status === 'rejected' && $time && (time() - $time) > 86400) {
                                delete_user_meta($user_id, 'withdraw_request');
                                delete_user_meta($user_id, 'withdraw_time');
                                $status = '';
                            }
                            ?>

                            <form method="post">
                                <button type="submit" name="hm_withdraw_request" class="hm-withdraw-btn">

                                    <?php
                                    if ($status === 'pending') {
                                        echo 'Pending...';
                                    } elseif ($status === 'approved') {
                                        echo 'Approved';
                                    } elseif ($status === 'rejected') {
                                        echo 'Rejected';
                                    } else {
                                        echo 'Solicitar Retiro';
                                    }
                                    ?>

                                </button>

                            </form>
                           
                            <!-- widthdra request  -->

                            <?php
                                if ( isset($_POST['hm_withdraw_request']) ) {

                                    $user_id = get_current_user_id();

                                    $status = get_user_meta($user_id, 'withdraw_request', true);

                                    // block if already pending
                                    if ($status === 'pending') {
                                        return;
                                    }

                                    $honeymoon_code = get_user_meta($user_id, 'honeymoon_code', true);

                                    $total = 0;

                                    if ($honeymoon_code) {
                                        $orders = wc_get_orders([
                                            'limit' => -1,
                                            'status' => ['wc-completed','wc-processing'],
                                            'meta_query' => [
                                                [
                                                    'key' => '_referral_code',
                                                    'value' => $honeymoon_code
                                                ]
                                            ]
                                        ]);

                                        foreach ($orders as $order) {
                                            $total += $order->get_total();
                                        }
                                    }

                                    update_user_meta($user_id, 'withdraw_request', 'pending');
                                    update_user_meta($user_id, 'withdraw_amount', $total);
                                    update_user_meta($user_id, 'withdraw_time', time());
                                }
                            ?>
                    </div>
                </div>
            </div>

        </div>


        <!-- Gift List -->
        <div class="tab" id="gifts">

            <h2>Historial de Regalos</h2>

            <div class="gift-list">

                <?php
        $user_id = get_current_user_id();
        $honeymoon_code = get_user_meta( $user_id, 'honeymoon_code', true );

        if ( ! empty( $honeymoon_code ) ) {

            $orders = wc_get_orders([
                'limit'      => 10, // last 10 orders
                'status'     => ['wc-completed','wc-processing'],
                'meta_query' => [
                    [
                        'key'   => '_referral_code',
                        'value' => $honeymoon_code,
                    ]
                ]
            ]);

            foreach ( $orders as $order ) {

                // Customer Name
                $name = $order->get_billing_first_name();
                if ( empty( $name ) ) {
                    $name = 'Guest';
                }

                // Email for avatar
                $email = $order->get_billing_email();

                // Avatar (fallback default)
                $avatar = get_avatar_url( $email );

                // Product name (first item)
                $items = $order->get_items();
                $product_name = '';

                foreach ( $items as $item ) {
                    $product_name = $item->get_name();
                    break; // only first product
                }

                // Price with WooCommerce currency
                $price = wc_price( $order->get_total() );
                ?>

                <div class="gift-item">
                    <img src="<?php echo esc_url( $avatar ); ?>" alt="">
                    <div>
                        <strong><?php echo esc_html( $name ); ?></strong>
                        <p>Product: <?php echo esc_html( $product_name ); ?></p>
                    </div>
                    <span><?php echo $price; ?></span>
                </div>

                <?php
            }
        }
        ?>

            </div>

        </div>


        <!-- Profile -->
        <div class="tab" id="profile">

            <?php
                $current_user = wp_get_current_user();

                $husband_first_name = get_user_meta($current_user->ID, 'husband_first_name', true);
                $husband_last_name  = get_user_meta($current_user->ID, 'husband_last_name', true);
                $wife_first_name    = get_user_meta($current_user->ID, 'wife_first_name', true);
                $wife_last_name     = get_user_meta($current_user->ID, 'wife_last_name', true);
            ?>

            <h2>Actualizar Perfil</h2>

            <form id="updateProfileForm">
                
                <input type="text" name="husband_first_name" value="<?php echo esc_attr($husband_first_name); ?>" placeholder="Nombre del esposo">

                <input type="text" name="husband_last_name" value="<?php echo esc_attr($husband_last_name); ?>" placeholder="Apellido del esposo">

                <input type="text" name="wife_first_name" value="<?php echo esc_attr($wife_first_name); ?>" placeholder="Nombre de la esposa">

                <input type="text" name="wife_last_name" value="<?php echo esc_attr($wife_last_name); ?>" placeholder="Apellido de la esposa">

                <input type="email" name="email" value="<?php echo esc_attr($current_user->user_email); ?>" placeholder="Correo electrónico">

                <button type="submit">Actualizar</button>

            </form>
        </div>

    </div>

</div>
