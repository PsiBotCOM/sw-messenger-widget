<?php
if ( ! defined( 'ABSPATH' ) ) exit;

function sw_page_settings() {
    if ( ! current_user_can( 'manage_options' ) ) return;

    if ( isset( $_POST['sw_settings_nonce'] ) && wp_verify_nonce( $_POST['sw_settings_nonce'], 'sw_save_settings' ) ) {
        update_option( SW_OPT_GENERAL, [
            'enabled'           => ! empty( $_POST['enabled'] ) ? 1 : 0,
            'position'          => in_array( $_POST['position'] ?? '', [ 'left', 'right' ] ) ? $_POST['position'] : 'right',
            'offset_side'       => intval( $_POST['offset_side'] ?? 20 ),
            'offset_bottom'     => intval( $_POST['offset_bottom'] ?? 20 ),
            'carousel_interval' => max( 0.5, min( 10, floatval( str_replace( ',', '.', $_POST['carousel_interval'] ?? 1.5 ) ) ) ),
            'animation'         => in_array( $_POST['animation'] ?? '', [ 'fade', 'slide' ] ) ? $_POST['animation'] : 'fade',
            'bubble_enabled'    => ! empty( $_POST['bubble_enabled'] ) ? 1 : 0,
            'bubble_text'       => sanitize_text_field( $_POST['bubble_text'] ?? '' ),
            'bubble_delay'      => max( 0, floatval( str_replace( ',', '.', $_POST['bubble_delay'] ?? 3 ) ) ),
            'language'          => in_array( $_POST['language'] ?? '', [ 'en', 'uk', 'ru' ] ) ? $_POST['language'] : 'en',
        ] );
        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html( sw_t( 'admin.settings_saved' ) ) . '</p></div>';
    }

    $g = sw_get_general();
    ?>
    <div class="wrap sw-admin">
        <h1><?php echo esc_html( sw_t( 'admin.settings_title' ) ); ?></h1>
        <form method="post">
            <?php wp_nonce_field( 'sw_save_settings', 'sw_settings_nonce' ); ?>

            <div class="sw-card">
                <h2><?php echo esc_html( sw_t( 'admin.section_widget' ) ); ?></h2>
                <table class="form-table">
                    <tr><th><?php echo esc_html( sw_t( 'admin.enable_plugin' ) ); ?></th><td>
                        <label class="sw-toggle-label">
                            <input type="checkbox" name="enabled" value="1" <?php checked( $g['enabled'], 1 ); ?>>
                            <span class="sw-toggle-switch"></span>
                            <span class="sw-toggle-text"><?php echo esc_html( sw_t( 'admin.show_widget_text' ) ); ?></span>
                        </label>
                    </td></tr>
                    <tr><th><?php echo esc_html( sw_t( 'admin.position' ) ); ?></th><td>
                        <label><input type="radio" name="position" value="right" <?php checked( $g['position'], 'right' ); ?>> <?php echo esc_html( sw_t( 'admin.position_right' ) ); ?></label> &nbsp;
                        <label><input type="radio" name="position" value="left"  <?php checked( $g['position'], 'left' );  ?>> <?php echo esc_html( sw_t( 'admin.position_left' ) ); ?></label>
                    </td></tr>
                    <tr><th><?php echo esc_html( sw_t( 'admin.offset_side' ) ); ?></th><td>
                        <input type="number" name="offset_side" value="<?php echo esc_attr( $g['offset_side'] ); ?>" min="0" max="200" class="small-text"> px
                    </td></tr>
                    <tr><th><?php echo esc_html( sw_t( 'admin.offset_bottom' ) ); ?></th><td>
                        <input type="number" name="offset_bottom" value="<?php echo esc_attr( $g['offset_bottom'] ); ?>" min="0" max="200" class="small-text"> px
                    </td></tr>
                </table>
            </div>

            <div class="sw-card">
                <h2><?php echo esc_html( sw_t( 'admin.section_carousel' ) ); ?></h2>
                <table class="form-table">
                    <tr><th><?php echo esc_html( sw_t( 'admin.icon_display_time' ) ); ?></th><td>
                        <input type="number" name="carousel_interval" value="<?php echo esc_attr( $g['carousel_interval'] ); ?>" min="0.5" max="10" step="0.5" class="small-text"> sec
                        <p class="description"><?php echo esc_html( sw_t( 'admin.icon_display_default' ) ); ?></p>
                    </td></tr>
                    <tr><th><?php echo esc_html( sw_t( 'admin.transition_animation' ) ); ?></th><td>
                        <select name="animation">
                            <option value="fade"  <?php selected( $g['animation'], 'fade' );  ?>><?php echo esc_html( sw_t( 'admin.anim_fade' ) ); ?></option>
                            <option value="slide" <?php selected( $g['animation'], 'slide' ); ?>><?php echo esc_html( sw_t( 'admin.anim_slide' ) ); ?></option>
                        </select>
                    </td></tr>
                </table>
            </div>

            <div class="sw-card">
                <h2><?php echo esc_html( sw_t( 'admin.section_bubble' ) ); ?></h2>
                <table class="form-table">
                    <tr><th><?php echo esc_html( sw_t( 'admin.enable_bubble' ) ); ?></th><td>
                        <label class="sw-toggle-label">
                            <input type="checkbox" name="bubble_enabled" value="1" <?php checked( $g['bubble_enabled'], 1 ); ?>>
                            <span class="sw-toggle-switch"></span>
                            <span class="sw-toggle-text"><?php echo esc_html( sw_t( 'admin.show_bubble_text' ) ); ?></span>
                        </label>
                    </td></tr>
                    <tr><th><?php echo esc_html( sw_t( 'admin.bubble_text' ) ); ?></th><td>
                        <input type="text" name="bubble_text" value="<?php echo esc_attr( $g['bubble_text'] ); ?>"
                               placeholder="<?php echo esc_attr( sw_t( 'frontend.bubble_default' ) ); ?>"
                               class="regular-text">
                    </td></tr>
                    <tr><th><?php echo esc_html( sw_t( 'admin.appear_delay' ) ); ?></th><td>
                        <input type="number" name="bubble_delay" value="<?php echo esc_attr( $g['bubble_delay'] ); ?>" min="0" max="60" step="0.5" class="small-text"> <?php echo esc_html( sw_t( 'admin.appear_delay_suffix' ) ); ?>
                    </td></tr>
                </table>
            </div>

            <div class="sw-card">
                <h2><?php echo esc_html( sw_t( 'admin.section_language' ) ); ?></h2>
                <table class="form-table">
                    <tr><th><?php echo esc_html( sw_t( 'admin.language' ) ); ?></th><td>
                        <select name="language">
                            <option value="en" <?php selected( $g['language'], 'en' ); ?>>English</option>
                            <option value="uk" <?php selected( $g['language'], 'uk' ); ?>>Українська</option>
                            <option value="ru" <?php selected( $g['language'], 'ru' ); ?>>Русский</option>
                        </select>
                    </td></tr>
                </table>
            </div>

            <?php submit_button( sw_t( 'admin.save_settings' ) ); ?>
        </form>
    </div>

    <style>
    .sw-admin .sw-card{background:#fff;border:1px solid #c3c4c7;border-radius:4px;padding:1px 20px 20px;margin-bottom:20px;}
    .sw-admin .sw-card h2{font-size:14px;font-weight:600;margin-bottom:0;}
    .sw-toggle-label{display:inline-flex;align-items:center;gap:10px;cursor:pointer;}
    .sw-toggle-label input{display:none;}
    .sw-toggle-switch{position:relative;width:36px;height:20px;background:#ccc;border-radius:10px;transition:background .2s;flex-shrink:0;}
    .sw-toggle-switch::after{content:'';position:absolute;top:2px;left:2px;width:16px;height:16px;background:#fff;border-radius:50%;transition:transform .2s;}
    .sw-toggle-label input:checked+.sw-toggle-switch{background:#2271b1;}
    .sw-toggle-label input:checked+.sw-toggle-switch::after{transform:translateX(16px);}
    </style>
    <?php
}
