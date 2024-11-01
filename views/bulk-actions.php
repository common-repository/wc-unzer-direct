<script type="text/javascript">
	jQuery( document ).ready( function() {
		jQuery( '<option>' ).val( 'unzer_direct_capture_recurring' ).text( '<?php _e( 'Capture payment and activate subscription', 'wc-unzer-direct' ); ?>' ).appendTo( "select[name='action']" );

		jQuery("select[name='action']").on('change', function () {
			if (this.value  === 'unzer_direct_capture_recurring') {
				jQuery(this).closest('form').attr('target', '_blank');
			}
		});
	} );
</script>
