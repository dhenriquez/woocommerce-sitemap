<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class DMM_Admin {

    public function __construct() {
        add_action( 'admin_menu', array( $this, 'add_plugin_page' ) );
        add_action( 'admin_init', array( $this, 'page_init' ) );
    }

    public function add_plugin_page() {
        add_options_page(
            'DameMiManga SEO', 
            'DameMiManga SEO', 
            'manage_options', 
            'dmm-seo', 
            array( $this, 'create_admin_page' )
        );
    }

    public function create_admin_page() {
        ?>
        <div class="wrap">
            <h1>Configuración SEO DameMiManga</h1>
            <form method="post" action="options.php">
            <?php
                settings_fields( 'dmm_seo_option_group' );
                do_settings_sections( 'dmm-seo-setting-admin' );
                submit_button();
            ?>
            </form>
            <hr>
            <h2>Herramientas Adicionales</h2>
            <form method="post" action="">
                <?php wp_nonce_field( 'dmm_flush_sitemap_nonce', 'dmm_flush_nonce' ); ?>
                <input type="submit" name="dmm_flush_sitemaps" class="button button-secondary" value="Vaciar Caché de Sitemaps y Regenerar Enlaces">
            </form>
            <?php
            if ( isset( $_POST['dmm_flush_sitemaps'] ) && check_admin_referer( 'dmm_flush_sitemap_nonce', 'dmm_flush_nonce' ) ) {
                DMM_Sitemap::add_rewrite_rules();
                flush_rewrite_rules();
                DMM_Sitemap::clear_sitemap_cache();
                echo '<div class="notice notice-success is-dismissible"><p>Reglas de reescritura regeneradas y caché del sitemap purgada.</p></div>';
            }
            ?>
        </div>
        <?php
    }

    public function page_init() {
        register_setting( 'dmm_seo_option_group', 'dmm_seo_options' );

        add_settings_section(
            'dmm_seo_setting_section',
            'Ajustes Generales',
            array( $this, 'section_info' ),
            'dmm-seo-setting-admin'
        );

        add_settings_field(
            'enable_sitemaps', 
            'Habilitar Sitemaps', 
            array( $this, 'enable_sitemaps_callback' ), 
            'dmm-seo-setting-admin', 
            'dmm_seo_setting_section'
        );

        add_settings_field(
            'enable_schema', 
            'Habilitar Schema de Mangas', 
            array( $this, 'enable_schema_callback' ), 
            'dmm-seo-setting-admin', 
            'dmm_seo_setting_section'
        );
    }

    public function section_info() {
        echo 'Activa o desactiva las funcionalidades principales del plugin.';
    }

    public function enable_sitemaps_callback() {
        $options = get_option( 'dmm_seo_options' );
        $checked = !isset( $options['enable_sitemaps'] ) || $options['enable_sitemaps'] == '1' ? 'checked' : ''; // Default to true
        echo '<input type="checkbox" name="dmm_seo_options[enable_sitemaps]" value="1" ' . $checked . '/>';
    }

    public function enable_schema_callback() {
        $options = get_option( 'dmm_seo_options' );
        $checked = !isset( $options['enable_schema'] ) || $options['enable_schema'] == '1' ? 'checked' : ''; // Default to true
        echo '<input type="checkbox" name="dmm_seo_options[enable_schema]" value="1" ' . $checked . '/>';
    }
}
