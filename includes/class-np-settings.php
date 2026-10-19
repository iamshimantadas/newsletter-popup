<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class NP_Settings {

    const OPTION_KEY = 'np_settings';

    public static function init() {
        add_action( 'admin_post_np_save_settings', [ __CLASS__, 'save' ] );
    }

    public static function defaults() {
        return [
            'enabled'            => 1,
            'scroll_threshold'   => 300,
            'dismiss_cooldown'   => 300,
            'subscribe_cooldown' => 2592000,
            'heading'            => 'Stay in the <span>loop</span>',
            'subheading'         => 'Get the latest updates, articles, and resources — delivered straight to your inbox. No spam, ever.',
            'button_text'        => 'Subscribe',
            'placeholder'        => 'you@example.com',
            'privacy_text'       => 'Your privacy matters. Unsubscribe anytime.',
            'from_name'          => get_bloginfo( 'name' ),
            'from_email'         => get_option( 'admin_email' ),
            'batch_size'         => 20,
            'batch_interval'     => 300,
        ];
    }

    public static function seed_defaults() {
        if ( false === get_option( self::OPTION_KEY ) ) {
            add_option( self::OPTION_KEY, self::defaults() );
        }
    }

    public static function get() {
        $saved = get_option( self::OPTION_KEY, [] );
        if ( ! is_array( $saved ) ) {
            $saved = [];
        }
        return array_merge( self::defaults(), $saved );
    }

    public static function save() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( 'Unauthorized' );
        }
        check_admin_referer( 'np_save_settings' );

        $d  = self::defaults();
        $in = $_POST;

        $new = [
            'enabled'            => isset( $in['enabled'] ) ? 1 : 0,
            'scroll_threshold'   => max( 0, (int) ( $in['scroll_threshold'] ?? $d['scroll_threshold'] ) ),
            'dismiss_cooldown'   => max( 0, (int) ( $in['dismiss_cooldown'] ?? $d['dismiss_cooldown'] ) ),
            'subscribe_cooldown' => max( 0, (int) ( $in['subscribe_cooldown'] ?? $d['subscribe_cooldown'] ) ),
            'heading'            => wp_kses_post( $in['heading'] ?? $d['heading'] ),
            'subheading'         => sanitize_textarea_field( $in['subheading'] ?? $d['subheading'] ),
            'button_text'        => sanitize_text_field( $in['button_text'] ?? $d['button_text'] ),
            'placeholder'        => sanitize_text_field( $in['placeholder'] ?? $d['placeholder'] ),
            'privacy_text'       => sanitize_text_field( $in['privacy_text'] ?? $d['privacy_text'] ),
            'from_name'          => sanitize_text_field( $in['from_name'] ?? $d['from_name'] ),
            'from_email'         => sanitize_email( $in['from_email'] ?? $d['from_email'] ),
            'batch_size'         => max( 1, min( 100, (int) ( $in['batch_size'] ?? $d['batch_size'] ) ) ),
            'batch_interval'     => max( 60, (int) ( $in['batch_interval'] ?? $d['batch_interval'] ) ),
        ];

        update_option( self::OPTION_KEY, $new );

        wp_safe_redirect( add_query_arg( [ 'page' => 'np-settings', 'updated' => 1 ], admin_url( 'admin.php' ) ) );
        exit;
    }

    public static function render_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }
        $s           = self::get();
        $smtp_plugin = self::detect_smtp_plugin();
        ?>
        <div class="wrap np-wrap">
            <h1><span class="dashicons dashicons-email-alt"></span> <?php esc_html_e( 'Newsletter Settings', 'newsletter-popup' ); ?></h1>

            <?php if ( isset( $_GET['updated'] ) ) : ?>
                <div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Settings saved.', 'newsletter-popup' ); ?></p></div>
            <?php endif; ?>

            <div class="np-smtp-status">
                <strong><?php esc_html_e( 'SMTP Status:', 'newsletter-popup' ); ?></strong>
                <?php if ( $smtp_plugin ) : ?>
                    <span class="np-badge np-badge-ok"><?php echo esc_html( $smtp_plugin ); ?> <?php esc_html_e( 'detected — emails will use it automatically.', 'newsletter-popup' ); ?></span>
                <?php else : ?>
                    <span class="np-badge np-badge-warn"><?php esc_html_e( 'No SMTP plugin detected. Using wp_mail() defaults. Install WP Mail SMTP or similar for reliable delivery.', 'newsletter-popup' ); ?></span>
                <?php endif; ?>
            </div>

            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                <input type="hidden" name="action" value="np_save_settings">
                <?php wp_nonce_field( 'np_save_settings' ); ?>

                <h2 class="np-section"><?php esc_html_e( 'General', 'newsletter-popup' ); ?></h2>
                <table class="form-table">
                    <tr>
                        <th><?php esc_html_e( 'Enable popup', 'newsletter-popup' ); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="enabled" value="1" <?php checked( $s['enabled'], 1 ); ?>>
                                <?php esc_html_e( 'Show newsletter popup on frontend', 'newsletter-popup' ); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="scroll_threshold"><?php esc_html_e( 'Scroll threshold (px)', 'newsletter-popup' ); ?></label></th>
                        <td>
                            <input type="number" min="0" id="scroll_threshold" name="scroll_threshold" value="<?php echo esc_attr( $s['scroll_threshold'] ); ?>" class="small-text">
                            <p class="description"><?php esc_html_e( 'How many pixels user must scroll before popup appears.', 'newsletter-popup' ); ?></p>
                        </td>
                    </tr>
                </table>

                <h2 class="np-section"><?php esc_html_e( 'Cooldown / Visibility', 'newsletter-popup' ); ?></h2>
                <table class="form-table">
                    <tr>
                        <th><label for="dismiss_cooldown"><?php esc_html_e( 'Dismiss cooldown (seconds)', 'newsletter-popup' ); ?></label></th>
                        <td>
                            <input type="number" min="0" id="dismiss_cooldown" name="dismiss_cooldown" value="<?php echo esc_attr( $s['dismiss_cooldown'] ); ?>" class="small-text">
                            <p class="description"><?php esc_html_e( 'After clicking the close (X) button, do not show again for this many seconds. Default 300 = 5 minutes.', 'newsletter-popup' ); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="subscribe_cooldown"><?php esc_html_e( 'Subscribed cooldown (seconds)', 'newsletter-popup' ); ?></label></th>
                        <td>
                            <input type="number" min="0" id="subscribe_cooldown" name="subscribe_cooldown" value="<?php echo esc_attr( $s['subscribe_cooldown'] ); ?>" class="small-text">
                            <p class="description"><?php esc_html_e( 'After a successful subscription, hide popup for this many seconds. Default 2592000 = 30 days.', 'newsletter-popup' ); ?></p>
                        </td>
                    </tr>
                </table>

                <h2 class="np-section"><?php esc_html_e( 'Content', 'newsletter-popup' ); ?></h2>
                <table class="form-table">
                    <tr>
                        <th><label for="heading"><?php esc_html_e( 'Heading (HTML allowed)', 'newsletter-popup' ); ?></label></th>
                        <td><input type="text" id="heading" name="heading" value="<?php echo esc_attr( $s['heading'] ); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th><label for="subheading"><?php esc_html_e( 'Description', 'newsletter-popup' ); ?></label></th>
                        <td><textarea id="subheading" name="subheading" rows="3" class="large-text"><?php echo esc_textarea( $s['subheading'] ); ?></textarea></td>
                    </tr>
                    <tr>
                        <th><label for="button_text"><?php esc_html_e( 'Button text', 'newsletter-popup' ); ?></label></th>
                        <td><input type="text" id="button_text" name="button_text" value="<?php echo esc_attr( $s['button_text'] ); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th><label for="placeholder"><?php esc_html_e( 'Input placeholder', 'newsletter-popup' ); ?></label></th>
                        <td><input type="text" id="placeholder" name="placeholder" value="<?php echo esc_attr( $s['placeholder'] ); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th><label for="privacy_text"><?php esc_html_e( 'Privacy note', 'newsletter-popup' ); ?></label></th>
                        <td><input type="text" id="privacy_text" name="privacy_text" value="<?php echo esc_attr( $s['privacy_text'] ); ?>" class="regular-text"></td>
                    </tr>
                </table>

                <h2 class="np-section"><?php esc_html_e( 'Email / Campaign Sending', 'newsletter-popup' ); ?></h2>
                <table class="form-table">
                    <tr>
                        <th><label for="from_name"><?php esc_html_e( 'From name', 'newsletter-popup' ); ?></label></th>
                        <td><input type="text" id="from_name" name="from_name" value="<?php echo esc_attr( $s['from_name'] ); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th><label for="from_email"><?php esc_html_e( 'From email', 'newsletter-popup' ); ?></label></th>
                        <td><input type="email" id="from_email" name="from_email" value="<?php echo esc_attr( $s['from_email'] ); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th><label for="batch_size"><?php esc_html_e( 'Batch size', 'newsletter-popup' ); ?></label></th>
                        <td>
                            <input type="number" min="1" max="100" id="batch_size" name="batch_size" value="<?php echo esc_attr( $s['batch_size'] ); ?>" class="small-text">
                            <p class="description"><?php esc_html_e( 'How many emails to send per cron run.', 'newsletter-popup' ); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="batch_interval"><?php esc_html_e( 'Batch interval (seconds)', 'newsletter-popup' ); ?></label></th>
                        <td>
                            <input type="number" min="60" id="batch_interval" name="batch_interval" value="<?php echo esc_attr( $s['batch_interval'] ); ?>" class="small-text">
                            <p class="description"><?php esc_html_e( 'Minimum 60. Default 300 = every 5 minutes.', 'newsletter-popup' ); ?></p>
                        </td>
                    </tr>
                </table>

                <?php submit_button( __( 'Save Settings', 'newsletter-popup' ) ); ?>
            </form>
        </div>
        <?php
    }

    private static function detect_smtp_plugin() {
        if ( defined( 'WPMS_PLUGIN_VER' ) )            return 'WP Mail SMTP';
        if ( class_exists( 'PostmanOptions' ) )        return 'Postman SMTP';
        if ( class_exists( 'FluentSmtpPlugin' ) )      return 'FluentSMTP';
        if ( defined( 'EasyWPSMTP_PLUGIN_VERSION' ) )  return 'Easy WP SMTP';
        if ( class_exists( '\\WPMailSmtp\\Plugin' ) )  return 'SMTP Mailer';
        return '';
    }
}

?>