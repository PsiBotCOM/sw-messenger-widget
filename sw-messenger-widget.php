<?php
/**
 * Plugin Name: Social Widget by psibot.com
 * Description: Floating social messenger widget with carousel, bubble, and full admin panel.
 * Version:     1.0.1
 * Author:      psibot.com
 * Author URI:  https://psibot.com
 * License:     GPL-2.0-or-later
 * Update URI: false
 * Text Domain: sw-messenger-widget
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'SW_VERSION',         '1.0.1' );
define( 'SW_DIR',             plugin_dir_path( __FILE__ ) );
define( 'SW_URL',             plugin_dir_url( __FILE__ ) );
define( 'SW_OPT_GENERAL',    'sw_general_settings' );
define( 'SW_OPT_MESSENGERS', 'sw_messengers' );

if ( is_admin() ) {
    require_once SW_DIR . 'admin/settings.php';
    require_once SW_DIR . 'admin/messengers.php';
    add_action( 'admin_menu', 'sw_register_menu' );
}

function sw_register_menu() {
    add_menu_page( 'Social Widget', 'Social Widget', 'manage_options', 'sw-messenger-widget', 'sw_page_settings', 'dashicons-share', 80 );
    add_submenu_page( 'sw-messenger-widget', 'General Settings', 'General', 'manage_options', 'sw-messenger-widget', 'sw_page_settings' );
    add_submenu_page( 'sw-messenger-widget', 'Messengers', 'Messengers', 'manage_options', 'sw-messenger-widget-messengers', 'sw_page_messengers' );
}

/* ── Frontend ── */
add_action( 'wp_enqueue_scripts', 'sw_enqueue' );

// Try all possible hooks — Flatsome uses wp_footer but some themes skip it
add_action( 'wp_footer',     'sw_render_widget', 100 );
add_action( 'wp_body_open', 'sw_render_widget', 100 );
add_action( 'shutdown',      'sw_render_via_ob', 0 );

// Output buffering fallback — catches cases where no hook fires
function sw_render_via_ob() {
    // Only use this if widget hasn't been rendered yet
    if ( ! defined('SW_RENDERED') && ! is_admin() && ! wp_doing_ajax() ) {
        // We can't inject into already-sent output here safely,
        // so this is just a safety no-op placeholder
    }
}

$GLOBALS['sw_rendered'] = false;

function sw_render_widget() {
    // Prevent double render if multiple hooks fire
    if ( ! empty( $GLOBALS['sw_rendered'] ) ) return;

    $g = sw_get_general();
    if ( empty( $g['enabled'] ) ) return;
    $messengers = sw_get_active_messengers();
    if ( empty( $messengers ) ) return;

    $GLOBALS['sw_rendered'] = true;

    $position      = $g['position'] ?? 'right';
    $offset_side   = intval( $g['offset_side'] ?? 20 );
    $offset_bottom = intval( $g['offset_bottom'] ?? 20 );
    $bubble_text   = esc_html( $g['bubble_text'] ?? 'Hi! How can we help?' );
    $bubble_on     = ! empty( $g['bubble_enabled'] );
    $side_prop     = ( $position === 'left' ) ? 'left' : 'right';
    ?>
    <div id="sw-widget" data-position="<?php echo esc_attr($position); ?>"
         style="position:fixed;<?php echo esc_attr($side_prop); ?>:<?php echo $offset_side; ?>px;bottom:<?php echo $offset_bottom; ?>px;z-index:99999;display:flex;flex-direction:column;align-items:<?php echo $position==='left'?'flex-start':'flex-end'; ?>;gap:10px;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;">

        <?php if ( $bubble_on ) : ?>
        <div id="sw-bubble" class="sw-bubble" style="display:none;">
            <button class="sw-bubble-close" aria-label="Close">&#x2715;</button>
            <span><?php echo $bubble_text; ?></span>
        </div>
        <?php endif; ?>

        <div id="sw-list" class="sw-list" aria-hidden="true">
            <?php foreach ( $messengers as $m ) : ?>
            <a href="<?php echo esc_url($m['url']); ?>" class="sw-item"
               target="_blank" rel="noopener noreferrer"
               aria-label="<?php echo esc_attr($m['label']); ?>">
                <span class="sw-item-icon"><?php echo $m['svg']; ?></span>
                <span class="sw-item-label"><?php echo esc_html($m['label']); ?></span>
            </a>
            <?php endforeach; ?>
        </div>

        <button id="sw-toggle" class="sw-toggle" aria-label="Open messenger list" aria-expanded="false">
            <span id="sw-carousel" class="sw-carousel">
                <?php foreach ( $messengers as $idx => $m ) : ?>
                <span class="sw-slide<?php echo $idx === 0 ? ' active' : ''; ?>">
                    <?php echo $m['svg']; ?>
                </span>
                <?php endforeach; ?>
            </span>
            <span id="sw-close-icon" class="sw-close-icon">&#x2715;</span>
        </button>
    </div>
    <?php
}

function sw_enqueue() {
    $g = sw_get_general();
    if ( empty( $g['enabled'] ) ) return;
    $messengers = sw_get_active_messengers();
    if ( empty( $messengers ) ) return;

    wp_enqueue_style(  'sw-messenger-widget', SW_URL . 'assets/widget.css', [], SW_VERSION );
    wp_enqueue_script( 'sw-messenger-widget', SW_URL . 'assets/widget.js',  [], SW_VERSION, true );
    wp_localize_script( 'sw-messenger-widget', 'SW_CONFIG', [
        'carousel_interval' => (float)( $g['carousel_interval'] ?? 1.5 ) * 1000,
        'animation'         => $g['animation'] ?? 'fade',
        'position'          => $g['position'] ?? 'right',
        'bubble_enabled'    => ! empty( $g['bubble_enabled'] ),
        'bubble_delay'      => (float)( $g['bubble_delay'] ?? 3 ) * 1000,
    ]);
}

/* ── Helpers ── */
function sw_get_general() {
    return wp_parse_args( get_option( SW_OPT_GENERAL, [] ), [
        'enabled' => 1, 'position' => 'right', 'offset_side' => 20,
        'offset_bottom' => 20, 'carousel_interval' => 1.5, 'animation' => 'fade',
        'bubble_enabled' => 1, 'bubble_text' => 'Hi! How can we help?', 'bubble_delay' => 3,
    ]);
}

function sw_get_messengers_config() {
    $defaults = sw_default_messengers();
    $saved    = get_option( SW_OPT_MESSENGERS, [] );
    foreach ( $defaults as $key => &$def ) {
        if ( isset( $saved[$key] ) ) {
            $def['enabled'] = !empty( $saved[$key]['enabled'] );
            $def['url']     = $saved[$key]['url'] ?? '';
            $def['order']   = isset( $saved[$key]['order'] ) ? intval( $saved[$key]['order'] ) : $def['order'];
        }
    }
    return $defaults;
}

function sw_get_active_messengers() {
    $all    = sw_get_messengers_config();
    $active = array_filter( $all, fn($m) => !empty($m['enabled']) && !empty($m['url']) );
    usort( $active, fn($a,$b) => $a['order'] <=> $b['order'] );
    return array_values( $active );
}

function sw_default_messengers() {
    return [
        'instagram' => ['label'=>'Instagram','url'=>'','enabled'=>0,'order'=>1,'svg'=>'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32" width="32" height="32"><defs><radialGradient id="ig1" cx="30%" cy="107%" r="150%"><stop offset="0%" stop-color="#ffd879"/><stop offset="25%" stop-color="#f9a040"/><stop offset="45%" stop-color="#f2703f"/><stop offset="65%" stop-color="#e2437e"/><stop offset="85%" stop-color="#bf3baf"/><stop offset="100%" stop-color="#7b41c4"/></radialGradient></defs><rect width="32" height="32" rx="8" fill="url(#ig1)"/><rect x="6" y="6" width="20" height="20" rx="5.5" fill="none" stroke="#fff" stroke-width="2"/><circle cx="16" cy="16" r="4.5" fill="none" stroke="#fff" stroke-width="2"/><circle cx="22.5" cy="9.5" r="1.3" fill="#fff"/></svg>'],
        'telegram'  => ['label'=>'Telegram','url'=>'','enabled'=>0,'order'=>2,'svg'=>'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32" width="32" height="32"><rect width="32" height="32" rx="8" fill="#2AABEE"/><path d="M6 16l14.5-7L19 26l-4.5-4-3 2.5V20l8-8-10 5.5L6 16z" fill="#fff"/></svg>'],
        'messenger' => ['label'=>'Messenger','url'=>'','enabled'=>0,'order'=>3,'svg'=>'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32" width="32" height="32"><defs><linearGradient id="ms1" x1="0" y1="0" x2="1" y2="1"><stop offset="0%" stop-color="#00B2FF"/><stop offset="100%" stop-color="#006AFF"/></linearGradient></defs><rect width="32" height="32" rx="8" fill="url(#ms1)"/><path d="M16 5C9.9 5 5 9.6 5 15.2c0 3 1.4 5.7 3.7 7.5V26l3.4-1.9c.9.3 1.9.4 2.9.4 6.1 0 11-4.6 11-10.3C26 9.6 22.1 5 16 5z" fill="#fff"/><path d="M9.5 18l4.5-4.8 2.8 2.8 4.2-2.8" fill="none" stroke="#006AFF" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>'],
        'whatsapp'  => ['label'=>'WhatsApp','url'=>'','enabled'=>0,'order'=>4,'svg'=>'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32" width="32" height="32"><rect width="32" height="32" rx="8" fill="#25D366"/><path d="M16 6A10 10 0 0 0 7.4 21.2L6 26l4.9-1.3A10 10 0 1 0 16 6z" fill="#fff"/><path d="M12 12.5c.4 1 1.4 3 2.8 4.4s3.4 2.4 4.4 2.8c.3.1.6 0 .7-.2l1-1.2c.2-.3.5-.3.8-.1l2.4 1.2c.3.2.4.5.3.8-.4 1.2-1.5 2.5-2.7 2.7-1.2.3-3-.1-5.6-2.1-2.6-1.9-4.1-4.4-4.5-5.7-.4-1.3.2-2.7 1.1-3.1.3-.1.7 0 .8.3l1.2 2.5c.2.2 0 .6-.1.7l-1.2 1z" fill="#25D366"/></svg>'],
        'viber'     => ['label'=>'Viber','url'=>'','enabled'=>0,'order'=>5,'svg'=>'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32" width="32" height="32"><rect width="32" height="32" rx="8" fill="#7360F2"/><path d="M16 5c-4.8 0-8.8 3.4-8.8 7.9 0 2.5 1.2 4.7 3.1 6.2v3l2.7-1.5c1 .3 2 .4 3 .4 4.8 0 8.8-3.5 8.8-8C24.8 8.4 20.8 5 16 5z" fill="#fff"/><path d="M19 19.5c-.3 0-1.3-.4-2-.7a9.5 9.5 0 0 1-2-1.4 9.5 9.5 0 0 1-1.4-2c-.3-.7-.7-1.7-.7-2 0-.4.3-.7.5-.9l.6-.6c.2-.2.5-.2.7 0l1.3 1.9c.2.2.2.5 0 .7l-.7.7a5.5 5.5 0 0 0 1 1.4 5.5 5.5 0 0 0 1.4 1l.7-.7c.2-.2.5-.2.7 0l1.9 1.3c.3.2.3.5.1.7l-.6.6c-.2.2-.3.3-.5 0z" fill="#7360F2"/></svg>'],
        'facebook'  => ['label'=>'Facebook','url'=>'','enabled'=>0,'order'=>6,'svg'=>'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32" width="32" height="32"><rect width="32" height="32" rx="8" fill="#1877F2"/><path d="M21 5h-3.3C14.9 5 13 7 13 9.8V12h-3v4h3v11h4V16h3l.5-4H17v-2c0-.7.3-1 1.1-1H21V5z" fill="#fff"/></svg>'],
        'tiktok'    => ['label'=>'TikTok','url'=>'','enabled'=>0,'order'=>7,'svg'=>'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32" width="32" height="32"><rect width="32" height="32" rx="8" fill="#010101"/><path d="M22 8.5c-1.4 0-2.6-.8-3.2-2H15v13a2.3 2.3 0 1 1-1.6-2.2v-3.4A5.8 5.8 0 1 0 19 19.5V13c1.2.8 2.6 1.3 4 1.3V11a5 5 0 0 1-1-.5z" fill="#fff"/></svg>'],
        'twitter'   => ['label'=>'X / Twitter','url'=>'','enabled'=>0,'order'=>8,'svg'=>'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32" width="32" height="32"><rect width="32" height="32" rx="8" fill="#000"/><path d="M23 6h-3.2L16 11.2 12.5 6H7l6.5 9.2L7 26h3.2l4-5.5 3.8 5.5H24l-6.8-9.5L23 6z" fill="#fff"/></svg>'],
        'linkedin'  => ['label'=>'LinkedIn','url'=>'','enabled'=>0,'order'=>9,'svg'=>'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32" width="32" height="32"><rect width="32" height="32" rx="8" fill="#0A66C2"/><path d="M9 12h3.5v11H9V12zm1.7-1.5a2 2 0 1 1 0-4 2 2 0 0 1 0 4zM15 12h3.3v1.5c.7-1.1 1.9-1.7 3.2-1.7 2.8 0 4 1.9 4 4.5V23h-3.3v-6c0-1.4-.5-2.3-1.8-2.3-1.4 0-2.2 1-2.2 2.7V23H15V12z" fill="#fff"/></svg>'],
        'email'     => ['label'=>'Email','url'=>'','enabled'=>0,'order'=>10,'svg'=>'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32" width="32" height="32"><rect width="32" height="32" rx="8" fill="#EA4335"/><rect x="5" y="9" width="22" height="15" rx="2.5" fill="#fff"/><path d="M5 10.5l11 7.5 11-7.5" fill="none" stroke="#EA4335" stroke-width="2" stroke-linecap="round"/></svg>'],
    ];
}

register_activation_hook( __FILE__, function() {
    if ( false === get_option( SW_OPT_GENERAL ) )    add_option( SW_OPT_GENERAL, [] );
    if ( false === get_option( SW_OPT_MESSENGERS ) ) add_option( SW_OPT_MESSENGERS, [] );
});
