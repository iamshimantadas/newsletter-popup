<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class NP_Shortcode {

    const OPTION_KEY = 'np_shortcode_forms';

    public static function init() {
        add_shortcode( 'newsletter_form', [ __CLASS__, 'render' ] );
        add_action( 'admin_post_np_save_shortcode',   [ __CLASS__, 'save' ] );
        add_action( 'admin_post_np_delete_shortcode', [ __CLASS__, 'delete' ] );
    }

    public static function all() {
        $forms = get_option( self::OPTION_KEY, [] );
        return is_array( $forms ) ? $forms : [];
    }

    public static function get( $id ) {
        $forms = self::all();
        return $forms[ $id ] ?? null;
    }

    public static function render( $atts ) {
        $atts = shortcode_atts( [ 'id' => '' ], $atts, 'newsletter_form' );

        if ( empty( $atts['id'] ) ) {
            return '';
        }

        $form = self::get( $atts['id'] );
        if ( ! $form ) {
            return '';
        }

        $html = $form['html'] ?? '';
        if ( ! $html ) {
            return '';
        }

        $settings = NP_Settings::get();

        $replace = [
            '{{button_text}}'  => esc_html( $settings['button_text'] ),
            '{{placeholder}}'  => esc_attr( $settings['placeholder'] ),
            '{{privacy_text}}' => esc_html( $settings['privacy_text'] ),
            '{{ajax_url}}'     => esc_url( admin_url( 'admin-ajax.php' ) ),
            '{{nonce}}'        => esc_attr( wp_create_nonce( 'np_subscribe_nonce' ) ),
        ];

        $html = strtr( $html, $replace );

        return '<div class="np-shortcode-wrap np-sc-' . esc_attr( $atts['id'] ) . '" data-np-sc-id="' . esc_attr( $atts['id'] ) . '">' . $html . '</div>';
    }

    /**
     * Save form — CRITICAL: uses wp_unslash() to prevent backslash injection.
     */
    public static function save() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( 'Unauthorized' );
        }
        check_admin_referer( 'np_save_shortcode' );

        $id   = isset( $_POST['sc_id'] ) && $_POST['sc_id'] !== '' ? sanitize_key( $_POST['sc_id'] ) : self::generate_id();
        $name = sanitize_text_field( $_POST['sc_name'] ?? '' );

        // FIX: Unslash to remove backslashes WordPress adds on submission.
        $raw_html = wp_unslash( $_POST['sc_html'] ?? '' );

        // Allow raw HTML for admins (unfiltered_html cap), otherwise sanitize.
        $html = current_user_can( 'unfiltered_html' ) ? $raw_html : wp_kses_post( $raw_html );

        if ( $name === '' ) {
            $name = 'Untitled Form';
        }

        $forms = self::all();
        $forms[ $id ] = [
            'id'         => $id,
            'name'       => $name,
            'html'       => $html,
            'updated_at' => current_time( 'mysql' ),
        ];

        update_option( self::OPTION_KEY, $forms );

        wp_safe_redirect( add_query_arg(
            [ 'page' => 'np-shortcode', 'updated' => 1 ],
            admin_url( 'admin.php' )
        ) );
        exit;
    }

    public static function delete() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( 'Unauthorized' );
        }
        check_admin_referer( 'np_delete_shortcode' );

        $id = sanitize_key( $_GET['id'] ?? '' );
        if ( $id ) {
            $forms = self::all();
            unset( $forms[ $id ] );
            update_option( self::OPTION_KEY, $forms );
        }

        wp_safe_redirect( add_query_arg(
            [ 'page' => 'np-shortcode', 'deleted' => 1 ],
            admin_url( 'admin.php' )
        ) );
        exit;
    }

    private static function generate_id() {
        return 'npsc' . substr( md5( uniqid( (string) mt_rand(), true ) ), 0, 8 );
    }

    public static function render_admin_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $action = $_GET['action'] ?? 'list';
        $id     = sanitize_key( $_GET['id'] ?? '' );

        if ( $action === 'new' || $action === 'edit' ) {
            self::render_editor( $action === 'edit' ? $id : '' );
            return;
        }

        $forms = self::all();
        ?>
        <div class="wrap np-wrap">
            <h1>
                <span class="dashicons dashicons-shortcode"></span>
                <?php esc_html_e( 'Shortcode Forms', 'newsletter-popup' ); ?>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=np-shortcode&action=new' ) ); ?>" class="page-title-action"><?php esc_html_e( 'Add New Form', 'newsletter-popup' ); ?></a>
            </h1>

            <?php if ( isset( $_GET['updated'] ) ) : ?>
                <div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Form saved.', 'newsletter-popup' ); ?></p></div>
            <?php endif; ?>
            <?php if ( isset( $_GET['deleted'] ) ) : ?>
                <div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Form deleted.', 'newsletter-popup' ); ?></p></div>
            <?php endif; ?>

            <p class="description">
                <?php esc_html_e( 'Create custom newsletter forms with your own HTML, then paste the shortcode into any page, post, or widget.', 'newsletter-popup' ); ?>
            </p>

            <table class="wp-list-table widefat fixed striped np-table">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Name', 'newsletter-popup' ); ?></th>
                        <th><?php esc_html_e( 'Shortcode', 'newsletter-popup' ); ?></th>
                        <th><?php esc_html_e( 'Updated', 'newsletter-popup' ); ?></th>
                        <th><?php esc_html_e( 'Actions', 'newsletter-popup' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                <?php if ( empty( $forms ) ) : ?>
                    <tr><td colspan="4"><?php esc_html_e( 'No forms yet.', 'newsletter-popup' ); ?></td></tr>
                <?php else : ?>
                    <?php foreach ( $forms as $f ) : ?>
                        <?php $sc = '[newsletter_form id="' . esc_attr( $f['id'] ) . '"]'; ?>
                        <tr>
                            <td><strong><?php echo esc_html( $f['name'] ); ?></strong></td>
                            <td>
                                <input type="text" readonly value="<?php echo esc_attr( $sc ); ?>" onclick="this.select();document.execCommand('copy');" style="width:280px;font-family:monospace;">
                                <button type="button" class="button button-small np-copy-btn" data-target="<?php echo esc_attr( $sc ); ?>"><?php esc_html_e( 'Copy', 'newsletter-popup' ); ?></button>
                            </td>
                            <td><?php echo esc_html( $f['updated_at'] ); ?></td>
                            <td class="np-actions">
                                <a class="button button-small" href="<?php echo esc_url( admin_url( 'admin.php?page=np-shortcode&action=edit&id=' . $f['id'] ) ); ?>"><?php esc_html_e( 'Edit', 'newsletter-popup' ); ?></a>
                                <a class="button button-small np-danger"
                                   href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=np_delete_shortcode&id=' . $f['id'] ), 'np_delete_shortcode' ) ); ?>"
                                   onclick="return confirm('<?php echo esc_js( __( 'Delete this form?', 'newsletter-popup' ) ); ?>');">
                                    <?php esc_html_e( 'Delete', 'newsletter-popup' ); ?>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>

            <h2 class="np-section"><?php esc_html_e( 'Available Dynamic Placeholders', 'newsletter-popup' ); ?></h2>
            <table class="widefat striped" style="max-width:720px;">
                <thead><tr><th>Placeholder</th><th>Description</th></tr></thead>
                <tbody>
                    <tr><td><code>{{button_text}}</code></td><td><?php esc_html_e( 'Global button text (from Settings)', 'newsletter-popup' ); ?></td></tr>
                    <tr><td><code>{{placeholder}}</code></td><td><?php esc_html_e( 'Global input placeholder', 'newsletter-popup' ); ?></td></tr>
                    <tr><td><code>{{privacy_text}}</code></td><td><?php esc_html_e( 'Global privacy note', 'newsletter-popup' ); ?></td></tr>
                    <tr><td><code>{{ajax_url}}</code></td><td><?php esc_html_e( 'AJAX endpoint URL', 'newsletter-popup' ); ?></td></tr>
                    <tr><td><code>{{nonce}}</code></td><td><?php esc_html_e( 'Security nonce (must be inside form as hidden input)', 'newsletter-popup' ); ?></td></tr>
                </tbody>
            </table>

            <h2 class="np-section"><?php esc_html_e( 'Required HTML Structure', 'newsletter-popup' ); ?></h2>
            <p><?php esc_html_e( 'Your form must contain an input with name="email" and a submit button. The plugin detects it automatically.', 'newsletter-popup' ); ?></p>
            <pre style="background:#f8fafc;padding:16px;border-radius:8px;border:1px solid #e2e8f0;overflow:auto;font-size:13px;">&lt;form class="np-subscribe-form"&gt;
  &lt;input type="email" name="email" placeholder="{{placeholder}}" required&gt;
  &lt;button type="submit"&gt;{{button_text}}&lt;/button&gt;
  &lt;input type="hidden" name="nonce" value="{{nonce}}"&gt;
  &lt;p class="np-message"&gt;&lt;/p&gt;
&lt;/form&gt;</pre>
        </div>
        <?php
    }

    private static function render_editor( $id ) {
        $form = $id ? self::get( $id ) : null;
        $name = $form['name'] ?? '';
        $html = $form['html'] ?? self::default_html();
        ?>
        <div class="wrap np-wrap">
            <h1>
                <span class="dashicons dashicons-shortcode"></span>
                <?php echo $id ? esc_html__( 'Edit Form', 'newsletter-popup' ) : esc_html__( 'New Form', 'newsletter-popup' ); ?>
            </h1>
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=np-shortcode' ) ); ?>">&larr; <?php esc_html_e( 'Back to forms', 'newsletter-popup' ); ?></a>

            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                <input type="hidden" name="action" value="np_save_shortcode">
                <input type="hidden" name="sc_id" value="<?php echo esc_attr( $id ); ?>">
                <?php wp_nonce_field( 'np_save_shortcode' ); ?>

                <table class="form-table">
                    <tr>
                        <th><label for="sc_name"><?php esc_html_e( 'Form Name', 'newsletter-popup' ); ?></label></th>
                        <td><input type="text" id="sc_name" name="sc_name" value="<?php echo esc_attr( $name ); ?>" class="regular-text" required></td>
                    </tr>
                    <tr>
                        <th><label for="sc_html"><?php esc_html_e( 'Form HTML', 'newsletter-popup' ); ?></label></th>
                        <td>
                            <textarea id="sc_html" name="sc_html" rows="20" class="large-text code" style="font-family:Menlo,Consolas,monospace;font-size:13px;"><?php echo esc_textarea( $html ); ?></textarea>
                            <p class="description">
                                <?php esc_html_e( 'Paste any HTML. Must include an input with name="email" and a submit button. Use {{nonce}}, {{ajax_url}}, {{button_text}}, {{placeholder}}, {{privacy_text}} for dynamic values.', 'newsletter-popup' ); ?>
                            </p>
                        </td>
                    </tr>
                </table>

                <?php submit_button( $id ? __( 'Update Form', 'newsletter-popup' ) : __( 'Create Form', 'newsletter-popup' ) ); ?>
            </form>

            <?php if ( $id ) : ?>
                <h2 class="np-section"><?php esc_html_e( 'Your Shortcode', 'newsletter-popup' ); ?></h2>
                <p><input type="text" readonly value='[newsletter_form id="<?php echo esc_attr( $id ); ?>"]' class="large-text code" onclick="this.select();document.execCommand('copy');"></p>
            <?php endif; ?>
        </div>
        <?php
    }

    private static function default_html() {
        return <<<HTML
<div class="np-custom-form">
  <h3>Subscribe to our newsletter</h3>
  <p>Get the latest updates directly in your inbox.</p>
  <form class="np-subscribe-form">
    <input type="email" name="email" placeholder="{{placeholder}}" required>
    <button type="submit">{{button_text}}</button>
    <input type="hidden" name="nonce" value="{{nonce}}">
    <p class="np-message" role="status" aria-live="polite"></p>
  </form>
  <p class="np-privacy">{{privacy_text}}</p>
</div>
HTML;
    }
}

?>