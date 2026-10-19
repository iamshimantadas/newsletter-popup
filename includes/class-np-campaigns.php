<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class NP_Campaigns {

    public static function init() {
        add_action( 'admin_post_np_save_campaign',   [ __CLASS__, 'save' ] );
        add_action( 'admin_post_np_delete_campaign', [ __CLASS__, 'delete' ] );
        add_action( 'admin_post_np_send_campaign',   [ __CLASS__, 'queue_send' ] );

        add_filter( 'cron_schedules', [ __CLASS__, 'add_cron_schedule' ] );
        add_action( 'np_cron_send_batch', [ __CLASS__, 'send_batch' ] );

        if ( ! wp_next_scheduled( 'np_cron_send_batch' ) ) {
            wp_schedule_event( time() + 60, 'np_batch_interval', 'np_cron_send_batch' );
        }
    }

    public static function add_cron_schedule( $schedules ) {
        $s = NP_Settings::get();
        $interval = max( 60, (int) $s['batch_interval'] );
        $schedules['np_batch_interval'] = [
            'interval' => $interval,
            'display'  => sprintf( __( 'Every %d seconds (Newsletter)', 'newsletter-popup' ), $interval ),
        ];
        return $schedules;
    }

    public static function get_all( $limit = 50 ) {
        global $wpdb;
        $table = $wpdb->prefix . NP_Handler::TABLE_CAMPAIGNS;
        return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} ORDER BY id DESC LIMIT %d", $limit ) );
    }

    public static function get( $id ) {
        global $wpdb;
        $table = $wpdb->prefix . NP_Handler::TABLE_CAMPAIGNS;
        return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", (int) $id ) );
    }

    public static function save() {
        if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Unauthorized' );
        check_admin_referer( 'np_save_campaign' );

        $id      = (int) ( $_POST['id'] ?? 0 );
        $subject = sanitize_text_field( $_POST['subject'] ?? '' );
        $body    = wp_kses_post( $_POST['body'] ?? '' );

        if ( ! $subject || ! $body ) {
            wp_safe_redirect( add_query_arg( [ 'page' => 'np-campaigns', 'error' => 'empty' ], admin_url( 'admin.php' ) ) );
            exit;
        }

        global $wpdb;
        $table = $wpdb->prefix . NP_Handler::TABLE_CAMPAIGNS;

        if ( $id ) {
            $wpdb->update( $table, [ 'subject' => $subject, 'body' => $body ], [ 'id' => $id ], [ '%s', '%s' ], [ '%d' ] );
        } else {
            $wpdb->insert( $table, [
                'subject'    => $subject,
                'body'       => $body,
                'status'     => 'draft',
                'created_at' => current_time( 'mysql' ),
            ], [ '%s', '%s', '%s', '%s' ] );
        }

        wp_safe_redirect( add_query_arg( [ 'page' => 'np-campaigns', 'saved' => 1 ], admin_url( 'admin.php' ) ) );
        exit;
    }

    public static function delete() {
        if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Unauthorized' );
        check_admin_referer( 'np_delete_campaign' );

        $id = (int) ( $_GET['id'] ?? 0 );
        if ( $id ) {
            global $wpdb;
            $wpdb->delete( $wpdb->prefix . NP_Handler::TABLE_CAMPAIGNS, [ 'id' => $id ], [ '%d' ] );
            $wpdb->delete( $wpdb->prefix . NP_Handler::TABLE_QUEUE, [ 'campaign_id' => $id ], [ '%d' ] );
        }

        wp_safe_redirect( add_query_arg( [ 'page' => 'np-campaigns', 'deleted' => 1 ], admin_url( 'admin.php' ) ) );
        exit;
    }

    public static function queue_send() {
        if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Unauthorized' );
        check_admin_referer( 'np_send_campaign' );

        $id = (int) ( $_GET['id'] ?? 0 );
        $campaign = self::get( $id );
        if ( ! $campaign ) {
            wp_safe_redirect( add_query_arg( [ 'page' => 'np-campaigns', 'error' => 'notfound' ], admin_url( 'admin.php' ) ) );
            exit;
        }

        global $wpdb;
        $subs_table  = $wpdb->prefix . NP_Handler::TABLE_SUBSCRIBERS;
        $queue_table = $wpdb->prefix . NP_Handler::TABLE_QUEUE;
        $camp_table  = $wpdb->prefix . NP_Handler::TABLE_CAMPAIGNS;

        $wpdb->delete( $queue_table, [ 'campaign_id' => $id ], [ '%d' ] );

        $subs = $wpdb->get_results( "SELECT id, email FROM {$subs_table} WHERE status = 'subscribed'" );

        foreach ( $subs as $s ) {
            $wpdb->insert( $queue_table, [
                'campaign_id'   => $id,
                'subscriber_id' => (int) $s->id,
                'email'         => $s->email,
                'status'        => 'pending',
            ], [ '%d', '%d', '%s', '%s' ] );
        }

        $wpdb->update( $camp_table, [
            'status'      => 'sending',
            'total'       => count( $subs ),
            'sent'        => 0,
            'failed'      => 0,
            'started_at'  => current_time( 'mysql' ),
            'finished_at' => null,
        ], [ 'id' => $id ], [ '%s', '%d', '%d', '%d', '%s', '%s' ], [ '%d' ] );

        self::send_batch();

        wp_safe_redirect( add_query_arg( [ 'page' => 'np-campaigns', 'sending' => 1 ], admin_url( 'admin.php' ) ) );
        exit;
    }

    public static function send_batch() {
        global $wpdb;

        $settings    = NP_Settings::get();
        $batch_size  = max( 1, (int) $settings['batch_size'] );
        $queue_table = $wpdb->prefix . NP_Handler::TABLE_QUEUE;
        $camp_table  = $wpdb->prefix . NP_Handler::TABLE_CAMPAIGNS;

        $campaign = $wpdb->get_row( "SELECT * FROM {$camp_table} WHERE status = 'sending' ORDER BY id ASC LIMIT 1" );
        if ( ! $campaign ) {
            return;
        }

        $rows = $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM {$queue_table} WHERE campaign_id = %d AND status = 'pending' ORDER BY id ASC LIMIT %d",
            (int) $campaign->id,
            $batch_size
        ) );

        if ( empty( $rows ) ) {
            $wpdb->update( $camp_table, [
                'status'      => 'sent',
                'finished_at' => current_time( 'mysql' ),
            ], [ 'id' => $campaign->id ], [ '%s', '%s' ], [ '%d' ] );
            return;
        }

        foreach ( $rows as $row ) {
            $result = NP_Mailer::send( $row->email, $campaign->subject, $campaign->body );

            if ( is_wp_error( $result ) || ! $result ) {
                $wpdb->update( $queue_table, [
                    'status'  => 'failed',
                    'error'   => is_wp_error( $result ) ? $result->get_error_message() : 'Unknown error',
                    'sent_at' => current_time( 'mysql' ),
                ], [ 'id' => $row->id ], [ '%s', '%s', '%s' ], [ '%d' ] );

                $wpdb->query( $wpdb->prepare(
                    "UPDATE {$camp_table} SET failed = failed + 1 WHERE id = %d",
                    $campaign->id
                ) );
            } else {
                $wpdb->update( $queue_table, [
                    'status'  => 'sent',
                    'sent_at' => current_time( 'mysql' ),
                ], [ 'id' => $row->id ], [ '%s', '%s' ], [ '%d' ] );

                $wpdb->query( $wpdb->prepare(
                    "UPDATE {$camp_table} SET sent = sent + 1 WHERE id = %d",
                    $campaign->id
                ) );
            }
        }
    }

    public static function render_page() {
        if ( ! current_user_can( 'manage_options' ) ) return;

        $action = $_GET['action'] ?? 'list';
        $id     = (int) ( $_GET['id'] ?? 0 );

        if ( $action === 'new' || $action === 'edit' ) {
            self::render_editor( $action === 'edit' ? $id : 0 );
            return;
        }
        ?>
        <div class="wrap np-wrap">
            <h1>
                <span class="dashicons dashicons-email-alt"></span>
                <?php esc_html_e( 'Email Campaigns', 'newsletter-popup' ); ?>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=np-campaigns&action=new' ) ); ?>" class="page-title-action"><?php esc_html_e( 'Add New', 'newsletter-popup' ); ?></a>
            </h1>

            <?php if ( isset( $_GET['saved'] ) ) : ?><div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Campaign saved.', 'newsletter-popup' ); ?></p></div><?php endif; ?>
            <?php if ( isset( $_GET['deleted'] ) ) : ?><div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Campaign deleted.', 'newsletter-popup' ); ?></p></div><?php endif; ?>
            <?php if ( isset( $_GET['sending'] ) ) : ?><div class="notice notice-info is-dismissible"><p><?php esc_html_e( 'Campaign is now sending in the background. Refresh to see progress.', 'newsletter-popup' ); ?></p></div><?php endif; ?>
            <?php if ( isset( $_GET['error'] ) ) : ?><div class="notice notice-error is-dismissible"><p><?php esc_html_e( 'Something went wrong. Please try again.', 'newsletter-popup' ); ?></p></div><?php endif; ?>

            <?php $campaigns = self::get_all(); ?>

            <table class="wp-list-table widefat fixed striped np-table">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Subject', 'newsletter-popup' ); ?></th>
                        <th><?php esc_html_e( 'Status', 'newsletter-popup' ); ?></th>
                        <th><?php esc_html_e( 'Progress', 'newsletter-popup' ); ?></th>
                        <th><?php esc_html_e( 'Created', 'newsletter-popup' ); ?></th>
                        <th><?php esc_html_e( 'Actions', 'newsletter-popup' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                <?php if ( empty( $campaigns ) ) : ?>
                    <tr><td colspan="5"><?php esc_html_e( 'No campaigns yet.', 'newsletter-popup' ); ?></td></tr>
                <?php else : ?>
                    <?php foreach ( $campaigns as $c ) :
                        $progress = $c->total > 0 ? min( 100, round( ( $c->sent + $c->failed ) / $c->total * 100 ) ) : 0;
                        ?>
                        <tr>
                            <td><strong><?php echo esc_html( $c->subject ); ?></strong></td>
                            <td>
                                <span class="np-badge <?php echo $c->status === 'sent' ? 'np-badge-ok' : ( $c->status === 'sending' ? 'np-badge-info' : 'np-badge-warn' ); ?>">
                                    <?php echo esc_html( $c->status ); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ( $c->total ) : ?>
                                    <div class="np-progress"><div class="np-progress-bar" style="width: <?php echo esc_attr( $progress ); ?>%"></div></div>
                                    <small><?php echo esc_html( $c->sent ); ?> / <?php echo esc_html( $c->total ); ?> <?php if ( $c->failed ) echo '· ' . esc_html( $c->failed ) . ' failed'; ?></small>
                                <?php else : ?>
                                    —
                                <?php endif; ?>
                            </td>
                            <td><?php echo esc_html( $c->created_at ); ?></td>
                            <td class="np-actions">
                                <a class="button button-small" href="<?php echo esc_url( admin_url( 'admin.php?page=np-campaigns&action=edit&id=' . (int) $c->id ) ); ?>"><?php esc_html_e( 'Edit', 'newsletter-popup' ); ?></a>

                                <?php if ( $c->status === 'draft' || $c->status === 'sent' ) : ?>
                                    <a class="button button-primary button-small"
                                       href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=np_send_campaign&id=' . (int) $c->id ), 'np_send_campaign' ) ); ?>"
                                       onclick="return confirm('<?php echo esc_js( __( 'Send this campaign to all subscribers?', 'newsletter-popup' ) ); ?>');">
                                        <?php esc_html_e( 'Send', 'newsletter-popup' ); ?>
                                    </a>
                                <?php endif; ?>

                                <a class="button button-small np-danger"
                                   href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=np_delete_campaign&id=' . (int) $c->id ), 'np_delete_campaign' ) ); ?>"
                                   onclick="return confirm('<?php echo esc_js( __( 'Delete this campaign?', 'newsletter-popup' ) ); ?>');">
                                    <?php esc_html_e( 'Delete', 'newsletter-popup' ); ?>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    private static function render_editor( $id ) {
        $c = $id ? self::get( $id ) : null;
        $subject = $c ? $c->subject : '';
        $body    = $c ? $c->body : '';
        ?>
        <div class="wrap np-wrap">
            <h1><span class="dashicons dashicons-email-alt"></span> <?php echo $id ? esc_html__( 'Edit Campaign', 'newsletter-popup' ) : esc_html__( 'New Campaign', 'newsletter-popup' ); ?></h1>
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=np-campaigns' ) ); ?>">&larr; <?php esc_html_e( 'Back to campaigns', 'newsletter-popup' ); ?></a>

            <div class="np-template-picker" style="margin:16px 0;padding:20px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;">
                <label style="display:block;font-weight:600;margin-bottom:10px;color:#0f172a;font-size:14px;">⚡ Quick Template</label>
                <select class="np-template-select" style="min-width:320px;padding:8px 12px;border-radius:6px;border:1px solid #cbd5e1;">
                    <option value="">— Choose a template —</option>
                </select>
                <button type="button" class="button button-primary np-template-apply" style="margin-left:8px;">Apply Template</button>
                <p class="description" style="margin-top:10px;color:#64748b;">
                    <?php esc_html_e( 'Applying a template will replace the subject and body. Templates use', 'newsletter-popup' ); ?>
                    <code>{{site_name}}</code>, <code>{{email}}</code>, <code>{{unsubscribe_url}}</code>, <code>{{site_url}}</code>.
                </p>
            </div>

            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                <input type="hidden" name="action" value="np_save_campaign">
                <input type="hidden" name="id" value="<?php echo (int) $id; ?>">
                <?php wp_nonce_field( 'np_save_campaign' ); ?>

                <table class="form-table">
                    <tr>
                        <th><label for="subject"><?php esc_html_e( 'Subject', 'newsletter-popup' ); ?></label></th>
                        <td><input type="text" id="subject" name="subject" value="<?php echo esc_attr( $subject ); ?>" class="large-text" required></td>
                    </tr>
                    <tr>
                        <th><label for="body"><?php esc_html_e( 'Body (HTML allowed)', 'newsletter-popup' ); ?></label></th>
                        <td>
                            <?php
                            wp_editor( $body, 'body', [
                                'textarea_name' => 'body',
                                'textarea_rows' => 20,
                                'media_buttons' => false,
                                'teeny'         => false,
                            ] );
                            ?>
                        </td>
                    </tr>
                </table>

                <?php submit_button( $id ? __( 'Update Campaign', 'newsletter-popup' ) : __( 'Create Campaign', 'newsletter-popup' ) ); ?>
            </form>
        </div>
        <?php
    }
}

?>