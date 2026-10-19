<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class NP_Mailer {

    public static function init() {
        add_filter( 'wp_mail_from',      [ __CLASS__, 'filter_from_email' ] );
        add_filter( 'wp_mail_from_name', [ __CLASS__, 'filter_from_name' ] );
    }

    public static function filter_from_email( $email ) {
        $s = NP_Settings::get();
        return ! empty( $s['from_email'] ) && is_email( $s['from_email'] ) ? $s['from_email'] : $email;
    }

    public static function filter_from_name( $name ) {
        $s = NP_Settings::get();
        return ! empty( $s['from_name'] ) ? $s['from_name'] : $name;
    }

    /**
     * Send a campaign email. Uses wp_mail, so any SMTP plugin works.
     */
    public static function send( $to, $subject, $body ) {

        $unsubscribe_url = add_query_arg( [
            'np_unsub' => rawurlencode( base64_encode( $to ) ),
            'np_key'   => self::unsub_key( $to ),
        ], home_url( '/' ) );

        $replacements = [
            '{{email}}'           => $to,
            '{{site_name}}'       => get_bloginfo( 'name' ),
            '{{site_url}}'        => home_url(),
            '{{unsubscribe_url}}' => esc_url( $unsubscribe_url ),
        ];

        $subject = strtr( $subject, $replacements );
        $body    = strtr( $body, $replacements );

        $body = self::wrap_html( $body );
        $body = apply_filters( 'np_email_body', $body, $to, $subject );

        $headers = [
            'Content-Type: text/html; charset=UTF-8',
            'List-Unsubscribe: <' . $unsubscribe_url . '>',
        ];

        return wp_mail( $to, $subject, $body, $headers );
    }

    /**
     * Wrap user HTML in a minimal email template.
     */
    private static function wrap_html( $inner ) {
        $site = esc_html( get_bloginfo( 'name' ) );
        $url  = esc_url( home_url() );

        return '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>' . $site . '</title></head>'
             . '<body style="margin:0;padding:0;background:#f4f6f8;font-family:Arial,Helvetica,sans-serif;color:#1e293b;">'
             . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f6f8;padding:24px 0;">'
             . '<tr><td align="center">'
             . '<table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px;background:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 4px 12px rgba(0,0,0,0.05);">'
             . '<tr><td style="padding:20px 24px;border-bottom:1px solid #eef2f6;"><a href="' . $url . '" style="text-decoration:none;color:#2563eb;font-weight:700;font-size:16px;">' . $site . '</a></td></tr>'
             . '<tr><td style="padding:24px;font-size:15px;line-height:1.6;color:#1e293b;">' . $inner . '</td></tr>'
             . '<tr><td style="padding:16px 24px;background:#f8fafc;font-size:12px;color:#64748b;text-align:center;">'
             . '&copy; ' . esc_html( date( 'Y' ) ) . ' ' . $site . '. '
             . '<a href="{{unsubscribe_url}}" style="color:#64748b;">Unsubscribe</a>'
             . '</td></tr>'
             . '</table></td></tr></table></body></html>';
    }

    /**
     * Deterministic unsubscribe key.
     */
    public static function unsub_key( $email ) {
        $salt = wp_salt( 'auth' );
        return substr( hash_hmac( 'sha256', strtolower( $email ), $salt ), 0, 24 );
    }
}

/**
 * Frontend unsubscribe endpoint (query string).
 */
add_action( 'init', function () {
    if ( empty( $_GET['np_unsub'] ) || empty( $_GET['np_key'] ) ) {
        return;
    }

    $email = sanitize_email( base64_decode( rawurldecode( wp_unslash( $_GET['np_unsub'] ) ) ) );
    $key   = sanitize_text_field( wp_unslash( $_GET['np_key'] ) );

    if ( ! $email || ! is_email( $email ) ) {
        wp_die( esc_html__( 'Invalid unsubscribe link.', 'newsletter-popup' ) );
    }

    if ( ! hash_equals( NP_Mailer::unsub_key( $email ), $key ) ) {
        wp_die( esc_html__( 'Invalid unsubscribe link.', 'newsletter-popup' ) );
    }

    global $wpdb;
    $table = $wpdb->prefix . NP_Handler::TABLE_SUBSCRIBERS;
    $wpdb->update(
        $table,
        [ 'status' => 'unsubscribed' ],
        [ 'email'  => $email ],
        [ '%s' ],
        [ '%s' ]
    );

    wp_die(
        '<h2>' . esc_html__( 'You have been unsubscribed.', 'newsletter-popup' ) . '</h2>'
        . '<p><a href="' . esc_url( home_url( '/' ) ) . '">' . esc_html__( 'Back to site', 'newsletter-popup' ) . '</a></p>',
        esc_html__( 'Unsubscribed', 'newsletter-popup' ),
        [ 'response' => 200 ]
    );
} );

?>