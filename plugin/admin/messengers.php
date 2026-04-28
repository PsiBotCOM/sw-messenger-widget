<?php
if ( ! defined( 'ABSPATH' ) ) exit;

function sw_page_messengers() {
    if ( ! current_user_can( 'manage_options' ) ) return;

    if ( isset( $_POST['sw_messengers_nonce'] ) && wp_verify_nonce( $_POST['sw_messengers_nonce'], 'sw_save_messengers' ) ) {
        $all    = sw_default_messengers();
        $posted = $_POST['messengers'] ?? [];
        $save   = [];
        foreach ( $all as $key => $def ) {
            $p = $posted[ $key ] ?? [];
            $save[ $key ] = [
                'enabled' => ! empty( $p['enabled'] ) ? 1 : 0,
                'url'     => sanitize_text_field( $p['url'] ?? '' ),
                'order'   => intval( $p['order'] ?? $def['order'] ),
            ];
        }
        update_option( SW_OPT_MESSENGERS, $save );
        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html( sw_t( 'admin.messengers_saved' ) ) . '</p></div>';
    }

    $messengers = sw_get_messengers_config();
    uasort( $messengers, fn( $a, $b ) => $a['order'] <=> $b['order'] );

    $placeholders = [
        'instagram' => 'https://instagram.com/yourname',
        'telegram'  => 'https://t.me/yourname',
        'messenger' => 'https://m.me/yourpagename',
        'whatsapp'  => 'https://wa.me/380XXXXXXXXX',
        'viber'     => 'viber://chat?number=+380XXXXXXXXX',
        'facebook'  => 'https://facebook.com/yourpage',
        'tiktok'    => 'https://tiktok.com/@yourname',
        'twitter'   => 'https://x.com/yourname',
        'linkedin'  => 'https://linkedin.com/company/yourcompany',
        'email'     => 'mailto:hello@yourdomain.com',
    ];
    ?>
    <div class="wrap sw-admin">
        <h1><?php echo esc_html( sw_t( 'admin.messengers_title' ) ); ?></h1>
        <p class="description" style="margin-bottom:16px;"><?php echo esc_html( sw_t( 'admin.messengers_desc' ) ); ?></p>

        <form method="post" id="sw-form">
            <?php wp_nonce_field( 'sw_save_messengers', 'sw_messengers_nonce' ); ?>
            <table class="wp-list-table widefat fixed striped" id="sw-sortable">
                <thead><tr>
                    <th style="width:32px;"></th>
                    <th style="width:40px;"><?php echo esc_html( sw_t( 'admin.col_icon' ) ); ?></th>
                    <th style="width:130px;"><?php echo esc_html( sw_t( 'admin.col_messenger' ) ); ?></th>
                    <th><?php echo esc_html( sw_t( 'admin.col_url' ) ); ?></th>
                    <th style="width:80px;"><?php echo esc_html( sw_t( 'admin.col_enabled' ) ); ?></th>
                    <th style="width:60px;"><?php echo esc_html( sw_t( 'admin.col_order' ) ); ?></th>
                </tr></thead>
                <tbody>
                <?php foreach ( $messengers as $key => $m ) : ?>
                <tr class="sw-row">
                    <td class="sw-handle" title="Drag to reorder">&#9776;</td>
                    <td class="sw-icon-cell"><?php echo sw_get_messenger_icon_html( $m, 'sw-admin-icon' ); ?></td>
                    <td><strong><?php echo esc_html( $m['label'] ); ?></strong></td>
                    <td><input type="text" name="messengers[<?php echo esc_attr( $key ); ?>][url]"
                        value="<?php echo esc_attr( $m['url'] ); ?>"
                        placeholder="<?php echo esc_attr( $placeholders[ $key ] ?? '' ); ?>"
                        style="width:100%;max-width:400px;"></td>
                    <td><label class="sw-toggle-label">
                        <input type="checkbox" name="messengers[<?php echo esc_attr( $key ); ?>][enabled]"
                            value="1" <?php checked( $m['enabled'], 1 ); ?>>
                        <span class="sw-toggle-switch"></span>
                    </label></td>
                    <td>
                        <input type="hidden" name="messengers[<?php echo esc_attr( $key ); ?>][order]"
                            value="<?php echo esc_attr( $m['order'] ); ?>" class="sw-order">
                        <span class="sw-order-num"><?php echo esc_html( $m['order'] ); ?></span>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <p style="margin-top:12px;color:#646970;font-size:13px;"><?php echo esc_html( sw_t( 'admin.messengers_tip' ) ); ?></p>
            <?php submit_button( sw_t( 'admin.save_messengers' ) ); ?>
        </form>
    </div>

    <style>
    .sw-admin table td{vertical-align:middle;padding:9px 8px;}
    .sw-icon-cell{line-height:0;}
    .sw-admin-icon{width:32px;height:32px;display:block;object-fit:contain;}
    .sw-handle{cursor:grab;color:#aaa;font-size:18px;text-align:center;}
    .sw-handle:active{cursor:grabbing;}
    .sw-row.sw-dragging{opacity:.5;background:#f0f6fc!important;}
    .sw-toggle-label{display:inline-flex;align-items:center;cursor:pointer;}
    .sw-toggle-label input{display:none;}
    .sw-toggle-switch{position:relative;width:36px;height:20px;background:#ccc;border-radius:10px;transition:background .2s;}
    .sw-toggle-switch::after{content:'';position:absolute;top:2px;left:2px;width:16px;height:16px;background:#fff;border-radius:50%;transition:transform .2s;}
    .sw-toggle-label input:checked+.sw-toggle-switch{background:#2271b1;}
    .sw-toggle-label input:checked+.sw-toggle-switch::after{transform:translateX(16px);}
    </style>

    <script>
    (function(){
        const tbody = document.querySelector('#sw-sortable tbody');
        let dragged = null;
        tbody.querySelectorAll('.sw-handle').forEach(h => {
            const row = h.closest('tr');
            row.setAttribute('draggable','true');
            row.addEventListener('dragstart', e => { dragged = e.currentTarget; setTimeout(()=>dragged.classList.add('sw-dragging'),0); });
            row.addEventListener('dragend', () => { dragged && dragged.classList.remove('sw-dragging'); dragged=null; reorder(); });
        });
        tbody.addEventListener('dragover', e => {
            e.preventDefault();
            const over = e.target.closest('tr.sw-row');
            if (over && over !== dragged) {
                const mid = over.getBoundingClientRect().top + over.getBoundingClientRect().height/2;
                tbody.insertBefore(dragged, e.clientY < mid ? over : over.nextSibling);
            }
        });
        function reorder() {
            tbody.querySelectorAll('tr.sw-row').forEach((r,i) => {
                r.querySelector('.sw-order').value = i+1;
                r.querySelector('.sw-order-num').textContent = i+1;
            });
        }
    })();
    </script>
    <?php
}
