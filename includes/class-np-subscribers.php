<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class NP_Subscribers {

    public static function init() {
        add_action( 'admin_post_np_export_subscribers', [ __CLASS__, 'export_csv' ] );
        add_action( 'admin_post_np_delete_subscriber',  [ __CLASS__, 'delete' ] );
        add_action( 'admin_post_np_add_subscriber',     [ __CLASS__, 'add' ] );
    }

    public static function is_subscribed( $email ) {
        global $wpdb;
        $table = $wpdb->prefix . NP_Handler::TABLE_SUBSCRIBERS;
        $email = sanitize_email( $email );
        if ( ! $email ) {
            return false;
        }
        $found = $wpdb->get_var( $wpdb->prepare(
            "SELECT id FROM {$table} WHERE email = %s AND status = 'subscribed' LIMIT 1",
            $email
        ) );
        return (bool) $found;
    }

    public static function count( $status = 'subscribed' ) {
        global $wpdb;
        $table = $wpdb->prefix . NP_Handler::TABLE_SUBSCRIBERS;
        if ( $status ) {
            return (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE status = %s", $status ) );
        }
        return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );
    }

    public static function get_list( $page = 1, $per_page = 20, $search = '' ) {
        global $wpdb;
        $table  = $wpdb->prefix . NP_Handler::TABLE_SUBSCRIBERS;
        $offset = max( 0, ( $page - 1 ) * $per_page );

        if ( $search !== '' ) {
            $like = '%' . $wpdb->esc_like( $search ) . '%';
            $rows = $wpdb->get_results( $wpdb->prepare(
                "SELECT * FROM {$table} WHERE email LIKE %s ORDER BY created_at DESC LIMIT %d OFFSET %d",
                $like, $per_page, $offset
            ) );
            $total = (int) $wpdb->get_var( $wpdb->prepare(
                "SELECT COUNT(*) FROM {$table} WHERE email LIKE %s", $like
            ) );
        } else {
            $rows  = $wpdb->get_results( $wpdb->prepare(
                "SELECT * FROM {$table} ORDER BY created_at DESC LIMIT %d OFFSET %d",
                $per_page, $offset
            ) );
            $total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );
        }

        return [ $rows, $total ];
    }

    public static function delete() {
        if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Unauthorized' );
        check_admin_referer( 'np_delete_subscriber' );

        $id = (int) ( $_GET['id'] ?? 0 );
        if ( $id ) {
            global $wpdb;
            $table = $wpdb->prefix . NP_Handler::TABLE_SUBSCRIBERS;
            $wpdb->delete( $table, [ 'id' => $id ], [ '%d' ] );
        }

        wp_safe_redirect( add_query_arg( [ 'page' => 'np-dashboard', 'deleted' => 1 ], admin_url( 'admin.php' ) ) );
        exit;
    }

    public static function add() {
        if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Unauthorized' );
        check_admin_referer( 'np_add_subscriber' );

        $email = sanitize_email( $_POST['email'] ?? '' );
        if ( $email && is_email( $email ) && ! self::is_subscribed( $email ) ) {
            global $wpdb;
            $table = $wpdb->prefix . NP_Handler::TABLE_SUBSCRIBERS;
            $wpdb->insert( $table, [
                'email'      => $email,
                'status'     => 'subscribed',
                'source'     => 'manual',
                'created_at' => current_time( 'mysql' ),
            ], [ '%s', '%s', '%s', '%s' ] );
        }

        wp_safe_redirect( add_query_arg( [ 'page' => 'np-dashboard', 'added' => 1 ], admin_url( 'admin.php' ) ) );
        exit;
    }

    public static function export_csv() {
        if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Unauthorized' );
        check_admin_referer( 'np_export_subscribers' );

        global $wpdb;
        $table = $wpdb->prefix . NP_Handler::TABLE_SUBSCRIBERS;
        $rows  = $wpdb->get_results( "SELECT email, status, source, created_at FROM {$table} ORDER BY created_at DESC", ARRAY_A );

        nocache_headers();
        header( 'Content-Type: text/csv; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename=subscribers-' . date( 'Y-m-d' ) . '.csv' );

        $out = fopen( 'php://output', 'w' );
        fputcsv( $out, [ 'Email', 'Status', 'Source', 'Subscribed At' ] );
        foreach ( $rows as $r ) {
            fputcsv( $out, [ $r['email'], $r['status'], $r['source'], $r['created_at'] ] );
        }
        fclose( $out );
        exit;
    }

    public static function render_page() {
        if ( ! current_user_can( 'manage_options' ) ) return;

        $page     = max( 1, (int) ( $_GET['paged'] ?? 1 ) );
        $per_page = 20;
        $search   = sanitize_text_field( $_GET['s'] ?? '' );
        list( $rows, $total ) = self::get_list( $page, $per_page, $search );
        $pages    = max( 1, (int) ceil( $total / $per_page ) );

        $subs_count  = self::count( 'subscribed' );
        $unsub_count = self::count( 'unsubscribed' );
        $all_count   = self::count( null );
        ?>
        <div class="wrap np-wrap">
            <h1><span class="dashicons dashicons-email-alt"></span> <?php esc_html_e( 'Newsletter Subscribers', 'newsletter-popup' ); ?></h1>

            <?php if ( isset( $_GET['deleted'] ) ) : ?>
                <div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Subscriber deleted.', 'newsletter-popup' ); ?></p></div>
            <?php endif; ?>
            <?php if ( isset( $_GET['added'] ) ) : ?>
                <div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Subscriber added.', 'newsletter-popup' ); ?></p></div>
            <?php endif; ?>

            <div class="np-stats">
                <div class="np-stat"><span class="np-stat-num"><?php echo esc_html( $all_count ); ?></span><span class="np-stat-label"><?php esc_html_e( 'Total', 'newsletter-popup' ); ?></span></div>
                <div class="np-stat np-stat-ok"><span class="np-stat-num"><?php echo esc_html( $subs_count ); ?></span><span class="np-stat-label"><?php esc_html_e( 'Subscribed', 'newsletter-popup' ); ?></span></div>
                <div class="np-stat np-stat-warn"><span class="np-stat-num"><?php echo esc_html( $unsub_count ); ?></span><span class="np-stat-label"><?php esc_html_e( 'Unsubscribed', 'newsletter-popup' ); ?></span></div>
            </div>

            <div class="np-toolbar">
                <form method="get" class="np-search-form">
                    <input type="hidden" name="page" value="np-dashboard">
                    <input type="search" name="s" value="<?php echo esc_attr( $search ); ?>" placeholder="<?php esc_attr_e( 'Search email…', 'newsletter-popup' ); ?>">
                    <button class="button"><?php esc_html_e( 'Search', 'newsletter-popup' ); ?></button>
                </form>

                <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="np-add-form">
                    <input type="hidden" name="action" value="np_add_subscriber">
                    <?php wp_nonce_field( 'np_add_subscriber' ); ?>
                    <input type="email" name="email" placeholder="add@example.com" required>
                    <button class="button button-primary"><?php esc_html_e( 'Add', 'newsletter-popup' ); ?></button>
                </form>

                <a class="button" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=np_export_subscribers' ), 'np_export_subscribers' ) ); ?>">
                    <span class="dashicons dashicons-download"></span> <?php esc_html_e( 'Export CSV', 'newsletter-popup' ); ?>
                </a>
            </div>

            <table class="wp-list-table widefat fixed striped np-table">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Email', 'newsletter-popup' ); ?></th>
                        <th><?php esc_html_e( 'Status', 'newsletter-popup' ); ?></th>
                        <th><?php esc_html_e( 'Source', 'newsletter-popup' ); ?></th>
                        <th><?php esc_html_e( 'Subscribed At', 'newsletter-popup' ); ?></th>
                        <th><?php esc_html_e( 'Actions', 'newsletter-popup' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                <?php if ( empty( $rows ) ) : ?>
                    <tr><td colspan="5"><?php esc_html_e( 'No subscribers yet.', 'newsletter-popup' ); ?></td></tr>
                <?php else : ?>
                    <?php foreach ( $rows as $r ) : ?>
                        <tr>
                            <td><strong><?php echo esc_html( $r->email ); ?></strong></td>
                            <td><span class="np-badge <?php echo $r->status === 'subscribed' ? 'np-badge-ok' : 'np-badge-warn'; ?>"><?php echo esc_html( $r->status ); ?></span></td>
                            <td><?php echo esc_html( $r->source ); ?></td>
                            <td><?php echo esc_html( $r->created_at ); ?></td>
                            <td>
                                <a class="button button-small" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=np_delete_subscriber&id=' . (int) $r->id ), 'np_delete_subscriber' ) ); ?>"
                                   onclick="return confirm('<?php echo esc_js( __( 'Delete this subscriber?', 'newsletter-popup' ) ); ?>');">
                                    <?php esc_html_e( 'Delete', 'newsletter-popup' ); ?>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>

            <?php if ( $pages > 1 ) : ?>
                <div class="tablenav"><div class="tablenav-pages">
                    <?php
                    echo paginate_links( [
                        'base'      => add_query_arg( 'paged', '%#%' ),
                        'format'    => '',
                        'prev_text' => '&laquo;',
                        'next_text' => '&raquo;',
                        'total'     => $pages,
                        'current'   => $page,
                    ] );
                    ?>
                </div></div>
            <?php endif; ?>
        </div>
        <?php
    }
}

?>