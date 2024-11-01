<?php
/**
 * Admin View: Notice - Update
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

?>
<div id="woocommerce-upgrade-notice" class="updated woocommerce-message wc-connect">
    <h3><strong><?php _e( 'WC Unzer Direct - Data Update', 'wc-unzer-direct' ); ?></strong></h3>
    <p><?php _e( 'To ensure you get the best experience at all times, we need to update your store\'s database to the latest version.', 'wc-unzer-direct' ); ?></p>
    <p class="submit"><a href="#" class="wc-unzer-direct-update-now button-primary"><?php _e( 'Run the updater', 'wc-unzer-direct' ); ?></a></p>
</div>
<script type="text/javascript">
    (function ($) {
        $( '.wc-unzer-direct-update-now' ).click( 'click', function() {
            var confirm = window.confirm( '<?php echo esc_js( __( 'It is strongly recommended that you backup your database before proceeding. Are you sure you wish to run the updater now?', 'wc-unzer-direct' ) ); ?>' ); // jshint ignore:line

            if (confirm) {
                var message = $('#woocommerce-upgrade-notice');

                message.find('p').fadeOut();

                $.post('<?php echo admin_url('admin-ajax.php'); ?>', {
                    action: 'unzer_direct_run_data_upgrader',
                    nonce: '<?php echo WC_UnzerDirect_Install::create_run_upgrader_nonce(); ?>'
                }, function () {
                    message.append($('<p></p>').text("<?php _e('The upgrader is now running. This might take a while. The notice will disappear once the upgrade is complete.', 'wc-unzer-direct'); ?>"));
                });
            }
        });
    })(jQuery);
</script>
