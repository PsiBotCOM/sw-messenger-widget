<?php
/**
 * Plugin Name: ObriiFlow Social Widget
 * Plugin URI:  https://obriiflow.com/plugin/
 * Description: Floating social messenger widget with carousel, bubble, and full admin panel.
 * Version:     1.0.1
 * Author:      obriiflow.com
 * Author URI:  https://obriiflow.com
 * License:     GPL-2.0-or-later
 * Text Domain: obriiflow-social-widget
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'SW_VERSION',         '1.0.1' );
define( 'SW_DB_VERSION',      '1.0.1' );
define( 'SW_DIR',             plugin_dir_path( __FILE__ ) );
define( 'SW_URL',             plugin_dir_url( __FILE__ ) );
define( 'SW_OPT_GENERAL',    'sw_general_settings' );
define( 'SW_OPT_MESSENGERS', 'sw_messengers' );

if ( is_admin() ) {
    require_once SW_DIR . 'admin/dashboard.php';
    require_once SW_DIR . 'admin/settings.php';
    require_once SW_DIR . 'admin/messengers.php';
    add_action( 'admin_menu', 'sw_register_menu' );
}

function sw_register_menu() {
    add_menu_page( sw_t( 'admin.menu_title' ), sw_t( 'admin.menu_title' ), 'manage_options', 'obriiflow-social-widget', 'sw_page_dashboard', 'dashicons-share', 80 );
    add_submenu_page( 'obriiflow-social-widget', sw_t( 'admin.dashboard' ),  sw_t( 'admin.dashboard' ),  'manage_options', 'obriiflow-social-widget',           'sw_page_dashboard' );
    add_submenu_page( 'obriiflow-social-widget', sw_t( 'admin.messengers' ), sw_t( 'admin.messengers' ), 'manage_options', 'obriiflow-social-widget-messengers', 'sw_page_messengers' );
    add_submenu_page( 'obriiflow-social-widget', sw_t( 'admin.settings' ),   sw_t( 'admin.settings' ),   'manage_options', 'obriiflow-social-widget-settings',   'sw_page_settings' );
}

/* ── AJAX Tracking ── */
add_action( 'wp_ajax_sw_track',        'sw_ajax_track' );
add_action( 'wp_ajax_nopriv_sw_track', 'sw_ajax_track' );

function sw_ajax_track() {
    if ( ! check_ajax_referer( 'sw_track_nonce', 'nonce', false ) ) {
        wp_die( '0' );
    }

    global $wpdb;
    $table = $wpdb->prefix . 'sw_stats';

    if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) !== $table ) {
        wp_die( '0' );
    }

    $type      = in_array( $_POST['type'] ?? '', [ 'view', 'click' ], true ) ? $_POST['type'] : '';
    $messenger = sanitize_key( $_POST['messenger'] ?? '' );

    if ( ! $type ) wp_die( '0' );

    $wpdb->insert(
        $table,
        [
            'type'       => $type,
            'messenger'  => ( $type === 'click' && $messenger ) ? $messenger : null,
            'created_at' => current_time( 'mysql' ),
        ],
        [ '%s', '%s', '%s' ]
    );

    wp_die( '1' );
}

/* ── Frontend ── */
add_action( 'wp_enqueue_scripts', 'sw_enqueue' );

// Try all possible hooks — Flatsome uses wp_footer but some themes skip it
add_action( 'wp_footer',    'sw_render_widget', 100 );
add_action( 'wp_body_open', 'sw_render_widget', 100 );
add_action( 'shutdown',     'sw_render_via_ob', 0 );

function sw_render_via_ob() {
    if ( ! defined( 'SW_RENDERED' ) && ! is_admin() && ! wp_doing_ajax() ) {
        // Safety no-op placeholder
    }
}

$GLOBALS['sw_rendered'] = false;

function sw_render_widget() {
    if ( ! empty( $GLOBALS['sw_rendered'] ) ) return;

    $g = sw_get_general();
    if ( empty( $g['enabled'] ) ) return;
    $messengers = sw_get_active_messengers();
    if ( empty( $messengers ) ) return;

    $GLOBALS['sw_rendered'] = true;

    $position      = $g['position'] ?? 'right';
    $offset_side   = intval( $g['offset_side'] ?? 20 );
    $offset_bottom = intval( $g['offset_bottom'] ?? 20 );
    $bubble_text   = esc_html( $g['bubble_text'] ?: sw_t( 'frontend.bubble_default' ) );
    $bubble_font_size = max( 10, min( 40, intval( $g['bubble_font_size'] ?? 15 ) ) );
    $bubble_on     = ! empty( $g['bubble_enabled'] );
    $show_labels   = ! empty( $g['show_labels'] );
    $side_prop     = ( $position === 'left' ) ? 'left' : 'right';
    ?>
    <div id="sw-widget" data-position="<?php echo esc_attr( $position ); ?>"
         style="position:fixed;<?php echo esc_attr( $side_prop ); ?>:<?php echo $offset_side; ?>px;bottom:<?php echo $offset_bottom; ?>px;z-index:99999;display:flex;flex-direction:column;align-items:<?php echo $position === 'left' ? 'flex-start' : 'flex-end'; ?>;gap:10px;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;--sw-bubble-font-size:<?php echo $bubble_font_size; ?>px;">

        <div id="sw-list" class="sw-list<?php echo $show_labels ? '' : ' sw-no-labels'; ?>" aria-hidden="true">
            <?php foreach ( $messengers as $m ) : ?>
            <a href="<?php echo esc_url( $m['url'] ); ?>" class="sw-item"
               data-messenger="<?php echo esc_attr( $m['key'] ?? '' ); ?>"
               target="_blank" rel="noopener noreferrer"
               aria-label="<?php echo esc_attr( $m['label'] ); ?>">
                <span class="sw-item-icon"><?php echo sw_get_messenger_icon_html( $m, 'sw-icon-img', '' ); ?></span>
                <?php if ( $show_labels ) : ?><span class="sw-item-label"><?php echo esc_html( $m['label'] ); ?></span><?php endif; ?>
            </a>
            <?php endforeach; ?>
        </div>

        <div class="sw-launcher">
            <?php if ( $bubble_on ) : ?>
            <div id="sw-bubble" class="sw-bubble" style="display:none;">
                <span><?php echo esc_html( $g['bubble_text'] ?: sw_t( 'frontend.bubble_default' ) ); ?></span>
            </div>
            <?php endif; ?>

            <button id="sw-toggle" class="sw-toggle" aria-label="Open messenger list" aria-expanded="false">
                <span id="sw-carousel" class="sw-carousel">
                    <?php foreach ( $messengers as $idx => $m ) : ?>
                    <span class="sw-slide<?php echo $idx === 0 ? ' active' : ''; ?>">
                        <?php echo sw_get_messenger_icon_html( $m, 'sw-icon-img', '' ); ?>
                    </span>
                    <?php endforeach; ?>
                </span>
                <span id="sw-close-icon" class="sw-close-icon">&#x2715;</span>
            </button>
        </div>
    </div>
    <?php
}

function sw_enqueue() {
    $g = sw_get_general();
    if ( empty( $g['enabled'] ) ) return;
    $messengers = sw_get_active_messengers();
    if ( empty( $messengers ) ) return;

    $css_version = file_exists( SW_DIR . 'assets/widget.css' ) ? filemtime( SW_DIR . 'assets/widget.css' ) : SW_VERSION;
    $js_version  = file_exists( SW_DIR . 'assets/widget.js' ) ? filemtime( SW_DIR . 'assets/widget.js' ) : SW_VERSION;

    wp_enqueue_style(  'obriiflow-social-widget', SW_URL . 'assets/widget.css', [], $css_version );
    wp_enqueue_script( 'obriiflow-social-widget', SW_URL . 'assets/widget.js',  [], $js_version, true );
    wp_localize_script( 'obriiflow-social-widget', 'SW_CONFIG', [
        'carousel_interval' => (float) ( $g['carousel_interval'] ?? 1.5 ) * 1000,
        'animation'         => $g['animation'] ?? 'fade',
        'position'          => $g['position'] ?? 'right',
        'bubble_enabled'    => ! empty( $g['bubble_enabled'] ),
        'bubble_delay'      => (float) ( $g['bubble_delay'] ?? 3 ) * 1000,
        'ajax_url'          => admin_url( 'admin-ajax.php' ),
        'nonce'             => wp_create_nonce( 'sw_track_nonce' ),
    ] );
}

/* ── Helpers ── */

function sw_t( $key ) {
    static $cache = [];

    $opts = get_option( SW_OPT_GENERAL, [] );
    $lang = $opts['language'] ?? 'en';
    if ( ! in_array( $lang, [ 'en', 'uk', 'ru' ], true ) ) {
        $lang = 'en';
    }

    if ( ! isset( $cache[ $lang ] ) ) {
        $file = SW_DIR . 'languages/' . $lang . '.json';
        $cache[ $lang ] = file_exists( $file )
            ? ( json_decode( file_get_contents( $file ), true ) ?: [] )
            : [];
    }

    $parts = explode( '.', $key );
    $val   = $cache[ $lang ];
    foreach ( $parts as $part ) {
        if ( ! isset( $val[ $part ] ) ) {
            if ( $lang !== 'en' ) {
                if ( ! isset( $cache['en'] ) ) {
                    $f = SW_DIR . 'languages/en.json';
                    $cache['en'] = file_exists( $f )
                        ? ( json_decode( file_get_contents( $f ), true ) ?: [] )
                        : [];
                }
                $v = $cache['en'];
                foreach ( $parts as $p ) {
                    if ( ! isset( $v[ $p ] ) ) return $key;
                    $v = $v[ $p ];
                }
                return is_string( $v ) ? $v : $key;
            }
            return $key;
        }
        $val = $val[ $part ];
    }
    return is_string( $val ) ? $val : $key;
}

function sw_get_general() {
    return wp_parse_args( get_option( SW_OPT_GENERAL, [] ), [
        'enabled'           => 1,
        'position'          => 'right',
        'offset_side'       => 20,
        'offset_bottom'     => 20,
        'show_labels'       => 1,
        'carousel_interval' => 1.5,
        'animation'         => 'fade',
        'bubble_enabled'    => 1,
        'bubble_text'       => '',
        'bubble_font_size'  => 15,
        'bubble_delay'      => 3,
        'language'          => 'en',
    ] );
}

function sw_get_active_messengers() {
    $list     = get_option( SW_OPT_MESSENGERS, [] );
    $defaults = sw_default_messengers();
    $active   = [];
    foreach ( $list as $entry ) {
        $key = $entry['key'] ?? '';
        if ( ! $key || empty( $entry['url'] ) || ! isset( $defaults[ $key ] ) ) continue;
        $active[] = [
            'key'      => $key,
            'label'    => ( $entry['label'] ?? '' ) !== '' ? $entry['label'] : $defaults[ $key ]['label'],
            'url'      => $entry['url'],
            'icon_url' => $defaults[ $key ]['icon_url'],
        ];
    }
    return $active;
}

function sw_get_messenger_icon_html( $messenger, $class = 'sw-icon-img', $alt = null ) {
    $icon_url = $messenger['icon_url'] ?? '';
    if ( ! $icon_url ) {
        return '';
    }

    $alt_text = null === $alt ? ( $messenger['label'] ?? '' ) : $alt;

    return sprintf(
        '<img class="%s" src="%s" alt="%s" loading="lazy" decoding="async">',
        esc_attr( $class ),
        esc_url( $icon_url ),
        esc_attr( $alt_text )
    );
}

function sw_default_messengers() {
    $icons_dir = SW_DIR . 'assets/icons/';
    $icons_url = SW_URL . 'assets/icons/';

    $items = [
        'instagram' => [ 'label' => 'Instagram',   'order' => 1  ],
        'telegram'  => [ 'label' => 'Telegram',    'order' => 2  ],
        'messenger' => [ 'label' => 'Messenger',   'order' => 3  ],
        'whatsapp'  => [ 'label' => 'WhatsApp',    'order' => 4  ],
        'viber'     => [ 'label' => 'Viber',       'order' => 5  ],
        'facebook'  => [ 'label' => 'Facebook',    'order' => 6  ],
        'tiktok'    => [ 'label' => 'TikTok',      'order' => 7  ],
        'twitter'   => [ 'label' => 'X / Twitter', 'order' => 8  ],
        'linkedin'  => [ 'label' => 'LinkedIn',    'order' => 9  ],
        'email'     => [ 'label' => 'Email',       'order' => 10 ],
        'youtube'   => [ 'label' => 'YouTube',     'order' => 11 ],
    ];

    foreach ( $items as $key => &$item ) {
        $item['url']     = '';
        $item['enabled'] = 0;
        $item['icon_url'] = $icons_url . $key . '.svg';
        $item['svg']     = file_get_contents( $icons_dir . $key . '.svg' ) ?: '';
    }

    return $items;
}

/* ── DB setup ── */

function sw_install_db() {
    global $wpdb;
    $charset = $wpdb->get_charset_collate();
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta( "CREATE TABLE {$wpdb->prefix}sw_stats (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  type varchar(10) NOT NULL,
  messenger varchar(50) DEFAULT NULL,
  created_at datetime NOT NULL,
  PRIMARY KEY  (id),
  KEY type_created (type,created_at)
) {$charset};" );
    update_option( 'sw_db_version', SW_DB_VERSION );
    if ( false === get_option( SW_OPT_GENERAL ) )    add_option( SW_OPT_GENERAL, [] );
    if ( false === get_option( SW_OPT_MESSENGERS ) ) add_option( SW_OPT_MESSENGERS, [
        [ 'key' => 'telegram', 'label' => 'Telegram', 'url' => '' ],
    ] );
}

// Runs on every load — creates/upgrades the table when version changes
add_action( 'plugins_loaded', function () {
    if ( get_option( 'sw_db_version' ) !== SW_DB_VERSION ) {
        sw_install_db();
    }
} );

register_activation_hook( __FILE__, 'sw_install_db' );
