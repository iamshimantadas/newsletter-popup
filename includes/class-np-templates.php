<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * NP_Templates — 24 Professional Email Templates with REAL SITE DATA + IMAGES
 *
 * Uses inline CSS with proper background colors for email clients.
 * All templates include featured images when available.
 */
class NP_Templates {

    public static function init() {
        // Consumed by admin.
    }

    public static function all() {
        return [
            'top10'              => [ 'label' => '1. Top 10 / Top 20',                'subject' => 'Top 10 picks from {{site_name}}',                     'body' => self::top10() ],
            'weekly'             => [ 'label' => '2. Weekly Newsletter',              'subject' => 'This week at {{site_name}}',                          'body' => self::weekly() ],
            'daily'              => [ 'label' => '3. Daily Digest',                   'subject' => 'Your daily digest from {{site_name}}',                'body' => self::daily() ],
            'new_arrivals'       => [ 'label' => '4. New Arrivals',                   'subject' => 'Fresh on {{site_name}} — new this week',              'body' => self::new_arrivals() ],
            'trending'           => [ 'label' => '5. Trending Now',                   'subject' => 'What everyone is reading on {{site_name}}',           'body' => self::trending() ],
            'popular'            => [ 'label' => '6. Most Popular',                   'subject' => 'All-time favorites from {{site_name}}',               'body' => self::popular() ],
            'editors_picks'      => [ 'label' => '7. Editor\'s Picks',                'subject' => 'Handpicked for you from {{site_name}}',               'body' => self::editors_picks() ],
            'category_roundup'   => [ 'label' => '8. Category Roundup',               'subject' => 'Updates from your favorite categories',               'body' => self::category_roundup() ],
            'seasonal'           => [ 'label' => '9. Seasonal Campaign',              'subject' => 'Seasonal picks just for you',                         'body' => self::seasonal() ],
            'special_offers'     => [ 'label' => '10. Special Offers',                'subject' => 'Exclusive offer inside — ends soon',                  'body' => self::special_offers() ],
            'product_spotlight'  => [ 'label' => '11. Product Spotlight',             'subject' => 'Spotlight: our newest favorite',                      'body' => self::product_spotlight() ],
            'product_comparison' => [ 'label' => '12. Product Comparison',            'subject' => 'Which one is right for you?',                         'body' => self::product_comparison() ],
            'back_in_stock'      => [ 'label' => '13. Back in Stock',                 'subject' => 'It\'s back — order before it sells out again',        'body' => self::back_in_stock() ],
            'price_drop'         => [ 'label' => '14. Price-Drop Alert',              'subject' => 'Price dropped on something you\'ll love',             'body' => self::price_drop() ],
            'events'             => [ 'label' => '15. Upcoming Events',               'subject' => 'Upcoming events at {{site_name}}',                    'body' => self::events() ],
            'deadlines'          => [ 'label' => '16. Upcoming Deadlines',            'subject' => 'Don\'t miss these important dates',                   'body' => self::deadlines() ],
            'tips'               => [ 'label' => '17. Tips and Tutorials',            'subject' => 'New tips and tutorials for you',                      'body' => self::tips() ],
            'case_studies'       => [ 'label' => '18. Case Studies & Success Stories', 'subject' => 'Real success stories from our community',            'body' => self::case_studies() ],
            'quiz'               => [ 'label' => '19. Quiz or Poll',                  'subject' => 'We want your opinion — 10-second question',           'body' => self::quiz() ],
            'personalized'       => [ 'label' => '20. Personalized Recommendations',  'subject' => 'Handpicked recommendations for you',                  'body' => self::personalized() ],
            'welcome'            => [ 'label' => '21. Welcome Series',                'subject' => 'Welcome to {{site_name}} 👋',                          'body' => self::welcome() ],
            'reengagement'       => [ 'label' => '22. Re-Engagement Campaign',        'subject' => 'We miss you at {{site_name}}',                        'body' => self::reengagement() ],
            'abandoned_cart'     => [ 'label' => '23. Abandoned Cart Reminder',       'subject' => 'You left something behind',                           'body' => self::abandoned_cart() ],
            'monthly_recap'      => [ 'label' => '24. Monthly Recap',                 'subject' => 'Your monthly recap from {{site_name}}',               'body' => self::monthly_recap() ],
        ];
    }

    /* =========================================================
       DATA HELPERS
    ========================================================= */

    private static function get_popular_posts( $count = 5, $days = 30 ) {
        $args = [
            'post_type'      => 'post',
            'post_status'    => 'publish',
            'posts_per_page' => $count,
            'date_query'     => [ [ 'after' => date( 'Y-m-d', strtotime( "-{$days} days" ) ) ] ],
            'meta_key'       => 'post_views',
            'orderby'        => 'meta_value_num',
            'order'          => 'DESC',
        ];
        $q = new WP_Query( $args );

        if ( ! $q->have_posts() ) {
            $q = new WP_Query( [
                'post_type'      => 'post',
                'post_status'    => 'publish',
                'posts_per_page' => $count,
                'orderby'        => 'comment_count',
                'order'          => 'DESC',
            ] );
        }
        return $q;
    }

    private static function get_latest_posts( $count = 5, $post_type = 'post' ) {
        return new WP_Query( [
            'post_type'      => $post_type,
            'post_status'    => 'publish',
            'posts_per_page' => $count,
            'orderby'        => 'date',
            'order'          => 'DESC',
        ] );
    }

    private static function get_category_posts( $category_slug, $count = 3 ) {
        return new WP_Query( [
            'post_type'      => 'post',
            'post_status'    => 'publish',
            'posts_per_page' => $count,
            'category_name'  => $category_slug,
        ] );
    }

    private static function get_top_categories( $count = 3 ) {
        return get_categories( [
            'orderby'    => 'count',
            'order'      => 'DESC',
            'number'     => $count,
            'hide_empty' => true,
        ] );
    }

    private static function get_best_selling_products( $count = 4 ) {
        if ( ! class_exists( 'WooCommerce' ) ) return null;
        return new WP_Query( [
            'post_type'      => 'product',
            'post_status'    => 'publish',
            'posts_per_page' => $count,
            'meta_key'       => 'total_sales',
            'orderby'        => 'meta_value_num',
            'order'          => 'DESC',
        ] );
    }

    private static function get_new_products( $count = 4 ) {
        if ( ! class_exists( 'WooCommerce' ) ) return null;
        return new WP_Query( [
            'post_type'      => 'product',
            'post_status'    => 'publish',
            'posts_per_page' => $count,
            'orderby'        => 'date',
            'order'          => 'DESC',
        ] );
    }

    private static function get_sale_products( $count = 1 ) {
        if ( ! class_exists( 'WooCommerce' ) ) return null;
        return new WP_Query( [
            'post_type'      => 'product',
            'post_status'    => 'publish',
            'posts_per_page' => $count,
            'post__in'       => array_merge( [ 0 ], wc_get_product_ids_on_sale() ),
            'orderby'        => 'date',
            'order'          => 'DESC',
        ] );
    }

    private static function get_upcoming_events( $count = 3 ) {
        return new WP_Query( [
            'post_type'      => 'tribe_events',
            'post_status'    => 'publish',
            'posts_per_page' => $count,
            'meta_key'       => '_EventStartDate',
            'orderby'        => 'meta_value',
            'order'          => 'ASC',
            'meta_query'     => [
                [ 'key' => '_EventStartDate', 'value' => current_time( 'Y-m-d H:i:s' ), 'compare' => '>=' ],
            ],
        ] );
    }

    /**
     * Get the featured image URL (or a fallback).
     */
    private static function get_image_url( $post_id, $size = 'medium' ) {
        if ( has_post_thumbnail( $post_id ) ) {
            $img = wp_get_attachment_image_src( get_post_thumbnail_id( $post_id ), $size );
            return $img ? $img[0] : '';
        }
        // WooCommerce: product gallery fallback.
        if ( function_exists( 'wc_get_product' ) ) {
            $product = wc_get_product( $post_id );
            if ( $product ) {
                $image_id = $product->get_image_id();
                if ( $image_id ) {
                    $img = wp_get_attachment_image_src( $image_id, $size );
                    return $img ? $img[0] : '';
                }
            }
        }
        return '';
    }

    /**
     * Render an image block for emails — safe with alt text and sizing.
     */
    private static function image_block( $post_id, $width = 560, $height = 220, $radius = 12 ) {
        $url = self::get_image_url( $post_id, 'large' );
        if ( ! $url ) {
            return '';
        }
        return '<img src="' . esc_url( $url ) . '" alt="' . esc_attr( get_the_title( $post_id ) ) . '" width="' . (int) $width . '" style="display:block;width:100%;max-width:' . (int) $width . 'px;height:auto;border:0;outline:none;text-decoration:none;border-radius:' . (int) $radius . 'px;margin:0;" />';
    }

    /**
     * Render a small thumbnail (for list items).
     */
    private static function thumb( $post_id, $size = 110 ) {
        $url = self::get_image_url( $post_id, 'medium' );
        if ( ! $url ) {
            return '';
        }
        return '<img src="' . esc_url( $url ) . '" alt="' . esc_attr( get_the_title( $post_id ) ) . '" width="' . (int) $size . '" style="display:block;width:' . (int) $size . 'px;height:' . (int) $size . 'px;object-fit:cover;border:0;outline:none;text-decoration:none;border-radius:10px;" />';
    }

    /* =========================================================
       SHARED BLOCKS
    ========================================================= */

    /**
     * Colored hero — with background color and padding.
     */
    private static function hero( $title, $subtitle, $bg = '#2563eb', $text = '#ffffff', $subtext = '#dbeafe' ) {
        return '
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
  <tr>
    <td style="background:' . $bg . ';padding:36px 32px;border-radius:14px;text-align:left;">
      <h1 style="margin:0;font-size:26px;font-weight:700;color:' . $text . ';line-height:1.3;letter-spacing:-0.02em;">' . $title . '</h1>
      <p style="margin:10px 0 0;font-size:16px;color:' . $subtext . ';line-height:1.5;">' . $subtitle . '</p>
    </td>
  </tr>
</table>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;"><tr><td style="height:28px;line-height:28px;font-size:0;">&nbsp;</td></tr></table>';
    }

    /**
     * CTA button.
     */
    private static function cta( $label, $url = '#', $bg = '#2563eb', $text = '#ffffff' ) {
        return '
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;margin-top:28px;">
  <tr><td align="center">
    <a href="' . esc_url( $url ) . '" style="display:inline-block;background:' . $bg . ';color:' . $text . ';text-decoration:none;padding:16px 36px;border-radius:8px;font-weight:600;font-size:16px;min-height:44px;box-sizing:border-box;line-height:1.2;">' . $label . '</a>
  </td></tr>
</table>';
    }

    /**
     * Spacer.
     */
    private static function spacer( $px = 24 ) {
        return '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;"><tr><td style="height:' . (int) $px . 'px;line-height:' . (int) $px . 'px;font-size:0;">&nbsp;</td></tr></table>';
    }

    /**
     * Numbered list item with optional thumbnail.
     */
    private static function numbered_item( $number, $post_id, $color = '#2563eb', $is_last = false ) {
        $title = get_the_title( $post_id );
        $url   = get_permalink( $post_id );
        $desc  = wp_trim_words( get_the_excerpt( $post_id ), 15, '…' );
        $img   = self::get_image_url( $post_id, 'medium' );
        $bottom = $is_last ? '0' : '16px';
        $border = $is_last ? 'none' : '1px solid #eef2f6';

        $img_cell = '';
        if ( $img ) {
            $img_cell = '
      <td width="110" style="vertical-align:top;padding-right:16px;">
        <a href="' . esc_url( $url ) . '" style="text-decoration:none;">
          <img src="' . esc_url( $img ) . '" alt="' . esc_attr( $title ) . '" width="110" style="display:block;width:110px;height:110px;object-fit:cover;border:0;border-radius:10px;" />
        </a>
      </td>';
        }

        return '
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;margin-bottom:20px;">
  <tr>
    <td width="48" style="vertical-align:top;padding-right:14px;">
      <div style="width:44px;height:44px;background:' . $color . ';border-radius:10px;text-align:center;line-height:44px;font-size:17px;font-weight:800;color:#ffffff;">' . $number . '</div>
    </td>' . $img_cell . '
    <td style="vertical-align:top;padding-bottom:' . $bottom . ';border-bottom:' . $border . ';">
      <a href="' . esc_url( $url ) . '" style="font-size:17px;font-weight:700;color:#0f172a;text-decoration:none;display:block;margin-bottom:6px;line-height:1.4;">' . esc_html( $title ) . '</a>
      <p style="margin:0 0 10px;font-size:14px;color:#64748b;line-height:1.5;">' . esc_html( $desc ) . '</p>
      <a href="' . esc_url( $url ) . '" style="font-size:14px;font-weight:600;color:#2563eb;text-decoration:none;">Read more &rarr;</a>
    </td>
  </tr>
</table>';
    }

    /**
     * Article row with optional thumbnail (no number).
     */
    private static function article_row( $post_id, $is_last = false, $show_img = true ) {
        $title = get_the_title( $post_id );
        $url   = get_permalink( $post_id );
        $desc  = wp_trim_words( get_the_excerpt( $post_id ), 15, '…' );
        $img   = $show_img ? self::get_image_url( $post_id, 'medium' ) : '';
        $border = $is_last ? 'none' : '1px solid #eef2f6';
        $padding = $is_last ? '16px 0 0' : '16px 0';

        $img_cell = '';
        if ( $img ) {
            $img_cell = '
      <td width="90" style="vertical-align:top;padding-right:14px;">
        <a href="' . esc_url( $url ) . '" style="text-decoration:none;">
          <img src="' . esc_url( $img ) . '" alt="' . esc_attr( $title ) . '" width="90" style="display:block;width:90px;height:90px;object-fit:cover;border:0;border-radius:10px;" />
        </a>
      </td>';
        }

        return '
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
  <tr>
    <td style="padding:' . $padding . ';border-bottom:' . $border . ';">
      <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
        <tr>' . $img_cell . '
          <td style="vertical-align:top;">
            <a href="' . esc_url( $url ) . '" style="font-size:16px;font-weight:600;color:#0f172a;text-decoration:none;display:block;margin-bottom:4px;line-height:1.4;">' . esc_html( $title ) . '</a>
            <span style="font-size:14px;color:#64748b;line-height:1.45;">' . esc_html( $desc ) . '</span>
          </td>
        </tr>
      </table>
    </td>
  </tr>
</table>';
    }

    /* =========================================================
       1. TOP 10 — 10 recent posts with thumbnails
    ========================================================= */
    private static function top10() {
        // Get 10 most recent published posts.
        $posts = self::get_latest_posts( 10, 'post' );

        $out = self::hero(
            'Top 10 Picks This Week',
            'Our most recent articles from {{site_name}} — handpicked for you.',
            '#0f172a',
            '#ffffff',
            '#93c5fd'
        );

        $i = 1;
        $total = $posts->post_count;
        $colors = [ '#2563eb', '#16a34a', '#d97706', '#7c3aed', '#dc2626', '#0891b2', '#db2777', '#ca8a04', '#059669', '#4f46e5' ];

        if ( $posts->have_posts() ) {
            while ( $posts->have_posts() ) {
                $posts->the_post();
                $out .= self::numbered_item(
                    $i,
                    get_the_ID(),
                    $colors[ ( $i - 1 ) % 10 ],
                    $i === $total
                );
                $i++;
            }
            wp_reset_postdata();
        } else {
            $out .= '<p style="color:#64748b;font-size:15px;line-height:1.6;">Publish posts and they will appear here as top picks.</p>';
        }

        $out .= self::cta( 'Read all articles', home_url( '/' ) );
        return $out;
    }

    /* =========================================================
       2. WEEKLY — featured post with big image
    ========================================================= */
    private static function weekly() {
        $latest = self::get_latest_posts( 3, 'post' );
        $featured = null;
        $secondary = [];

        if ( $latest->have_posts() ) {
            $featured = $latest->posts[0];
            $secondary = array_slice( $latest->posts, 1 );
        }

        $out = self::hero(
            'This Week at {{site_name}}',
            'A roundup of everything worth reading from the past 7 days.',
            '#1e40af',
            '#ffffff',
            '#bfdbfe'
        );

        if ( $featured ) {
            $img = self::image_block( $featured->ID, 560, 280 );
            $out .= '
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;margin-bottom:24px;background:#f8fafc;border-radius:14px;overflow:hidden;">
  ' . ( $img ? '<tr><td style="padding:0;">' . $img . '</td></tr>' : '' ) . '
  <tr><td style="padding:28px;">
    <div style="font-size:11px;font-weight:700;letter-spacing:0.15em;text-transform:uppercase;color:#2563eb;margin-bottom:8px;">Featured this week</div>
    <a href="' . esc_url( get_permalink( $featured ) ) . '" style="font-size:22px;font-weight:700;color:#0f172a;text-decoration:none;display:block;margin-bottom:10px;line-height:1.35;">' . esc_html( get_the_title( $featured ) ) . '</a>
    <p style="margin:0 0 14px;font-size:15px;color:#475569;line-height:1.6;">' . esc_html( wp_trim_words( get_the_excerpt( $featured ), 25, '…' ) ) . '</p>
    <a href="' . esc_url( get_permalink( $featured ) ) . '" style="font-size:15px;font-weight:600;color:#2563eb;text-decoration:none;">Read the full story &rarr;</a>
  </td></tr>
</table>';
        }

        if ( ! empty( $secondary ) ) {
            $out .= self::spacer( 8 );
            $out .= '<div style="font-size:12px;font-weight:700;letter-spacing:0.15em;text-transform:uppercase;color:#64748b;margin:0 0 16px;">Also worth reading</div>';
            $i = 0;
            $total = count( $secondary );
            foreach ( $secondary as $post ) {
                $i++;
                $out .= self::article_row( $post->ID, $i === $total );
            }
        }

        if ( ! $featured && empty( $secondary ) ) {
            $out .= '<p style="color:#64748b;font-size:15px;line-height:1.6;">No posts published this week yet. Your latest content will appear here automatically.</p>';
        }

        $out .= self::cta( 'Read more on the blog', home_url( '/' ) );
        return $out;
    }

    /* =========================================================
       3. DAILY DIGEST — with thumbnails
    ========================================================= */
    private static function daily() {
        $today = self::get_latest_posts( 5, 'post' );

        $out = self::hero(
            'Your Daily Digest',
            'Fresh content published recently on {{site_name}}.',
            '#065f46',
            '#ffffff',
            '#a7f3d0'
        );

        $i = 0;
        $total = $today->post_count;

        if ( $today->have_posts() ) {
            while ( $today->have_posts() ) {
                $today->the_post();
                $i++;
                $out .= self::article_row( get_the_ID(), $i === $total );
            }
            wp_reset_postdata();
        } else {
            $out .= '<p style="color:#64748b;font-size:15px;line-height:1.6;">No new content today. Check back tomorrow — your latest posts will be summarized here automatically.</p>';
        }

        $out .= '
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;margin-top:24px;">
  <tr><td style="padding:14px 18px;background:#ecfdf5;border-left:3px solid #10b981;border-radius:8px;font-size:13px;color:#065f46;line-height:1.5;">
    <strong>Tip:</strong> Bookmark this email to reference these updates later.
  </td></tr>
</table>';
        return $out;
    }

    /* =========================================================
       4. NEW ARRIVALS — grid with images
    ========================================================= */
    private static function new_arrivals() {
        $is_woo = class_exists( 'WooCommerce' );
        $items = $is_woo ? self::get_new_products( 4 ) : self::get_latest_posts( 4 );

        $out = self::hero(
            'Fresh on {{site_name}}',
            'The newest additions to our site — just published.',
            '#7c3aed',
            '#ffffff',
            '#ddd6fe'
        );

        if ( $items && $items->have_posts() ) {
            $out .= '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;"><tr>';
            $i = 0;
            while ( $items->have_posts() ) {
                $items->the_post();
                $pid = get_the_ID();
                if ( $i > 0 && $i % 2 === 0 ) {
                    $out .= '</tr><tr>';
                }
                $img = self::image_block( $pid, 260, 180, 10 );
                $pad_left  = ( $i % 2 === 0 ) ? '0' : '8px';
                $pad_right = ( $i % 2 === 0 ) ? '8px' : '0';
                $out .= '
<td width="50%" style="vertical-align:top;padding:0 ' . $pad_right . ' 16px ' . $pad_left . ';">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;border:1px solid #e2e8f0;border-radius:12px;overflow:hidden;background:#ffffff;">
    ' . ( $img ? '<tr><td style="padding:0;">' . $img . '</td></tr>' : '' ) . '
    <tr><td style="padding:18px;">
      <div style="font-size:10px;font-weight:700;letter-spacing:0.15em;text-transform:uppercase;color:#7c3aed;margin-bottom:6px;">New</div>
      <h3 style="margin:0 0 8px;font-size:15px;font-weight:700;color:#0f172a;line-height:1.35;">' . esc_html( get_the_title() ) . '</h3>
      <p style="margin:0 0 12px;font-size:13px;color:#64748b;line-height:1.5;">' . esc_html( wp_trim_words( get_the_excerpt(), 12, '…' ) ) . '</p>
      <a href="' . esc_url( get_permalink() ) . '" style="font-size:13px;font-weight:600;color:#2563eb;text-decoration:none;">View &rarr;</a>
    </td></tr>
  </table>
</td>';
                $i++;
            }
            $out .= '</tr></table>';
            wp_reset_postdata();
        } else {
            $out .= '<p style="color:#64748b;font-size:15px;line-height:1.6;">New arrivals will appear here automatically when you publish new content.</p>';
        }

        $out .= self::cta( 'Browse all new arrivals', home_url( '/' ), '#7c3aed' );
        return $out;
    }

    /* =========================================================
       5. TRENDING — with thumbnails
    ========================================================= */
    private static function trending() {
        $posts = self::get_popular_posts( 3, 7 );

        $out = self::hero(
            'Trending Now',
            'These are getting a lot of attention right now on {{site_name}}.',
            '#dc2626',
            '#ffffff',
            '#fecaca'
        );

        $i = 1;
        $palettes = [
            [ '#fef3c7', '#92400e', '#d97706' ],
            [ '#dbeafe', '#1e40af', '#2563eb' ],
            [ '#dcfce7', '#166534', '#16a34a' ],
        ];

        if ( $posts->have_posts() ) {
            while ( $posts->have_posts() ) {
                $posts->the_post();
                $pid = get_the_ID();
                $c = $palettes[ ( $i - 1 ) % 3 ];
                $img = self::get_image_url( $pid, 'medium' );

                $img_cell = $img
                    ? '<td width="90" style="vertical-align:top;padding-right:14px;"><a href="' . esc_url( get_permalink() ) . '" style="text-decoration:none;"><img src="' . esc_url( $img ) . '" alt="' . esc_attr( get_the_title() ) . '" width="90" style="display:block;width:90px;height:90px;object-fit:cover;border:0;border-radius:10px;" /></a></td>'
                    : '';

                $out .= '
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;margin-bottom:14px;">
  <tr><td style="background:' . $c[0] . ';padding:18px 20px;border-radius:12px;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
      <tr>
        <td width="44" style="vertical-align:top;padding-right:14px;"><div style="font-size:22px;font-weight:800;color:' . $c[2] . ';line-height:1;">#' . $i . '</div></td>' . $img_cell . '
        <td style="vertical-align:top;">
          <a href="' . esc_url( get_permalink() ) . '" style="font-size:16px;font-weight:700;color:' . $c[1] . ';text-decoration:none;display:block;margin-bottom:4px;line-height:1.4;">' . esc_html( get_the_title() ) . '</a>
          <span style="font-size:13px;color:' . $c[2] . ';line-height:1.4;">' . esc_html( wp_trim_words( get_the_excerpt(), 12, '…' ) ) . '</span>
        </td>
      </tr>
    </table>
  </td></tr>
</table>';
                $i++;
            }
            wp_reset_postdata();
        } else {
            $out .= '<p style="color:#64748b;font-size:15px;line-height:1.6;">Trending content will appear once your readers start engaging with recent posts.</p>';
        }

        return $out;
    }

    /* =========================================================
       6. MOST POPULAR
    ========================================================= */
    private static function popular() {
        $posts = self::get_popular_posts( 5, 365 );

        $out = self::hero(
            'Most Popular',
            'All-time favorites our readers keep coming back to on {{site_name}}.',
            '#0891b2',
            '#ffffff',
            '#cffafe'
        );

        $i = 0;
        $total = $posts->post_count;

        if ( $posts->have_posts() ) {
            while ( $posts->have_posts() ) {
                $posts->the_post();
                $i++;
                $out .= self::article_row( get_the_ID(), $i === $total );
            }
            wp_reset_postdata();
        } else {
            $out .= '<p style="color:#64748b;font-size:15px;line-height:1.6;">Your all-time popular posts will be listed here.</p>';
        }

        $out .= self::cta( 'Explore the full archive', home_url( '/' ), '#0891b2' );
        return $out;
    }

    /* =========================================================
       7. EDITOR'S PICKS — dark hero + image cards
    ========================================================= */
    private static function editors_picks() {
        $sticky = get_option( 'sticky_posts' );
        $args = [
            'post_type'      => 'post',
            'post_status'    => 'publish',
            'posts_per_page' => 3,
        ];
        if ( ! empty( $sticky ) ) {
            $args['post__in'] = $sticky;
        } else {
            $args['orderby'] = 'date';
            $args['order']   = 'DESC';
        }
        $posts = new WP_Query( $args );

        $out = '
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
  <tr><td style="background:#0f172a;padding:36px 32px;border-radius:14px;text-align:center;">
    <div style="font-size:11px;letter-spacing:0.22em;text-transform:uppercase;color:#93c5fd;font-weight:700;margin-bottom:12px;">Editor\'s Picks</div>
    <h2 style="margin:0;color:#ffffff;font-size:24px;font-weight:700;line-height:1.3;">Handpicked favorites</h2>
    <p style="margin:10px 0 0;color:#cbd5e1;font-size:15px;">Curated by our editorial team at {{site_name}}.</p>
  </td></tr>
</table>
' . self::spacer( 28 );

        $palettes = [ '#2563eb', '#16a34a', '#d97706' ];
        $i = 0;

        if ( $posts->have_posts() ) {
            while ( $posts->have_posts() ) {
                $posts->the_post();
                $pid = get_the_ID();
                $img = self::get_image_url( $pid, 'medium' );

                $img_cell = $img
                    ? '<td width="130" style="vertical-align:top;padding-right:18px;"><a href="' . esc_url( get_permalink() ) . '" style="text-decoration:none;"><img src="' . esc_url( $img ) . '" alt="' . esc_attr( get_the_title() ) . '" width="130" style="display:block;width:130px;height:130px;object-fit:cover;border:0;border-radius:10px;" /></a></td>'
                    : '';

                $out .= '
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;margin-bottom:16px;">
  <tr><td style="padding:22px;background:#f8fafc;border-radius:12px;border-left:4px solid ' . $palettes[ $i % 3 ] . ';">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
      <tr>' . $img_cell . '
        <td style="vertical-align:top;">
          <a href="' . esc_url( get_permalink() ) . '" style="color:#2563eb;font-weight:700;text-decoration:none;font-size:17px;display:block;margin-bottom:8px;line-height:1.4;">' . esc_html( get_the_title() ) . '</a>
          <span style="color:#64748b;font-size:14px;line-height:1.55;">' . esc_html( wp_trim_words( get_the_excerpt(), 20, '…' ) ) . '</span>
        </td>
      </tr>
    </table>
  </td></tr>
</table>';
                $i++;
            }
            wp_reset_postdata();
        } else {
            $out .= '<p style="color:#64748b;font-size:15px;line-height:1.6;">Feature posts by marking them as "Sticky" in the editor — they will appear here.</p>';
        }

        return $out;
    }

    /* =========================================================
       8. CATEGORY ROUNDUP
    ========================================================= */
    private static function category_roundup() {
        $cats = self::get_top_categories( 3 );

        $out = self::hero(
            'Category Roundup',
            'Latest updates from your favorite topics on {{site_name}}.',
            '#4338ca',
            '#ffffff',
            '#c7d2fe'
        );

        if ( $cats ) {
            foreach ( $cats as $cat ) {
                $posts = self::get_category_posts( $cat->slug, 2 );
                if ( $posts->have_posts() ) {
                    $out .= '
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;margin-top:8px;">
  <tr><td style="padding:14px 18px;background:#eef2ff;border-radius:10px;">
    <div style="font-size:13px;font-weight:700;color:#4338ca;letter-spacing:0.05em;text-transform:uppercase;">' . esc_html( $cat->name ) . ' · ' . (int) $cat->count . ' articles</div>
  </td></tr>
</table>' . self::spacer( 12 );

                    $i = 0;
                    $total = $posts->post_count;
                    while ( $posts->have_posts() ) {
                        $posts->the_post();
                        $i++;
                        $out .= self::article_row( get_the_ID(), $i === $total );
                    }
                    wp_reset_postdata();
                    $out .= self::spacer( 12 );
                }
            }
        } else {
            $out .= '<p style="color:#64748b;font-size:15px;line-height:1.6;">Once you organize content into categories, updates will appear here.</p>';
        }

        return $out;
    }

    /* =========================================================
       9. SEASONAL
    ========================================================= */
    private static function seasonal() {
        $posts = self::get_latest_posts( 3 );
        $season = self::current_season();
        $month = date( 'F' );

        $out = '
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
  <tr><td style="background:#f59e0b;padding:40px 32px;border-radius:16px;text-align:center;">
    <div style="font-size:11px;letter-spacing:0.22em;text-transform:uppercase;color:#fef3c7;font-weight:700;margin-bottom:10px;">' . esc_html( $month ) . ' Edition</div>
    <h1 style="margin:0 0 8px;font-size:28px;color:#ffffff;font-weight:800;line-height:1.25;">' . ucfirst( $season ) . ' Picks</h1>
    <p style="margin:0;color:#fef3c7;font-size:15px;line-height:1.5;">Fresh content for ' . $month . ' from {{site_name}}.</p>
  </td></tr>
</table>
' . self::spacer( 28 );

        if ( $posts->have_posts() ) {
            $i = 0;
            $total = $posts->post_count;
            while ( $posts->have_posts() ) {
                $posts->the_post();
                $i++;
                $out .= self::article_row( get_the_ID(), $i === $total );
            }
            wp_reset_postdata();
        } else {
            $out .= '<p style="color:#64748b;font-size:15px;line-height:1.6;">Seasonal content will appear here as you publish.</p>';
        }

        $out .= self::cta( 'Explore this season', home_url( '/' ), '#f59e0b' );
        return $out;
    }

    private static function current_season() {
        $m = (int) date( 'n' );
        if ( $m >= 3 && $m <= 5 ) return 'spring';
        if ( $m >= 6 && $m <= 8 ) return 'summer';
        if ( $m >= 9 && $m <= 11 ) return 'autumn';
        return 'winter';
    }

    /* =========================================================
       10. SPECIAL OFFERS
    ========================================================= */
    private static function special_offers() {
        $sale = self::get_sale_products( 1 );
        $product = null;
        $pid = 0;

        if ( $sale && $sale->have_posts() ) {
            $sale->the_post();
            $pid = get_the_ID();
            $product = wc_get_product( $pid );
            wp_reset_postdata();
        }

        $offer_title = 'SAVE 25% TODAY';
        $code = 'SAVE25';

        if ( $product ) {
            $regular = $product->get_regular_price();
            $sale_price = $product->get_sale_price();
            if ( $regular && $sale_price ) {
                $discount = round( ( ( $regular - $sale_price ) / $regular ) * 100 );
                $offer_title = 'SAVE ' . $discount . '% TODAY';
                $code = 'SAVE' . $discount;
            }
        }

        $out = '
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
  <tr><td style="background:#dc2626;padding:36px 32px;border-radius:14px;text-align:center;">
    <div style="font-size:11px;letter-spacing:0.22em;text-transform:uppercase;color:#fecaca;font-weight:700;margin-bottom:14px;">Limited Time Offer</div>
    <h1 style="margin:0 0 14px;font-size:38px;color:#ffffff;font-weight:800;line-height:1.1;">' . esc_html( $offer_title ) . '</h1>
    <p style="margin:0 0 4px;color:#fecaca;font-size:14px;">Use code</p>
    <div style="margin:8px auto 0;display:inline-block;background:#ffffff;color:#dc2626;padding:10px 22px;border-radius:6px;font-family:Menlo,Consolas,monospace;font-size:20px;font-weight:800;letter-spacing:0.08em;">' . esc_html( $code ) . '</div>
  </td></tr>
</table>
' . self::spacer( 24 );

        if ( $product ) {
            $img = self::image_block( $pid, 560, 260, 12 );
            $out .= '
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;background:#fef2f2;border-radius:12px;overflow:hidden;">
  ' . ( $img ? '<tr><td style="padding:0;">' . $img . '</td></tr>' : '' ) . '
  <tr><td style="padding:24px;">
    <h2 style="margin:0 0 8px;font-size:20px;font-weight:700;color:#0f172a;line-height:1.3;">' . esc_html( $product->get_name() ) . '</h2>
    <p style="margin:0 0 14px;color:#475569;font-size:15px;line-height:1.55;">' . esc_html( wp_trim_words( $product->get_short_description() ?: $product->get_description(), 25, '…' ) ) . '</p>
    <div style="font-size:20px;font-weight:700;color:#dc2626;">' . $product->get_price_html() . '</div>
  </td></tr>
</table>';
        } else {
            $out .= '<p style="margin:0;font-size:16px;color:#475569;line-height:1.6;">This offer is valid until the end of the month. Don\'t miss out on these savings.</p>';
        }

        $out .= self::cta( 'Shop the offer', $product ? get_permalink( $pid ) : home_url( '/' ), '#dc2626' );
        return $out;
    }

    /* =========================================================
       11. PRODUCT SPOTLIGHT
    ========================================================= */
    private static function product_spotlight() {
        $products = self::get_new_products( 1 );

        $out = self::hero(
            'Product Spotlight',
            'One product we think you\'ll love this week.',
            '#059669',
            '#ffffff',
            '#a7f3d0'
        );

        if ( $products && $products->have_posts() ) {
            $products->the_post();
            $pid = get_the_ID();
            $product = wc_get_product( $pid );
            $img = self::image_block( $pid, 560, 300, 12 );

            $out .= '
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;border:1px solid #e2e8f0;border-radius:12px;overflow:hidden;background:#ffffff;">
  ' . ( $img ? '<tr><td style="padding:0;">' . $img . '</td></tr>' : '' ) . '
  <tr><td style="padding:28px;">
    <div style="font-size:11px;font-weight:700;letter-spacing:0.15em;text-transform:uppercase;color:#059669;margin-bottom:8px;">Featured product</div>
    <h2 style="margin:0 0 10px;font-size:22px;font-weight:700;color:#0f172a;line-height:1.3;">' . esc_html( get_the_title() ) . '</h2>
    <p style="margin:0 0 16px;font-size:15px;color:#475569;line-height:1.6;">' . esc_html( wp_trim_words( get_the_excerpt(), 30, '…' ) ) . '</p>
    <div style="margin-bottom:20px;font-size:22px;font-weight:700;color:#0f172a;">' . ( $product ? $product->get_price_html() : '' ) . '</div>
    <a href="' . esc_url( get_permalink() ) . '" style="display:inline-block;background:#059669;color:#ffffff;text-decoration:none;padding:14px 28px;border-radius:8px;font-weight:600;font-size:15px;min-height:44px;box-sizing:border-box;">View details</a>
  </td></tr>
</table>';
            wp_reset_postdata();
        } else {
            $out .= '<p style="color:#64748b;font-size:15px;line-height:1.6;">' . ( class_exists( 'WooCommerce' ) ? 'Publish a product and it will appear here as the spotlight.' : 'Install WooCommerce to use this template with real products.' ) . '</p>';
        }

        return $out;
    }

    /* =========================================================
       12. PRODUCT COMPARISON
    ========================================================= */
    private static function product_comparison() {
        $products = self::get_best_selling_products( 2 );

        $out = self::hero(
            'Which one is right for you?',
            'A side-by-side comparison of two popular products.',
            '#0f172a',
            '#ffffff',
            '#94a3b8'
        );

        if ( $products && $products->post_count >= 2 ) {
            $a = $products->posts[0];
            $b = $products->posts[1];
            $pa = wc_get_product( $a->ID );
            $pb = wc_get_product( $b->ID );
            $img_a = self::image_block( $a->ID, 260, 180, 10 );
            $img_b = self::image_block( $b->ID, 260, 180, 10 );

            $out .= '
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
  <tr>
    <td width="50%" style="vertical-align:top;padding:0 8px 0 0;">
      <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;border:1px solid #e2e8f0;border-radius:12px;overflow:hidden;background:#ffffff;">
        ' . ( $img_a ? '<tr><td style="padding:0;">' . $img_a . '</td></tr>' : '' ) . '
        <tr><td style="padding:18px;">
          <h3 style="margin:0 0 8px;font-size:16px;font-weight:700;color:#0f172a;line-height:1.35;">' . esc_html( $a->post_title ) . '</h3>
          <div style="font-size:16px;font-weight:700;color:#2563eb;">' . $pa->get_price_html() . '</div>
        </td></tr>
      </table>
    </td>
    <td width="50%" style="vertical-align:top;padding:0 0 0 8px;">
      <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;border:1px solid #e2e8f0;border-radius:12px;overflow:hidden;background:#ffffff;">
        ' . ( $img_b ? '<tr><td style="padding:0;">' . $img_b . '</td></tr>' : '' ) . '
        <tr><td style="padding:18px;">
          <h3 style="margin:0 0 8px;font-size:16px;font-weight:700;color:#0f172a;line-height:1.35;">' . esc_html( $b->post_title ) . '</h3>
          <div style="font-size:16px;font-weight:700;color:#2563eb;">' . $pb->get_price_html() . '</div>
        </td></tr>
      </table>
    </td>
  </tr>
</table>
' . self::spacer( 20 );

            $out .= '
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;border:1px solid #e2e8f0;border-radius:12px;overflow:hidden;">
  <tr style="background:#0f172a;">
    <th style="padding:14px 16px;text-align:left;font-size:13px;color:#ffffff;font-weight:700;">Feature</th>
    <th style="padding:14px 16px;text-align:center;font-size:13px;color:#ffffff;font-weight:700;">Option A</th>
    <th style="padding:14px 16px;text-align:center;font-size:13px;color:#ffffff;font-weight:700;">Option B</th>
  </tr>
  <tr><td style="padding:12px 16px;border-top:1px solid #eef2f6;font-size:14px;color:#475569;">Price</td><td style="padding:12px 16px;text-align:center;border-top:1px solid #eef2f6;font-size:14px;">' . $pa->get_price_html() . '</td><td style="padding:12px 16px;text-align:center;border-top:1px solid #eef2f6;font-size:14px;">' . $pb->get_price_html() . '</td></tr>
  <tr><td style="padding:12px 16px;border-top:1px solid #eef2f6;font-size:14px;color:#475569;">Availability</td><td style="padding:12px 16px;text-align:center;border-top:1px solid #eef2f6;font-size:14px;">' . ( $pa->is_in_stock() ? '<span style="color:#16a34a;font-weight:600;">✓ In stock</span>' : '<span style="color:#dc2626;">Out</span>' ) . '</td><td style="padding:12px 16px;text-align:center;border-top:1px solid #eef2f6;font-size:14px;">' . ( $pb->is_in_stock() ? '<span style="color:#16a34a;font-weight:600;">✓ In stock</span>' : '<span style="color:#dc2626;">Out</span>' ) . '</td></tr>
  <tr><td style="padding:16px;border-top:1px solid #eef2f6;"></td>
      <td style="padding:16px;text-align:center;border-top:1px solid #eef2f6;"><a href="' . esc_url( get_permalink( $a->ID ) ) . '" style="display:inline-block;background:#2563eb;color:#ffffff;padding:10px 20px;border-radius:6px;text-decoration:none;font-weight:600;font-size:13px;">View</a></td>
      <td style="padding:16px;text-align:center;border-top:1px solid #eef2f6;"><a href="' . esc_url( get_permalink( $b->ID ) ) . '" style="display:inline-block;background:#2563eb;color:#ffffff;padding:10px 20px;border-radius:6px;text-decoration:none;font-weight:600;font-size:13px;">View</a></td></tr>
</table>';
            wp_reset_postdata();
        } else {
            $out .= '<p style="color:#64748b;font-size:15px;line-height:1.6;">' . ( class_exists( 'WooCommerce' ) ? 'Add more products to enable comparison templates.' : 'Install WooCommerce to compare real products.' ) . '</p>';
        }

        return $out;
    }

    /* =========================================================
       13. BACK IN STOCK
    ========================================================= */
    private static function back_in_stock() {
        $product = null;
        $pid = 0;
        if ( class_exists( 'WooCommerce' ) ) {
            $q = new WP_Query( [
                'post_type'      => 'product',
                'post_status'    => 'publish',
                'posts_per_page' => 1,
                'meta_query'     => [ [ 'key' => '_stock_status', 'value' => 'instock' ] ],
                'orderby'        => 'modified',
                'order'          => 'DESC',
            ] );
            if ( $q->have_posts() ) {
                $q->the_post();
                $pid = get_the_ID();
                $product = wc_get_product( $pid );
                wp_reset_postdata();
            }
        }

        $out = '
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
  <tr><td style="background:#dcfce7;border-left:4px solid #16a34a;padding:18px 22px;border-radius:10px;">
    <strong style="color:#166534;font-size:15px;line-height:1.4;">✅ Back in Stock</strong>
    <div style="color:#15803d;font-size:14px;margin-top:3px;line-height:1.4;">Order before it sells out again</div>
  </td></tr>
</table>
' . self::spacer( 24 );

        $out .= self::hero( 'It\'s available again', 'An item you may have been waiting for is back in stock.', '#16a34a', '#ffffff', '#bbf7d0' );

        if ( $product ) {
            $img = self::image_block( $pid, 560, 300, 12 );
            $out .= '
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;border:1px solid #e2e8f0;border-radius:12px;overflow:hidden;background:#ffffff;">
  ' . ( $img ? '<tr><td style="padding:0;">' . $img . '</td></tr>' : '' ) . '
  <tr><td style="padding:24px;">
    <h2 style="margin:0 0 10px;font-size:20px;font-weight:700;color:#0f172a;line-height:1.3;">' . esc_html( $product->get_name() ) . '</h2>
    <p style="margin:0 0 6px;color:#16a34a;font-weight:600;font-size:14px;">✓ In stock — limited quantity</p>
    <div style="margin:8px 0 20px;font-size:20px;font-weight:700;color:#0f172a;">' . $product->get_price_html() . '</div>
    <a href="' . esc_url( $product->get_permalink() ) . '" style="display:inline-block;background:#16a34a;color:#ffffff;padding:14px 28px;border-radius:8px;text-decoration:none;font-weight:600;font-size:15px;min-height:44px;box-sizing:border-box;">Order now</a>
  </td></tr>
</table>';
        } else {
            $out .= '<p style="color:#64748b;font-size:15px;line-height:1.6;">' . ( class_exists( 'WooCommerce' ) ? 'Restocked products will appear here.' : 'Install WooCommerce to use this template.' ) . '</p>';
        }

        return $out;
    }

    /* =========================================================
       14. PRICE DROP
    ========================================================= */
    private static function price_drop() {
        $sale = self::get_sale_products( 1 );
        $regular = $sale_price = null;
        $pid = 0;

        if ( $sale && $sale->have_posts() ) {
            $sale->the_post();
            $pid = get_the_ID();
            $p = wc_get_product( $pid );
            $regular = $p->get_regular_price();
            $sale_price = $p->get_sale_price();
            wp_reset_postdata();
        }

        $regular_display = $regular ? '$' . $regular : '$199';
        $sale_display = $sale_price ? '$' . $sale_price : '$149';

        $out = '
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
  <tr><td style="background:#2563eb;padding:40px 32px;border-radius:14px;text-align:center;">
    <div style="font-size:11px;letter-spacing:0.22em;text-transform:uppercase;color:#bfdbfe;font-weight:700;margin-bottom:16px;">Price Drop Alert</div>
    <div style="font-size:48px;font-weight:800;color:#ffffff;line-height:1;letter-spacing:-0.02em;">
      <span style="text-decoration:line-through;opacity:0.55;font-size:24px;margin-right:12px;">' . esc_html( $regular_display ) . '</span>' . esc_html( $sale_display ) . '
    </div>
    <div style="margin-top:12px;color:#dbeafe;font-size:14px;">Limited time offer</div>
  </td></tr>
</table>
' . self::spacer( 24 );

        if ( $pid ) {
            $img = self::image_block( $pid, 560, 260, 12 );
            $out .= '
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;background:#eff6ff;border-radius:12px;overflow:hidden;">
  ' . ( $img ? '<tr><td style="padding:0;">' . $img . '</td></tr>' : '' ) . '
  <tr><td style="padding:24px;">
    <h2 style="margin:0 0 8px;font-size:19px;font-weight:700;color:#0f172a;line-height:1.3;">' . esc_html( get_the_title( $pid ) ) . '</h2>
    <p style="margin:0;color:#1e40af;font-weight:600;font-size:14px;">Price reduced for a limited time.</p>
  </td></tr>
</table>';
        } else {
            $out .= '<p style="margin:0;font-size:16px;color:#475569;line-height:1.6;">Good news — the price just dropped on an item you were interested in. Grab it before it goes back up.</p>';
        }

        $out .= self::cta( 'Grab the deal', $pid ? get_permalink( $pid ) : home_url( '/' ) );
        return $out;
    }

    /* =========================================================
       15. EVENTS
    ========================================================= */
    private static function events() {
        $events = self::get_upcoming_events( 3 );

        $out = self::hero(
            'Upcoming Events',
            'Save the date — here\'s what\'s coming up at {{site_name}}.',
            '#7c3aed',
            '#ffffff',
            '#ddd6fe'
        );

        if ( $events->have_posts() ) {
            while ( $events->have_posts() ) {
                $events->the_post();
                $pid = get_the_ID();
                $start = get_post_meta( $pid, '_EventStartDate', true );
                $date_label = $start ? date( 'M j · g:i A', strtotime( $start ) ) : 'Date TBA';
                $img = self::get_image_url( $pid, 'medium' );

                $img_cell = $img
                    ? '<td width="110" style="vertical-align:top;padding-right:16px;"><a href="' . esc_url( get_permalink() ) . '" style="text-decoration:none;"><img src="' . esc_url( $img ) . '" alt="' . esc_attr( get_the_title() ) . '" width="110" style="display:block;width:110px;height:110px;object-fit:cover;border:0;border-radius:10px;" /></a></td>'
                    : '';

                $out .= '
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;margin-bottom:16px;">
  <tr><td style="background:#faf5ff;border-left:4px solid #7c3aed;padding:20px 22px;border-radius:10px;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
      <tr>' . $img_cell . '
        <td style="vertical-align:top;">
          <div style="color:#7c3aed;font-weight:700;font-size:12px;letter-spacing:0.08em;margin-bottom:6px;">' . esc_html( strtoupper( $date_label ) ) . '</div>
          <a href="' . esc_url( get_permalink() ) . '" style="font-size:17px;font-weight:700;color:#0f172a;text-decoration:none;display:block;margin-bottom:6px;line-height:1.35;">' . esc_html( get_the_title() ) . '</a>
          <p style="margin:0 0 10px;font-size:14px;color:#64748b;line-height:1.5;">' . esc_html( wp_trim_words( get_the_excerpt(), 18, '…' ) ) . '</p>
          <a href="' . esc_url( get_permalink() ) . '" style="font-size:14px;font-weight:600;color:#7c3aed;text-decoration:none;">Register &rarr;</a>
        </td>
      </tr>
    </table>
  </td></tr>
</table>';
            }
            wp_reset_postdata();
        } else {
            $out .= '<p style="color:#64748b;font-size:15px;line-height:1.6;">' . ( post_type_exists( 'tribe_events' ) ? 'No upcoming events scheduled. Add events to your calendar.' : 'Install The Events Calendar plugin to auto-populate this template.' ) . '</p>';
        }

        return $out;
    }

    /* =========================================================
       16. DEADLINES
    ========================================================= */
    private static function deadlines() {
        $future = new WP_Query( [
            'post_type'      => 'post',
            'post_status'    => 'future',
            'posts_per_page' => 3,
            'orderby'        => 'date',
            'order'          => 'ASC',
        ] );

        $out = '
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
  <tr><td style="background:#fef3c7;border-left:4px solid #f59e0b;padding:18px 22px;border-radius:10px;">
    <strong style="color:#92400e;font-size:15px;line-height:1.4;">⏰ Important dates</strong>
    <div style="color:#a16207;font-size:14px;margin-top:3px;line-height:1.4;">Mark your calendar</div>
  </td></tr>
</table>
' . self::spacer( 24 );

        $out .= self::hero( 'Upcoming Deadlines', 'Keep these on your radar.', '#f59e0b', '#ffffff', '#fef3c7' );

        if ( $future->have_posts() ) {
            $out .= '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">';
            $i = 0;
            $total = $future->post_count;
            while ( $future->have_posts() ) {
                $future->the_post();
                $i++;
                $is_last = $i === $total;
                $out .= '
  <tr><td style="padding:18px 22px;background:#fffbeb;' . ( $is_last ? '' : 'border-bottom:1px solid #fef3c7;' ) . 'font-size:15px;color:#1e293b;line-height:1.5;' . ( $i === 1 ? 'border-radius:10px 10px 0 0;' : '' ) . ( $is_last ? 'border-radius:0 0 10px 10px;' : '' ) . '">
    <div style="display:inline-block;background:#f59e0b;color:#ffffff;padding:4px 12px;border-radius:6px;font-size:12px;font-weight:700;margin-right:10px;">' . esc_html( strtoupper( get_the_date( 'M j', get_the_ID() ) ) ) . '</div>
    <strong>' . esc_html( get_the_title() ) . '</strong>
  </td></tr>';
            }
            $out .= '</table>';
            wp_reset_postdata();
        } else {
            $out .= '<p style="color:#64748b;font-size:15px;line-height:1.6;">Schedule posts in the future and they will appear here as important dates.</p>';
        }

        return $out;
    }

    /* =========================================================
       17. TIPS & TUTORIALS
    ========================================================= */
    private static function tips() {
        $tips_cat = get_category_by_slug( 'tutorials' ) ?: get_category_by_slug( 'tips' ) ?: get_category_by_slug( 'how-to' );
        $args = [
            'post_type'      => 'post',
            'post_status'    => 'publish',
            'posts_per_page' => 2,
        ];
        if ( $tips_cat ) {
            $args['cat'] = $tips_cat->term_id;
        } else {
            $args['orderby'] = 'comment_count';
            $args['order']   = 'DESC';
        }
        $posts = new WP_Query( $args );

        $out = self::hero(
            'Tips & Tutorials',
            'Useful how-tos to help you get more done.',
            '#7c3aed',
            '#ffffff',
            '#ddd6fe'
        );

        if ( $posts->have_posts() ) {
            while ( $posts->have_posts() ) {
                $posts->the_post();
                $pid = get_the_ID();
                $img = self::image_block( $pid, 560, 220, 12 );

                $out .= '
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;margin-bottom:18px;border:1px solid #e2e8f0;border-radius:12px;overflow:hidden;background:#ffffff;">
  ' . ( $img ? '<tr><td style="padding:0;">' . $img . '</td></tr>' : '' ) . '
  <tr><td style="padding:22px;">
    <div style="font-size:11px;font-weight:700;letter-spacing:0.15em;text-transform:uppercase;color:#7c3aed;margin-bottom:8px;">' . ( $tips_cat ? esc_html( strtoupper( $tips_cat->name ) ) : 'GUIDE' ) . '</div>
    <h3 style="margin:0 0 8px;font-size:18px;font-weight:700;color:#0f172a;line-height:1.35;">' . esc_html( get_the_title() ) . '</h3>
    <p style="margin:0 0 14px;font-size:14px;color:#475569;line-height:1.55;">' . esc_html( wp_trim_words( get_the_excerpt(), 20, '…' ) ) . '</p>
    <a href="' . esc_url( get_permalink() ) . '" style="font-size:14px;font-weight:600;color:#7c3aed;text-decoration:none;">Read tutorial &rarr;</a>
  </td></tr>
</table>';
            }
            wp_reset_postdata();
        } else {
            $out .= '<p style="color:#64748b;font-size:15px;line-height:1.6;">Create a "Tutorials" or "Tips" category and publish posts — they will auto-fill this template.</p>';
        }

        return $out;
    }

    /* =========================================================
       18. CASE STUDIES
    ========================================================= */
    private static function case_studies() {
        $cs_cat = get_category_by_slug( 'case-studies' ) ?: get_category_by_slug( 'success-stories' ) ?: get_category_by_slug( 'testimonials' );
        $args = [
            'post_type'      => 'post',
            'post_status'    => 'publish',
            'posts_per_page' => 2,
        ];
        if ( $cs_cat ) {
            $args['cat'] = $cs_cat->term_id;
        } else {
            $args['orderby'] = 'date';
            $args['order']   = 'DESC';
        }
        $posts = new WP_Query( $args );

        $out = self::hero(
            'Success Stories',
            'Real results from real people using {{site_name}}.',
            '#0e7490',
            '#ffffff',
            '#a5f3fc'
        );

        $colors = [ '#2563eb', '#16a34a' ];
        $i = 0;

        if ( $posts->have_posts() ) {
            while ( $posts->have_posts() ) {
                $posts->the_post();
                $pid = get_the_ID();
                $img = self::image_block( $pid, 560, 240, 12 );

                $out .= '
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;margin-bottom:24px;background:#f8fafc;border-radius:12px;overflow:hidden;border-left:4px solid ' . $colors[ $i % 2 ] . ';">
  ' . ( $img ? '<tr><td style="padding:0;">' . $img . '</td></tr>' : '' ) . '
  <tr><td style="padding:28px;">
    <div style="font-size:56px;color:' . $colors[ $i % 2 ] . ';line-height:0.6;font-family:Georgia,serif;">&ldquo;</div>
    <p style="margin:16px 0 20px;color:#1e293b;font-style:italic;font-size:16px;line-height:1.6;">' . esc_html( wp_trim_words( get_the_excerpt(), 30, '…' ) ) . '</p>
    <div style="font-size:14px;color:#64748b;line-height:1.4;"><strong style="color:#0f172a;">' . esc_html( get_the_title() ) . '</strong></div>
  </td></tr>
</table>';
                $i++;
            }
            wp_reset_postdata();
        } else {
            $out .= '<p style="color:#64748b;font-size:15px;line-height:1.6;">Publish posts in a "Case Studies" or "Testimonials" category and they will appear here.</p>';
        }

        $out .= self::cta( 'Read the full case studies', home_url( '/' ), '#0e7490' );
        return $out;
    }

    /* =========================================================
       19. QUIZ
    ========================================================= */
    private static function quiz() {
        $poll = get_category_by_slug( 'poll' ) ?: get_category_by_slug( 'quiz' );
        $args = [
            'post_type'      => 'post',
            'post_status'    => 'publish',
            'posts_per_page' => 1,
        ];
        if ( $poll ) {
            $args['cat'] = $poll->term_id;
        } else {
            $args['orderby'] = 'date';
            $args['order']   = 'DESC';
        }
        $q = new WP_Query( $args );

        $out = self::hero(
            'Quick Question for You',
            'We\'d love your opinion — it takes 10 seconds.',
            '#d97706',
            '#ffffff',
            '#fef3c7'
        );

        if ( $q->have_posts() ) {
            $q->the_post();
            $out .= '<p style="margin:0 0 20px;font-weight:600;color:#0f172a;font-size:17px;line-height:1.5;">' . esc_html( get_the_title() ) . '</p>';
            $out .= self::cta( 'Take the poll', get_permalink(), '#d97706' );
            wp_reset_postdata();
        } else {
            $out .= '
<p style="margin:0 0 18px;font-weight:600;color:#0f172a;font-size:17px;line-height:1.5;">Which best describes you?</p>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;margin-bottom:12px;">
  <tr><td><a href="#" style="display:block;padding:20px 22px;background:#fffbeb;border:2px solid #fcd34d;border-radius:10px;color:#92400e;text-decoration:none;font-weight:600;font-size:15px;line-height:1.4;">🅰 Option A — I\'m just getting started</a></td></tr>
</table>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;margin-bottom:12px;">
  <tr><td><a href="#" style="display:block;padding:20px 22px;background:#fffbeb;border:2px solid #fcd34d;border-radius:10px;color:#92400e;text-decoration:none;font-weight:600;font-size:15px;line-height:1.4;">🅱 Option B — I use it regularly</a></td></tr>
</table>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
  <tr><td><a href="#" style="display:block;padding:20px 22px;background:#fffbeb;border:2px solid #fcd34d;border-radius:10px;color:#92400e;text-decoration:none;font-weight:600;font-size:15px;line-height:1.4;">🅲 Option C — I\'m an advanced user</a></td></tr>
</table>';
        }

        return $out;
    }

    /* =========================================================
       20. PERSONALIZED
    ========================================================= */
    private static function personalized() {
        $posts = self::get_latest_posts( 3 );

        $out = self::hero(
            'Picked Just for You',
            'Based on what\'s popular on {{site_name}} right now, we think you\'ll like these.',
            '#7c3aed',
            '#ffffff',
            '#ddd6fe'
        );

        if ( $posts->have_posts() ) {
            $i = 0;
            while ( $posts->have_posts() ) {
                $posts->the_post();
                $i++;
                $pid = get_the_ID();
                $img = self::image_block( $pid, 560, 200, 12 );

                $out .= '
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;margin-bottom:16px;background:#f8fafc;border-radius:12px;overflow:hidden;">
  ' . ( $img ? '<tr><td style="padding:0;">' . $img . '</td></tr>' : '' ) . '
  <tr><td style="padding:22px;">
    <div style="font-size:11px;font-weight:700;letter-spacing:0.15em;text-transform:uppercase;color:#7c3aed;margin-bottom:8px;">Recommendation #' . $i . '</div>
    <a href="' . esc_url( get_permalink() ) . '" style="font-weight:700;color:#2563eb;text-decoration:none;font-size:18px;display:block;margin-bottom:8px;line-height:1.4;">' . esc_html( get_the_title() ) . '</a>
    <p style="margin:0;color:#64748b;font-size:14px;line-height:1.5;">' . esc_html( wp_trim_words( get_the_excerpt(), 18, '…' ) ) . '</p>
  </td></tr>
</table>';
            }
            wp_reset_postdata();
        } else {
            $out .= '<p style="color:#64748b;font-size:15px;line-height:1.6;">Recommendations will appear as your site publishes content.</p>';
        }

        return $out;
    }

    /* =========================================================
       21. WELCOME
    ========================================================= */
    private static function welcome() {
        return '
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
  <tr><td style="background:#2563eb;padding:44px 32px;border-radius:16px;text-align:center;">
    <div style="font-size:56px;line-height:1;margin-bottom:12px;">👋</div>
    <h1 style="margin:0 0 10px;font-size:30px;font-weight:700;color:#ffffff;line-height:1.25;letter-spacing:-0.02em;">Welcome to {{site_name}}</h1>
    <p style="margin:0;font-size:16px;color:#dbeafe;line-height:1.5;">You\'re officially part of the community.</p>
  </td></tr>
</table>
' . self::spacer( 28 ) . '
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
  <tr><td style="padding:26px;background:#eff6ff;border-left:4px solid #2563eb;border-radius:12px;">
    <h2 style="margin:0 0 14px;font-size:17px;font-weight:700;color:#1e40af;">Here\'s what you can expect:</h2>
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
      <tr><td style="padding:10px 0;font-size:15px;color:#1e293b;line-height:1.55;border-bottom:1px solid #dbeafe;"><strong style="color:#2563eb;">✦</strong> &nbsp;Curated content from our site</td></tr>
      <tr><td style="padding:10px 0;font-size:15px;color:#1e293b;line-height:1.55;border-bottom:1px solid #dbeafe;"><strong style="color:#2563eb;">✦</strong> &nbsp;Exclusive tips and resources</td></tr>
      <tr><td style="padding:10px 0;font-size:15px;color:#1e293b;line-height:1.55;"><strong style="color:#2563eb;">✦</strong> &nbsp;Early access to new content</td></tr>
    </table>
  </td></tr>
</table>'
            . self::cta( 'Start exploring', '{{site_url}}' );
    }

    /* =========================================================
       22. RE-ENGAGEMENT
    ========================================================= */
    private static function reengagement() {
        $posts = self::get_popular_posts( 3, 90 );

        $out = self::hero(
            'We miss you',
            'It\'s been a while since we last saw you at {{site_name}}. Here\'s what you may have missed.',
            '#0e7490',
            '#ffffff',
            '#a5f3fc'
        );

        if ( $posts->have_posts() ) {
            $i = 0;
            $total = $posts->post_count;
            while ( $posts->have_posts() ) {
                $posts->the_post();
                $i++;
                $out .= self::article_row( get_the_ID(), $i === $total );
            }
            wp_reset_postdata();
        } else {
            $out .= '<p style="color:#64748b;font-size:15px;line-height:1.6;">Catch up on our latest and most popular content.</p>';
        }

        $out .= self::cta( 'Come back and catch up', home_url( '/' ), '#0e7490' );
        $out .= '
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;margin-top:24px;">
  <tr><td style="background:#f1f5f9;padding:16px 20px;border-radius:10px;text-align:center;color:#64748b;font-size:12px;line-height:1.5;">
    Not interested anymore? <a href="{{unsubscribe_url}}" style="color:#64748b;text-decoration:underline;">Unsubscribe</a> — no hard feelings.
  </td></tr>
</table>';
        return $out;
    }

    /* =========================================================
       23. ABANDONED CART
    ========================================================= */
    private static function abandoned_cart() {
        $product = null;
        $pid = 0;
        if ( class_exists( 'WooCommerce' ) ) {
            $q = new WP_Query( [
                'post_type'      => 'product',
                'post_status'    => 'publish',
                'posts_per_page' => 1,
                'meta_key'       => 'total_sales',
                'orderby'        => 'meta_value_num',
                'order'          => 'DESC',
            ] );
            if ( $q->have_posts() ) {
                $q->the_post();
                $pid = get_the_ID();
                $product = wc_get_product( $pid );
                wp_reset_postdata();
            }
        }

        $out = self::hero(
            'You left something behind',
            'We saved your cart for you. Complete your order before items go out of stock.',
            '#dc2626',
            '#ffffff',
            '#fecaca'
        );

        if ( $product ) {
            $img = self::image_block( $pid, 560, 260, 12 );
            $out .= '
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;border:1px solid #fecaca;border-radius:12px;overflow:hidden;background:#fef2f2;">
  ' . ( $img ? '<tr><td style="padding:0;">' . $img . '</td></tr>' : '' ) . '
  <tr><td style="padding:24px;">
    <h2 style="margin:0 0 10px;font-size:20px;font-weight:700;color:#0f172a;line-height:1.3;">' . esc_html( $product->get_name() ) . '</h2>
    <p style="margin:0 0 6px;color:#64748b;font-size:14px;line-height:1.4;">Quantity: 1</p>
    <div style="margin-top:10px;font-size:20px;font-weight:700;color:#0f172a;">' . $product->get_price_html() . '</div>
  </td></tr>
</table>';
        } else {
            $out .= '<p style="color:#64748b;font-size:15px;line-height:1.6;">' . ( class_exists( 'WooCommerce' ) ? 'Your cart items will appear here.' : 'Install WooCommerce to use cart templates.' ) . '</p>';
        }

        $out .= '
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;margin-top:16px;">
  <tr><td style="padding:14px 18px;background:#fef3c7;border-radius:8px;font-size:14px;color:#92400e;line-height:1.5;">
    <strong>Note:</strong> Items in your cart are not reserved and may sell out.
  </td></tr>
</table>';

        $out .= self::cta( 'Complete your order', home_url( '/cart/' ), '#dc2626' );
        return $out;
    }

    /* =========================================================
       24. MONTHLY RECAP
    ========================================================= */
    private static function monthly_recap() {
        $top = self::get_popular_posts( 3, 30 );
        $latest = self::get_latest_posts( 2 );
        $month = date( 'F Y' );

        $out = self::hero(
            'Monthly Recap — ' . $month,
            'A quick look at what happened this month at {{site_name}}.',
            '#0891b2',
            '#ffffff',
            '#cffafe'
        );

        if ( $top->have_posts() ) {
            $out .= '
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;margin-bottom:16px;">
  <tr><td style="padding:24px;background:#ecfeff;border-left:4px solid #0891b2;border-radius:12px;">
    <div style="font-size:11px;letter-spacing:0.18em;text-transform:uppercase;color:#0891b2;font-weight:700;margin-bottom:14px;">Most Read This Month</div>
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">';

            $i = 1;
            while ( $top->have_posts() ) {
                $top->the_post();
                $out .= '
      <tr><td style="padding:10px 0;font-size:15px;color:#1e293b;line-height:1.55;' . ( $i < 3 ? 'border-bottom:1px solid #cffafe;' : '' ) . '">
        <span style="display:inline-block;background:#0891b2;color:#ffffff;width:24px;height:24px;line-height:24px;text-align:center;border-radius:6px;font-size:12px;font-weight:700;margin-right:12px;">' . $i . '</span>
        <a href="' . esc_url( get_permalink() ) . '" style="color:#0891b2;text-decoration:none;font-weight:600;">' . esc_html( get_the_title() ) . '</a>
      </td></tr>';
                $i++;
            }
            $out .= '
    </table>
  </td></tr>
</table>';
            wp_reset_postdata();
        }

        if ( $latest->have_posts() ) {
            $out .= '
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
  <tr><td style="padding:24px;background:#f0fdfa;border-left:4px solid #14b8a6;border-radius:12px;">
    <div style="font-size:11px;letter-spacing:0.18em;text-transform:uppercase;color:#14b8a6;font-weight:700;margin-bottom:10px;">Fresh Content</div>
    <p style="margin:0;color:#1e293b;font-size:15px;line-height:1.6;">New this month: ';
            $titles = [];
            while ( $latest->have_posts() ) {
                $latest->the_post();
                $titles[] = '<a href="' . esc_url( get_permalink() ) . '" style="color:#14b8a6;text-decoration:none;font-weight:600;">' . esc_html( get_the_title() ) . '</a>';
            }
            $out .= implode( ', ', $titles ) . '.</p>
  </td></tr>
</table>';
            wp_reset_postdata();
        }

        return $out;
    }
}

?>