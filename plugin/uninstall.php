cat > plugin/uninstall.php << 'EOF'
<?php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) exit;

delete_option( 'sw_general_settings' );
delete_option( 'sw_messengers' );
EOF