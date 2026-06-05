<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class DMM_Schema {

    public function __construct() {
        $options = get_option( 'dmm_seo_options' );
        if ( isset( $options['enable_schema'] ) && $options['enable_schema'] !== '1' ) {
            return;
        }

        // Remover schemas nativos de WooCommerce
        add_filter( 'woocommerce_structured_data_product', '__return_empty_array' );
        
        add_action( 'wp_head', array( $this, 'inject_schema' ) );
    }

    public function inject_schema() {
        if ( is_front_page() ) {
            $this->inject_bookstore_schema();
        } elseif ( is_product() ) {
            $this->inject_comic_product_schema();
        }
    }

    private function inject_bookstore_schema() {
        $schema = array(
            '@context' => 'https://schema.org',
            '@type'    => array( 'BookStore', 'Organization' ),
            '@id'      => home_url() . '/#organization',
            'name'     => get_bloginfo( 'name' ),
            'url'      => home_url(),
        );
        echo '<script type="application/ld+json">' . wp_json_encode( $schema ) . '</script>' . "\n";
    }

    private function inject_comic_product_schema() {
        global $product;
        if ( ! is_a( $product, 'WC_Product' ) ) {
            return;
        }

        $image = wp_get_attachment_image_url( $product->get_image_id(), 'full' );
        
        // Obtener ISBN del SKU
        $isbn = $product->get_sku();

        // Obtener atributos globales (pa_editorial, pa_autor)
        $editorial = $product->get_attribute( 'pa_editorial' );
        $autor = $product->get_attribute( 'pa_autor' );

        $schema = array(
            '@context'    => 'https://schema.org',
            '@type'       => array( 'Product', 'ComicIssue' ),
            'name'        => $product->get_name(),
            'description' => wp_strip_all_tags( $product->get_short_description() ? $product->get_short_description() : $product->get_description() ),
            'sku'         => $isbn,
            'isbn'        => $isbn,
            'bookFormat'  => 'GraphicNovel',
        );

        if ( $image ) {
            $schema['image'] = $image;
        }

        if ( ! empty( $editorial ) ) {
            $schema['publisher'] = array(
                '@type' => 'Organization',
                'name'  => $editorial
            );
        }

        if ( ! empty( $autor ) ) {
            $schema['author'] = array(
                '@type' => 'Person',
                'name'  => $autor
            );
        }

        $schema['offers'] = array(
            '@type'         => 'Offer',
            'price'         => $product->get_price(),
            'priceCurrency' => 'CLP',
            'availability'  => $product->is_in_stock() ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
            'url'           => get_permalink( $product->get_id() ),
        );

        echo '<script type="application/ld+json">' . wp_json_encode( $schema ) . '</script>' . "\n";
    }
}
