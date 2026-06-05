<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class DMM_Sitemap {

    public function __construct() {
        $options = get_option( 'dmm_seo_options' );
        if ( isset( $options['enable_sitemaps'] ) && $options['enable_sitemaps'] !== '1' ) {
            return;
        }

        add_filter( 'query_vars', array( $this, 'add_query_vars' ) );
        add_action( 'template_redirect', array( $this, 'render_sitemap' ) );
        
        // Hooks para limpiar la caché
        add_action( 'save_post', array( __CLASS__, 'clear_sitemap_cache' ) );
        add_action( 'delete_post', array( __CLASS__, 'clear_sitemap_cache' ) );
    }

    public static function add_rewrite_rules() {
        add_rewrite_rule( 'sitemap_index\.xml$', 'index.php?dmm_sitemap=index', 'top' );
        add_rewrite_rule( 'sitemap-([^/]+)\.xml$', 'index.php?dmm_sitemap=$matches[1]', 'top' );
    }

    public function add_query_vars( $vars ) {
        $vars[] = 'dmm_sitemap';
        return $vars;
    }

    public static function clear_sitemap_cache() {
        global $wpdb;
        $wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_dmm_sitemap_%'" );
        $wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_dmm_sitemap_%'" );
    }

    public function render_sitemap() {
        $sitemap_type = get_query_var( 'dmm_sitemap' );
        if ( empty( $sitemap_type ) ) {
            return;
        }

        header( 'Content-Type: application/xml; charset=utf-8' );
        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";

        $cache_key = 'dmm_sitemap_' . $sitemap_type;
        $cached_xml = get_transient( $cache_key );

        if ( false !== $cached_xml ) {
            echo $cached_xml;
            exit;
        }

        ob_start();

        if ( 'index' === $sitemap_type ) {
            $this->render_sitemap_index();
        } else {
            $this->render_sitemap_urlset( $sitemap_type );
        }

        $xml = ob_get_clean();
        set_transient( $cache_key, $xml, 12 * HOUR_IN_SECONDS );
        
        echo $xml;
        exit;
    }

    private function render_sitemap_index() {
        $base_url = home_url();
        echo '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
        
        // Productos
        echo '<sitemap><loc>' . esc_url( $base_url . '/sitemap-products-1.xml' ) . '</loc></sitemap>';
        // Páginas
        echo '<sitemap><loc>' . esc_url( $base_url . '/sitemap-pages-1.xml' ) . '</loc></sitemap>';
        // Posts
        echo '<sitemap><loc>' . esc_url( $base_url . '/sitemap-posts-1.xml' ) . '</loc></sitemap>';
        // Categorías de producto
        echo '<sitemap><loc>' . esc_url( $base_url . '/sitemap-product_cat-1.xml' ) . '</loc></sitemap>';
        
        echo '</sitemapindex>';
    }

    private function render_sitemap_urlset( $type ) {
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">';

        // Parse type (e.g. products-1)
        if ( preg_match( '/^([a-z_]+)-([0-9]+)$/', $type, $matches ) ) {
            $post_type_or_tax = $matches[1];
            $page = intval( $matches[2] );
            $posts_per_page = 5000;
            $offset = ( $page - 1 ) * $posts_per_page;

            if ( in_array( $post_type_or_tax, array( 'products', 'pages', 'posts' ) ) ) {
                $actual_post_type = 'post';
                if ( 'products' === $post_type_or_tax ) $actual_post_type = 'product';
                if ( 'pages' === $post_type_or_tax ) $actual_post_type = 'page';

                $args = array(
                    'post_type'      => $actual_post_type,
                    'post_status'    => 'publish',
                    'posts_per_page' => $posts_per_page,
                    'offset'         => $offset,
                    'fields'         => 'ids',
                    'orderby'        => 'ID',
                    'order'          => 'DESC'
                );
                
                $query = new WP_Query( $args );
                foreach ( $query->posts as $post_id ) {
                    $url = get_permalink( $post_id );
                    $modified = get_post_modified_time( 'Y-m-d\TH:i:s+00:00', false, $post_id );
                    
                    echo '<url>';
                    echo '<loc>' . esc_url( $url ) . '</loc>';
                    echo '<lastmod>' . esc_html( $modified ) . '</lastmod>';
                    
                    if ( 'product' === $actual_post_type && has_post_thumbnail( $post_id ) ) {
                        $image_url = get_the_post_thumbnail_url( $post_id, 'full' );
                        if ( $image_url ) {
                            echo '<image:image><image:loc>' . esc_url( $image_url ) . '</image:loc></image:image>';
                        }
                    }
                    echo '</url>';
                }
            } elseif ( 'product_cat' === $post_type_or_tax ) {
                $terms = get_terms( array(
                    'taxonomy'   => 'product_cat',
                    'hide_empty' => true,
                    'number'     => $posts_per_page,
                    'offset'     => $offset
                ) );
                if ( ! is_wp_error( $terms ) ) {
                    foreach ( $terms as $term ) {
                        echo '<url>';
                        echo '<loc>' . esc_url( get_term_link( $term ) ) . '</loc>';
                        echo '</url>';
                    }
                }
            }
        }
        
        echo '</urlset>';
    }
}
