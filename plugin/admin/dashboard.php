<?php
if ( ! defined( 'ABSPATH' ) ) exit;

function sw_page_dashboard() {
    if ( ! current_user_can( 'manage_options' ) ) return;

    global $wpdb;
    $table = $wpdb->prefix . 'sw_stats';

    if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) !== $table ) {
        echo '<div class="wrap"><h1>' . esc_html( sw_t( 'admin.dashboard_title' ) ) . '</h1>';
        echo '<div class="notice notice-error"><p>' . esc_html( sw_t( 'admin.stats_table_missing' ) ) . '</p></div></div>';
        return;
    }

    $total_views  = (int) $wpdb->get_var( "SELECT COUNT(*) FROM `{$table}` WHERE type = 'view'" );
    $total_clicks = (int) $wpdb->get_var( "SELECT COUNT(*) FROM `{$table}` WHERE type = 'click'" );

    $clicks_by_messenger = $wpdb->get_results(
        "SELECT messenger, COUNT(*) as cnt FROM `{$table}`
         WHERE type = 'click' AND messenger IS NOT NULL AND messenger != ''
         GROUP BY messenger ORDER BY cnt DESC",
        ARRAY_A
    );

    $since = gmdate( 'Y-m-d', strtotime( '-29 days' ) ) . ' 00:00:00';
    $daily_rows = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT DATE(created_at) as day, type, COUNT(*) as cnt
             FROM `{$table}` WHERE created_at >= %s
             GROUP BY DATE(created_at), type ORDER BY day ASC",
            $since
        ),
        ARRAY_A
    );

    $chart_data = [];
    for ( $i = 29; $i >= 0; $i-- ) {
        $chart_data[ gmdate( 'Y-m-d', strtotime( "-{$i} days" ) ) ] = [ 'views' => 0, 'clicks' => 0 ];
    }
    foreach ( $daily_rows as $row ) {
        if ( isset( $chart_data[ $row['day'] ] ) ) {
            $key = $row['type'] === 'view' ? 'views' : 'clicks';
            $chart_data[ $row['day'] ][ $key ] = (int) $row['cnt'];
        }
    }

    $max_val = 0;
    foreach ( $chart_data as $d ) {
        $max_val = max( $max_val, $d['views'], $d['clicks'] );
    }

    $chart_max_px = 100;
    $messengers_config = sw_get_messengers_config();
    ?>
    <div class="wrap sw-admin sw-dashboard">
        <h1><?php echo esc_html( sw_t( 'admin.dashboard_title' ) ); ?></h1>

        <div class="sw-stats-cards">
            <div class="sw-stat-card">
                <div class="sw-stat-value"><?php echo number_format( $total_views ); ?></div>
                <div class="sw-stat-label"><?php echo esc_html( sw_t( 'admin.total_views' ) ); ?></div>
            </div>
            <div class="sw-stat-card">
                <div class="sw-stat-value"><?php echo number_format( $total_clicks ); ?></div>
                <div class="sw-stat-label"><?php echo esc_html( sw_t( 'admin.total_clicks' ) ); ?></div>
            </div>
        </div>

        <div class="sw-card">
            <h2><?php echo esc_html( sw_t( 'admin.last_30_days' ) ); ?></h2>
            <?php if ( $max_val === 0 ) : ?>
                <p class="sw-no-data"><?php echo esc_html( sw_t( 'admin.no_data' ) ); ?></p>
            <?php else : ?>
            <div class="sw-chart-legend">
                <span class="sw-leg sw-leg-v"><?php echo esc_html( sw_t( 'admin.views' ) ); ?></span>
                <span class="sw-leg sw-leg-c"><?php echo esc_html( sw_t( 'admin.clicks' ) ); ?></span>
            </div>
            <div class="sw-chart-wrap">
                <div class="sw-chart-bars">
                    <?php foreach ( $chart_data as $day => $vals ) :
                        $v_px = (int) round( $vals['views']  / $max_val * $chart_max_px );
                        $c_px = (int) round( $vals['clicks'] / $max_val * $chart_max_px );
                        $v_tip = esc_attr( sw_t( 'admin.views' )  . ': ' . $vals['views'] );
                        $c_tip = esc_attr( sw_t( 'admin.clicks' ) . ': ' . $vals['clicks'] );
                    ?>
                    <div class="sw-chart-col">
                        <div class="sw-bar-pair">
                            <div class="sw-bar sw-bar-v" style="height:<?php echo $v_px; ?>px" title="<?php echo $v_tip; ?>"></div>
                            <div class="sw-bar sw-bar-c" style="height:<?php echo $c_px; ?>px" title="<?php echo $c_tip; ?>"></div>
                        </div>
                        <span class="sw-bar-label"><?php echo esc_html( gmdate( 'd.m', strtotime( $day ) ) ); ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <?php if ( ! empty( $clicks_by_messenger ) ) : ?>
        <div class="sw-card">
            <h2><?php echo esc_html( sw_t( 'admin.clicks_by_messenger' ) ); ?></h2>
            <div class="sw-ms-stats">
                <?php
                $max_cnt = max( array_column( $clicks_by_messenger, 'cnt' ) );
                foreach ( $clicks_by_messenger as $row ) :
                    $key   = $row['messenger'];
                    $label = $messengers_config[ $key ]['label'] ?? $key;
                    $icon  = sw_get_messenger_icon_html( $messengers_config[ $key ] ?? [], 'sw-dashboard-icon' );
                    $pct   = $max_cnt > 0 ? (int) round( $row['cnt'] / $max_cnt * 100 ) : 0;
                ?>
                <div class="sw-ms-row">
                    <div class="sw-ms-icon"><?php echo $icon; ?></div>
                    <div class="sw-ms-name"><?php echo esc_html( $label ); ?></div>
                    <div class="sw-ms-bar-wrap"><div class="sw-ms-bar" style="width:<?php echo $pct; ?>%"></div></div>
                    <div class="sw-ms-count"><?php echo (int) $row['cnt']; ?></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <style>
    .sw-dashboard .sw-stats-cards{display:flex;gap:16px;margin-bottom:20px;flex-wrap:wrap;}
    .sw-stat-card{background:#fff;border:1px solid #c3c4c7;border-radius:4px;padding:20px 32px;min-width:160px;text-align:center;}
    .sw-stat-value{font-size:40px;font-weight:700;color:#2271b1;line-height:1;}
    .sw-stat-label{font-size:13px;color:#646970;margin-top:6px;}

    .sw-admin .sw-card{background:#fff;border:1px solid #c3c4c7;border-radius:4px;padding:1px 20px 20px;margin-bottom:20px;}
    .sw-admin .sw-card h2{font-size:14px;font-weight:600;margin-bottom:12px;}
    .sw-no-data{color:#999;font-style:italic;padding:8px 0;}

    .sw-chart-legend{display:flex;gap:16px;margin-bottom:10px;font-size:12px;}
    .sw-leg{display:flex;align-items:center;gap:5px;}
    .sw-leg::before{content:'';display:inline-block;width:12px;height:12px;border-radius:2px;}
    .sw-leg-v::before{background:#2271b1;}
    .sw-leg-c::before{background:#f0a500;}

    .sw-chart-wrap{overflow-x:auto;}
    .sw-chart-bars{display:flex;align-items:flex-end;gap:3px;height:120px;border-bottom:2px solid #e0e0e0;padding-bottom:0;min-width:560px;}
    .sw-chart-col{display:flex;flex-direction:column;align-items:center;justify-content:flex-end;flex:1;min-width:14px;height:100%;}
    .sw-bar-pair{display:flex;align-items:flex-end;gap:1px;width:100%;}
    .sw-bar{flex:1;min-height:2px;border-radius:2px 2px 0 0;transition:opacity .15s;}
    .sw-bar:hover{opacity:.75;}
    .sw-bar-v{background:#2271b1;}
    .sw-bar-c{background:#f0a500;}
    .sw-bar-label{font-size:9px;color:#aaa;margin-top:3px;white-space:nowrap;}

    .sw-ms-stats{display:flex;flex-direction:column;gap:8px;}
    .sw-ms-row{display:flex;align-items:center;gap:12px;padding:4px 0;}
    .sw-ms-icon{width:32px;height:32px;flex-shrink:0;display:flex;align-items:center;justify-content:center;}
    .sw-dashboard-icon{width:32px;height:32px;display:block;object-fit:contain;}
    .sw-ms-name{width:110px;font-size:13px;font-weight:500;}
    .sw-ms-bar-wrap{flex:1;height:14px;background:#f0f0f0;border-radius:7px;overflow:hidden;}
    .sw-ms-bar{height:100%;background:#f0a500;border-radius:7px;transition:width .3s ease;}
    .sw-ms-count{width:40px;text-align:right;font-size:13px;font-weight:600;color:#444;}
    </style>
    <?php
}
