<?php
/**
 * Plugin Name: DameMiManga SEO & Sitemap
 * Plugin URI: https://damemimanga.cl
 * Description: Optimización SEO personalizada, Sitemaps XML y Schema estructurado para la tienda de mangas. Reemplazo completo para RankMath.
 * Version: 1.0.0
 * Author: DameMiManga
 * Text Domain: dmm-seo
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

define( 'DMM_SEO_VERSION', '1.0.0' );
define( 'DMM_SEO_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'DMM_SEO_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Incluir las clases
require_once DMM_SEO_PLUGIN_DIR . 'includes/admin/class-dmm-admin.php';
require_once DMM_SEO_PLUGIN_DIR . 'includes/class-dmm-sitemap.php';
require_once DMM_SEO_PLUGIN_DIR . 'includes/class-dmm-schema.php';
require_once DMM_SEO_PLUGIN_DIR . 'includes/class-dmm-seo-tags.php';

// Inicializar
function dmm_seo_init() {
    new DMM_Admin();
    new DMM_Sitemap();
    new DMM_Schema();
    new DMM_SEO_Tags();
}
add_action( 'plugins_loaded', 'dmm_seo_init' );

// Activación del plugin
register_activation_hook( __FILE__, 'dmm_seo_activate' );
function dmm_seo_activate() {
    DMM_Sitemap::add_rewrite_rules();
    flush_rewrite_rules();
}

// Desactivación del plugin
register_deactivation_hook( __FILE__, 'dmm_seo_deactivate' );
function dmm_seo_deactivate() {
    flush_rewrite_rules();
}
