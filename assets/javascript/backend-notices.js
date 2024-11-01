(function($, config) {
    $(function() {
        $(document.body).on('click', '.wc-unzer-direct-notice .notice-dismiss', function() {
            $.post(config.flush)
        });
    });
})(jQuery, window.wcqpBackendNotices || {});
