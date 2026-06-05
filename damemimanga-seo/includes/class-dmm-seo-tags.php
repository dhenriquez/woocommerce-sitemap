<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class DMM_SEO_Tags {

    public function __construct() {
        // Canonicals
        remove_action( 'wp_head', 'rel_canonical' );
        add_action( 'wp_head', array( $this, 'inject_canonical' ) );

        // Robots.txt
        add_filter( 'robots_txt', array( $this, 'modify_robots_txt' ), 10, 2 );
    }

    public function inject_canonical() {
        if ( is_singular() ) {
            echo '<link rel="canonical" href="' . esc_url( get_permalink() ) . '" />' . "\n";
        } elseif ( is_front_page() ) {
            echo '<link rel="canonical" href="' . esc_url( home_url( '/' ) ) . '" />' . "\n";
        } elseif ( is_product_category() ) {
            global $wp_query;
            $cat = $wp_query->get_queried_object();
            if ( $cat ) {
                echo '<link rel="canonical" href="' . esc_url( get_term_link( $cat ) ) . '" />' . "\n";
            }
        }
    }

    public function modify_robots_txt( $output, $public ) {
        $custom_rules = "User-agent: *\n";
        $custom_rules .= "Disallow: /wp-admin/\n";
        $custom_rules .= "Allow: /wp-admin/admin-ajax.php\n";
        $custom_rules .= "Disallow: /wp-content/uploads/wc-logs/\n";
        $custom_rules .= "Disallow: /*?add-to-cart=*\n";
        $custom_rules .= "Disallow: /*?vaciar-carrito=*\n";
        $custom_rules .= "\nSitemap: " . home_url( '/sitemap_index.xml' ) . "\n";

        return $custom_rules;
    }
}
