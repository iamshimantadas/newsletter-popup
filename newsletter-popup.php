<?php
/**
 * Plugin Name:       Newsletter Popup
 * Plugin URI:        https://microcodes.in
 * Description:       Scroll-triggered newsletter popup + shortcode forms + email campaigns with 24 professional templates. Uses WP SMTP.
 * Version:           2.2.2
 * Author:            Shimanta Das
 * License:           GPL-2.0-or-later
 * Text Domain:       newsletter-popup
 * Domain Path:       /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'NP_VERSION', '2.2.2' );
define( 'NP_PATH',    plugin_dir_path( __FILE__ ) );
define( 'NP_URL',     plugin_dir_url( __FILE__ ) );
define( 'NP_FILE',    __FILE__ );

$np_autoload = NP_PATH . 'vendor/autoload.php';
if ( file_exists( $np_autoload ) ) {
    require_once $np_autoload;
} else {
    add_action( 'admin_notices', function () {
        echo '<div class="notice notice-error"><p><strong>Newsletter Popup:</strong> Composer dependencies missing. Run <code>composer install --no-dev</code> inside the plugin folder.</p></div>';
    } );
}

require_once NP_PATH . 'includes/class-np-handler.php';
require_once NP_PATH . 'includes/class-np-settings.php';
require_once NP_PATH . 'includes/class-np-subscribers.php';
require_once NP_PATH . 'includes/class-np-campaigns.php';
require_once NP_PATH . 'includes/class-np-mailer.php';
require_once NP_PATH . 'includes/class-np-shortcode.php';
require_once NP_PATH . 'includes/class-np-templates.php';

register_activation_hook( __FILE__, [ 'NP_Handler', 'activate' ] );
register_deactivation_hook( __FILE__, [ 'NP_Handler', 'deactivate' ] );

add_action( 'plugins_loaded', function () {
    NP_Settings::init();
    NP_Subscribers::init();
    NP_Campaigns::init();
    NP_Mailer::init();
    NP_Shortcode::init();
    NP_Templates::init();
} );

/* ---------- ADMIN MENU ---------- */
add_action( 'admin_menu', function () {

    add_menu_page(
        __( 'Newsletter Popup', 'newsletter-popup' ),
        __( 'Newsletter', 'newsletter-popup' ),
        'manage_options',
        'np-dashboard',
        [ 'NP_Subscribers', 'render_page' ],
        'dashicons-email-alt',
        30
    );

    add_submenu_page( 'np-dashboard', __( 'Subscribers', 'newsletter-popup' ), __( 'Subscribers', 'newsletter-popup' ), 'manage_options', 'np-dashboard', [ 'NP_Subscribers', 'render_page' ] );
    add_submenu_page( 'np-dashboard', __( 'Email Campaigns', 'newsletter-popup' ), __( 'Email Campaigns', 'newsletter-popup' ), 'manage_options', 'np-campaigns', [ 'NP_Campaigns', 'render_page' ] );
    add_submenu_page( 'np-dashboard', __( 'Shortcode Forms', 'newsletter-popup' ), __( 'Shortcode Forms', 'newsletter-popup' ), 'manage_options', 'np-shortcode', [ 'NP_Shortcode', 'render_admin_page' ] );
    add_submenu_page( 'np-dashboard', __( 'Settings', 'newsletter-popup' ), __( 'Settings', 'newsletter-popup' ), 'manage_options', 'np-settings', [ 'NP_Settings', 'render_page' ] );
} );

/* ---------- ADMIN ASSETS ---------- */
add_action( 'admin_enqueue_scripts', function ( $hook ) {
    if ( strpos( $hook, 'np-' ) === false && strpos( $hook, 'np_' ) === false ) {
        return;
    }
    wp_enqueue_style( 'np-admin', NP_URL . 'assets/admin.css', [], NP_VERSION );
    wp_enqueue_script( 'np-admin', NP_URL . 'assets/admin.js', [ 'jquery' ], NP_VERSION, true );
    wp_localize_script( 'np-admin', 'NP_ADMIN', [
        'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
        'nonce'     => wp_create_nonce( 'np_admin_nonce' ),
        'templates' => NP_Templates::all(),
    ] );
} );

/* ---------- FRONTEND ASSETS ---------- */
add_action( 'wp_enqueue_scripts', function () {

    $settings = NP_Settings::get();

    // ALWAYS load styles — shortcode needs them regardless of popup state.
    wp_enqueue_style(
        'np-fontawesome',
        'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css',
        [],
        '6.0.0'
    );
    wp_enqueue_style( 'np-style', NP_URL . 'assets/style.css', [ 'np-fontawesome' ], NP_VERSION );

    // ALWAYS load JS — handles both popup and shortcode forms.
    wp_enqueue_script( 'np-script', NP_URL . 'assets/script.js', [], NP_VERSION, true );

    wp_localize_script( 'np-script', 'NP_DATA', [
        'ajaxUrl'           => admin_url( 'admin-ajax.php' ),
        'nonce'             => wp_create_nonce( 'np_subscribe_nonce' ),
        'scrollThreshold'   => (int) $settings['scroll_threshold'],
        'dismissCooldown'   => (int) $settings['dismiss_cooldown'],
        'subscribeCooldown' => (int) $settings['subscribe_cooldown'],
        'cookiePrefix'      => 'np_',
        'popupEnabled'      => ! empty( $settings['enabled'] ) ? 1 : 0,
    ] );
} );

/* ---------- POPUP MARKUP ---------- */
add_action( 'wp_footer', function () {

    $settings = NP_Settings::get();
    if ( empty( $settings['enabled'] ) ) {
        return;
    }
    if ( is_user_logged_in() ) {
        $email = wp_get_current_user()->user_email;
        if ( $email && NP_Subscribers::is_subscribed( $email ) ) {
            return;
        }
    }
    ?>
    <div class="newsletter-overlay" id="newsletterOverlay" role="dialog" aria-modal="true" aria-label="<?php esc_attr_e( 'Newsletter subscription', 'newsletter-popup' ); ?>">
        <div class="newsletter-card">
            <button class="close-btn" id="closeNewsletter" aria-label="<?php esc_attr_e( 'Close', 'newsletter-popup' ); ?>">
                <i class="fas fa-times"></i>
            </button>

            <div class="newsletter-icon"><i class="fas fa-paper-plane"></i></div>

            <h2><?php echo wp_kses_post( $settings['heading'] ); ?></h2>
            <p class="newsletter-description"><?php echo esc_html( $settings['subheading'] ); ?></p>

            <form class="np-subscribe-form" data-np-form="popup" novalidate>
                <div class="form-group">
                    <label for="newsletter-email">
                        <i class="fas fa-envelope"></i> <?php esc_html_e( 'Email address', 'newsletter-popup' ); ?>
                    </label>
                    <div class="input-wrapper">
                        <input type="email" id="newsletter-email" name="email" placeholder="<?php echo esc_attr( $settings['placeholder'] ); ?>" required autocomplete="email">
                        <button type="submit">
                            <span class="np-btn-text"><?php echo esc_html( $settings['button_text'] ); ?></span>
                            <i class="fas fa-arrow-right"></i>
                        </button>
                    </div>
                    <p class="np-message" role="status" aria-live="polite"></p>
                </div>
            </form>

            <div class="privacy-note">
                <i class="fas fa-lock"></i>
                <span><?php echo esc_html( $settings['privacy_text'] ); ?></span>
            </div>
        </div>
    </div>
    <?php
} );
?>