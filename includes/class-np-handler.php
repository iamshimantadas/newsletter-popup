<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

use Egulias\EmailValidator\EmailValidator;
use Egulias\EmailValidator\Validation\DNSCheckValidation;
use Egulias\EmailValidator\Validation\MultipleValidationWithAnd;
use Egulias\EmailValidator\Validation\RFCValidation;

class NP_Handler {

    const TABLE_SUBSCRIBERS = 'np_subscribers';
    const TABLE_CAMPAIGNS   = 'np_campaigns';
    const TABLE_QUEUE       = 'np_campaign_queue';

    public static function activate() {
        self::create_tables();
        NP_Settings::seed_defaults();
    }

    public static function deactivate() {
        // Nothing destructive.
    }

    public static function create_tables() {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $charset = $wpdb->get_charset_collate();

        $subscribers = $wpdb->prefix . self::TABLE_SUBSCRIBERS;
        $campaigns   = $wpdb->prefix . self::TABLE_CAMPAIGNS;
        $queue       = $wpdb->prefix . self::TABLE_QUEUE;

        $sql1 = "CREATE TABLE {$subscribers} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            email VARCHAR(190) NOT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'subscribed',
            source VARCHAR(50) NOT NULL DEFAULT 'popup',
            ip VARCHAR(45) DEFAULT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY email (email),
            KEY status (status)
        ) {$charset};";

        $sql2 = "CREATE TABLE {$campaigns} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            subject VARCHAR(255) NOT NULL,
            body LONGTEXT NOT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'draft',
            total INT(11) NOT NULL DEFAULT 0,
            sent INT(11) NOT NULL DEFAULT 0,
            failed INT(11) NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            started_at DATETIME DEFAULT NULL,
            finished_at DATETIME DEFAULT NULL,
            PRIMARY KEY (id),
            KEY status (status)
        ) {$charset};";

        $sql3 = "CREATE TABLE {$queue} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            campaign_id BIGINT(20) UNSIGNED NOT NULL,
            subscriber_id BIGINT(20) UNSIGNED NOT NULL,
            email VARCHAR(190) NOT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'pending',
            error TEXT DEFAULT NULL,
            sent_at DATETIME DEFAULT NULL,
            PRIMARY KEY (id),
            KEY campaign_id (campaign_id),
            KEY status (status)
        ) {$charset};";

        dbDelta( $sql1 );
        dbDelta( $sql2 );
        dbDelta( $sql3 );
    }

    public static function is_valid_email( $email ) {
        if ( ! class_exists( EmailValidator::class ) ) {
            return (bool) is_email( $email );
        }

        $validator = new EmailValidator();
        $chain     = new MultipleValidationWithAnd( [
            new RFCValidation(),
            new DNSCheckValidation(),
        ] );

        return $validator->isValid( $email, $chain );
    }

    public static function handle_subscribe() {

        if ( ! check_ajax_referer( 'np_subscribe_nonce', 'nonce', false ) ) {
            wp_send_json_error( [ 'message' => __( 'Security check failed. Please refresh the page.', 'newsletter-popup' ) ], 403 );
        }

        $email = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';

        if ( empty( $email ) || ! is_email( $email ) ) {
            wp_send_json_error( [ 'message' => __( 'Please enter a valid email address.', 'newsletter-popup' ) ], 400 );
        }

        if ( ! self::is_valid_email( $email ) ) {
            wp_send_json_error( [ 'message' => __( 'That email could not be verified. Please check and try again.', 'newsletter-popup' ) ], 422 );
        }

        if ( NP_Subscribers::is_subscribed( $email ) ) {
            wp_send_json_success( [
                'message'    => __( 'You are already subscribed. Thanks!', 'newsletter-popup' ),
                'subscribed' => true,
            ] );
        }

        global $wpdb;
        $table = $wpdb->prefix . self::TABLE_SUBSCRIBERS;
        $ip    = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : null;

        $ok = $wpdb->insert(
            $table,
            [
                'email'      => $email,
                'status'     => 'subscribed',
                'source'     => 'popup',
                'ip'         => $ip,
                'created_at' => current_time( 'mysql' ),
            ],
            [ '%s', '%s', '%s', '%s', '%s' ]
        );

        if ( ! $ok ) {
            wp_send_json_error( [ 'message' => __( 'Something went wrong. Please try again later.', 'newsletter-popup' ) ], 500 );
        }

        do_action( 'np_new_subscriber', $email );

        wp_send_json_success( [
            'message'    => __( 'Thanks for subscribing!', 'newsletter-popup' ),
            'subscribed' => true,
        ] );
    }
}

add_action( 'wp_ajax_np_subscribe',        [ 'NP_Handler', 'handle_subscribe' ] );
add_action( 'wp_ajax_nopriv_np_subscribe', [ 'NP_Handler', 'handle_subscribe' ] );

?>