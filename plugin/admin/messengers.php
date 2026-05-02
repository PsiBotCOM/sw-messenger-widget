<?php
if ( ! defined( 'ABSPATH' ) ) exit;

function sw_page_messengers() {
    if ( ! current_user_can( 'manage_options' ) ) return;

    if ( isset( $_POST['sw_messengers_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['sw_messengers_nonce'] ) ), 'sw_save_messengers' ) ) {
        $defaults = sw_default_messengers();
        $posted   = isset( $_POST['messengers'] ) ? wp_unslash( (array) $_POST['messengers'] ) : [];
        $save     = [];
        foreach ( $posted as $entry ) {
            if ( ! is_array( $entry ) ) {
                continue;
            }

            $key   = sanitize_key( $entry['key'] ?? '' );
            $label = sanitize_text_field( $entry['label'] ?? '' );
            $url   = sanitize_text_field( $entry['url'] ?? '' );
            if ( ! $key || ! isset( $defaults[ $key ] ) ) continue;
            $save[] = [
                'key'   => $key,
                'label' => $label !== '' ? $label : $defaults[ $key ]['label'],
                'url'   => $url,
            ];
        }
        update_option( SW_OPT_MESSENGERS, $save );
        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html( sw_t( 'admin.messengers_saved' ) ) . '</p></div>';
    }

    $list     = get_option( SW_OPT_MESSENGERS, [] );
    $defaults = sw_default_messengers();

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
        'youtube'   => 'https://youtube.com/@yourchannel',
    ];

    $defaults_js = [];
    foreach ( $defaults as $key => $m ) {
        $defaults_js[ $key ] = [ 'label' => $m['label'], 'icon_url' => $m['icon_url'] ];
    }

    $trash_svg = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>';
    ?>
    <div class="wrap sw-admin">
        <h1><?php echo esc_html( sw_t( 'admin.messengers_title' ) ); ?></h1>
        <p class="description" style="margin-bottom:16px;"><?php echo esc_html( sw_t( 'admin.messengers_desc' ) ); ?></p>

        <form method="post" id="sw-form">
            <?php wp_nonce_field( 'sw_save_messengers', 'sw_messengers_nonce' ); ?>
            <div class="sw-card">
                <table class="wp-list-table widefat fixed striped" id="sw-sortable">
                    <thead><tr>
                        <th style="width:32px;"></th>
                        <th style="width:40px;"><?php echo esc_html( sw_t( 'admin.col_icon' ) ); ?></th>
                        <th style="width:170px;"><?php echo esc_html( sw_t( 'admin.col_label' ) ); ?></th>
                        <th><?php echo esc_html( sw_t( 'admin.col_url' ) ); ?></th>
                        <th class="sw-delete-col"></th>
                    </tr></thead>
                    <tbody id="sw-tbody">
                    <?php if ( empty( $list ) ) : ?>
                        <tr id="sw-empty-row"><td colspan="5" class="sw-empty-msg"><?php echo esc_html( sw_t( 'admin.no_messengers' ) ); ?></td></tr>
                    <?php else : ?>
                        <?php foreach ( $list as $i => $entry ) :
                            $key   = $entry['key'] ?? '';
                            if ( ! isset( $defaults[ $key ] ) ) continue;
                            $label = ( $entry['label'] ?? '' ) !== '' ? $entry['label'] : $defaults[ $key ]['label'];
                            $url   = $entry['url'] ?? '';
                            $ph    = $placeholders[ $key ] ?? '';
                            $allowed_img = [ 'img' => [ 'class' => true, 'src' => true, 'alt' => true, 'loading' => true, 'decoding' => true ] ];
                        ?>
                        <tr class="sw-row" draggable="true">
                            <td class="sw-handle" title="Drag to reorder">&#9776;</td>
                            <td class="sw-icon-cell"><?php echo wp_kses( sw_get_messenger_icon_html( $defaults[ $key ], 'sw-admin-icon' ), $allowed_img ); ?></td>
                            <td>
                                <input type="hidden" name="messengers[<?php echo absint( $i ); ?>][key]" value="<?php echo esc_attr( $key ); ?>">
                                <input type="text"   name="messengers[<?php echo absint( $i ); ?>][label]" value="<?php echo esc_attr( $label ); ?>" class="sw-label-input">
                            </td>
                            <td><input type="text" name="messengers[<?php echo absint( $i ); ?>][url]" value="<?php echo esc_attr( $url ); ?>" placeholder="<?php echo esc_attr( $ph ); ?>" class="sw-url-input"></td>
                            <td class="sw-delete-cell">
                                <button type="button" class="sw-delete-btn" title="<?php echo esc_attr( sw_t( 'admin.delete' ) ); ?>">
                                    <?php
                                    $allowed_svg = [
                                        'svg'      => [ 'xmlns' => true, 'width' => true, 'height' => true, 'viewBox' => true, 'fill' => true, 'stroke' => true, 'stroke-width' => true, 'stroke-linecap' => true, 'stroke-linejoin' => true ],
                                        'polyline' => [ 'points' => true ],
                                        'path'     => [ 'd' => true ],
                                    ];
                                    echo wp_kses( $trash_svg, $allowed_svg );
                                    ?>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <?php submit_button( sw_t( 'admin.save_messengers' ) ); ?>
        </form>

        <div class="sw-card sw-add-card">
            <h2><?php echo esc_html( sw_t( 'admin.add_messenger' ) ); ?></h2>
            <div class="sw-add-form">
                <div class="sw-add-select-wrap">
                    <img id="sw-add-icon" class="sw-admin-icon" src="" alt="">
                    <select id="sw-add-key">
                        <?php foreach ( $defaults as $key => $m ) : ?>
                        <option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $m['label'] ); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <input type="text" id="sw-add-label" style="width:150px;" placeholder="<?php echo esc_attr( sw_t( 'admin.col_label' ) ); ?>">
                <input type="text" id="sw-add-url" style="width:280px;" placeholder="">
                <button type="button" id="sw-add-btn" class="button button-primary"><?php echo esc_html( sw_t( 'admin.add' ) ); ?></button>
            </div>
            <p class="description" style="margin-top:10px;"><?php echo esc_html( sw_t( 'admin.messengers_tip' ) ); ?></p>
        </div>
    </div>

    <style>
    .sw-admin table td { vertical-align: middle; padding: 9px 8px; }
    .sw-icon-cell { line-height: 0; }
    .sw-admin-icon { width: 32px; height: 32px; display: block; object-fit: contain; }
    .sw-handle { cursor: grab; color: #aaa; font-size: 18px; text-align: center; }
    .sw-handle:active { cursor: grabbing; }
    .sw-row.sw-dragging { opacity: .5; background: #f0f6fc !important; }
    .sw-label-input { width: 100%; max-width: 150px; }
    .sw-url-input { width: 100%; max-width: 400px; }
    .sw-empty-msg { text-align: center; color: #999; padding: 20px !important; font-style: italic; }
    .sw-delete-col { width: 56px; }
    .sw-admin table td.sw-delete-cell { padding: 0 8px; text-align: center; overflow: visible; }
    .sw-delete-btn { width: 30px; height: 30px; background: none; border: none; cursor: pointer; color: #b32d2e; padding: 0; line-height: 1; border-radius: 4px; display: inline-flex; align-items: center; justify-content: center; vertical-align: middle; }
    .sw-delete-btn svg { width: 16px; height: 16px; display: block; flex: 0 0 auto; overflow: visible; }
    .sw-delete-btn:hover { background: #fbeaea; }

    .sw-admin .sw-card { background: #fff; border: 1px solid #c3c4c7; border-radius: 4px; padding: 1px 20px 20px; margin-bottom: 20px; }
    .sw-add-card h2 { font-size: 14px; font-weight: 600; margin-bottom: 12px; }
    .sw-add-form { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
    .sw-add-select-wrap { display: flex; align-items: center; gap: 8px; }
    .sw-add-select-wrap img { flex-shrink: 0; }
    </style>

    <script>
    (function () {
        var DEFS = <?php echo wp_json_encode( $defaults_js ); ?>;
        var PHS  = <?php echo wp_json_encode( $placeholders ); ?>;
        var DELETE_LABEL = <?php echo wp_json_encode( sw_t( 'admin.delete' ) ); ?>;
        var EMPTY_MSG    = <?php echo wp_json_encode( sw_t( 'admin.no_messengers' ) ); ?>;
        var nextIdx = <?php echo count( $list ) + 100; ?>;

        var tbody    = document.getElementById('sw-tbody');
        var addKey   = document.getElementById('sw-add-key');
        var addLabel = document.getElementById('sw-add-label');
        var addUrl   = document.getElementById('sw-add-url');
        var addBtn   = document.getElementById('sw-add-btn');
        var addIcon  = document.getElementById('sw-add-icon');

        function updateAddPreview() {
            var d = DEFS[addKey.value] || {};
            if (addIcon) addIcon.src = d.icon_url || '';
            addUrl.placeholder = PHS[addKey.value] || '';
            addLabel.placeholder = d.label || '';
        }
        addKey.addEventListener('change', updateAddPreview);
        updateAddPreview();

        addBtn.addEventListener('click', function () {
            var key     = addKey.value;
            var d       = DEFS[key] || {};
            var label   = addLabel.value.trim() || d.label || key;
            var url     = addUrl.value.trim();
            var ph      = PHS[key] || '';
            var iconUrl = d.icon_url || '';

            removeEmptyRow();
            var tr = buildRow(nextIdx, key, iconUrl, label, url, ph);
            tbody.appendChild(tr);
            initRow(tr);
            nextIdx++;

            addLabel.value = '';
            addUrl.value   = '';
            updateAddPreview();
        });

        tbody.addEventListener('click', function (e) {
            var btn = e.target.closest('.sw-delete-btn');
            if (!btn) return;
            btn.closest('tr').remove();
            if (!tbody.querySelector('tr.sw-row')) showEmptyRow();
        });

        /* Drag-and-drop */
        var dragged = null;
        tbody.addEventListener('dragstart', function (e) {
            var row = e.target.closest('tr.sw-row');
            if (!row) return;
            dragged = row;
            setTimeout(function () { if (dragged) dragged.classList.add('sw-dragging'); }, 0);
        });
        tbody.addEventListener('dragend', function () {
            if (dragged) dragged.classList.remove('sw-dragging');
            dragged = null;
        });
        tbody.addEventListener('dragover', function (e) {
            e.preventDefault();
            if (!dragged) return;
            var over = e.target.closest('tr.sw-row');
            if (over && over !== dragged) {
                var mid = over.getBoundingClientRect().top + over.getBoundingClientRect().height / 2;
                tbody.insertBefore(dragged, e.clientY < mid ? over : over.nextSibling);
            }
        });

        /* Re-index inputs before submit so PHP receives rows in visual order */
        document.getElementById('sw-form').addEventListener('submit', function () {
            tbody.querySelectorAll('tr.sw-row').forEach(function (row, i) {
                row.querySelectorAll('input[name^="messengers["]').forEach(function (input) {
                    input.name = input.name.replace(/^messengers\[\d+\]/, 'messengers[' + i + ']');
                });
            });
        });

        tbody.querySelectorAll('tr.sw-row').forEach(initRow);

        function initRow(row) {
            row.setAttribute('draggable', 'true');
        }

        function removeEmptyRow() {
            var e = document.getElementById('sw-empty-row');
            if (e) e.remove();
        }

        function showEmptyRow() {
            var tr = document.createElement('tr');
            tr.id = 'sw-empty-row';
            tr.innerHTML = '<td colspan="5" class="sw-empty-msg">' + escHtml(EMPTY_MSG) + '</td>';
            tbody.appendChild(tr);
        }

        var TRASH_BTN =
            '<button type="button" class="sw-delete-btn" title="' + escAttr(DELETE_LABEL) + '">' +
            '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' +
            '<polyline points="3 6 5 6 21 6"/>' +
            '<path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/>' +
            '<path d="M10 11v6"/><path d="M14 11v6"/>' +
            '<path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/>' +
            '</svg></button>';

        function buildRow(idx, key, iconUrl, label, url, ph) {
            var tr = document.createElement('tr');
            tr.className = 'sw-row';
            tr.innerHTML =
                '<td class="sw-handle">&#9776;</td>' +
                '<td class="sw-icon-cell"><img class="sw-admin-icon" src="' + escAttr(iconUrl) + '" alt="" loading="lazy" decoding="async"></td>' +
                '<td>' +
                    '<input type="hidden" name="messengers[' + idx + '][key]" value="' + escAttr(key) + '">' +
                    '<input type="text" name="messengers[' + idx + '][label]" value="' + escAttr(label) + '" class="sw-label-input">' +
                '</td>' +
                '<td><input type="text" name="messengers[' + idx + '][url]" value="' + escAttr(url) + '" placeholder="' + escAttr(ph) + '" class="sw-url-input"></td>' +
                '<td class="sw-delete-cell">' + TRASH_BTN + '</td>';
            return tr;
        }

        function escAttr(str) {
            return String(str).replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
        }
        function escHtml(str) {
            return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
        }
    })();
    </script>
    <?php
}
