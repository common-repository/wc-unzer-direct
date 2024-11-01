( function( $ ) {
	"use strict";

	UnzerDirect.prototype.init = function() {
		// Add event handlers
		this.actionBox.on( 'click', '[data-action]', $.proxy( this.callAction, this ) );
	};

	UnzerDirect.prototype.callAction = function( e ) {
		e.preventDefault();
		var target = $( e.target );
		var action = target.attr( 'data-action' );

		if( typeof this[action] !== 'undefined' ) {
			var message = target.attr('data-confirm') || 'Are you sure you want to continue?';
			if( confirm( message ) ) {
				this[action]();
			}
		}
	};

	UnzerDirect.prototype.capture = function() {
		this.request( {
			unzer_direct_action : 'capture'
		} );
	};

	UnzerDirect.prototype.captureAmount = function () {
		this.request({
			unzer_direct_action: 'capture',
			unzer_direct_amount: $('#unzer-direct-balance__amount-field').val()
		} );
	};

	UnzerDirect.prototype.cancel = function() {
		this.request( {
			unzer_direct_action : 'cancel'
		} );
	};

	UnzerDirect.prototype.refund = function() {
		this.request( {
			unzer_direct_action : 'refund'
		} );
	};

	UnzerDirect.prototype.split_capture = function() {
		this.request( {
			unzer_direct_action : 'splitcapture',
			amount : parseFloat( $('#unzer_direct_split_amount').val() ),
			finalize : 0
		} );
	};

	UnzerDirect.prototype.split_finalize = function() {
		this.request( {
			unzer_direct_action : 'splitcapture',
			amount : parseFloat( $('#unzer_direct_split_amount').val() ),
			finalize : 1
		} );
	};

	UnzerDirect.prototype.request = function( dataObject ) {
		var that = this;
		var request = $.ajax( {
			type : 'POST',
			url : ajaxurl,
			dataType: 'json',
			data : $.extend( {}, { action : 'unzer_direct_manual_transaction_actions', post : this.postID.val() }, dataObject ),
			beforeSend : $.proxy( this.showLoader, this, true ),
			success : function() {
				$.get( window.location.href, function( data ) {
					var newData = $(data).find( '#' + that.actionBox.attr( 'id' ) + ' .inside' ).html();
					that.actionBox.find( '.inside' ).html( newData );
					that.showLoader( false );
				} );
			},
			error : function(jqXHR, textStatus, errorThrown) {
				alert(jqXHR.responseText);
				that.showLoader( false );
			}
		} );

		return request;
	};

	UnzerDirect.prototype.showLoader = function( e, show ) {
		if( show ) {
			this.actionBox.append( this.loaderBox );
		} else {
			this.actionBox.find( this.loaderBox ).remove();
		}
	};

    UnzerDirectCheckAPIStatus.prototype.init = function () {
    	if (this.apiSettingsField.length) {
			$(window).on('load', $.proxy(this.pingAPI, this));
			this.apiSettingsField.on('blur', $.proxy(this.pingAPI, this));
			this.insertIndicator();
		}
	};

	UnzerDirectCheckAPIStatus.prototype.insertIndicator = function () {
		this.indicator.insertAfter(this.apiSettingsField.hide().fadeIn());
	};

	UnzerDirectCheckAPIStatus.prototype.pingAPI = function () {
		$.post(ajaxurl, { action: 'unzer_direct_ping_api', api_key: this.apiSettingsField.val() }, $.proxy(function (response) {
			if (response.status === 'success') {
				this.indicator.addClass('ok').removeClass('error');
			} else {
				this.indicator.addClass('error').removeClass('ok');
			}
		}, this), "json");
	};

	// DOM ready
	$(function() {
		new UnzerDirect().init();
		new UnzerDirectCheckAPIStatus().init();
		new UnzerDirectPrivateKey().init();

		function unzerInsertAjaxResponseMessage(response) {
			if (response.hasOwnProperty('status') && response.status == 'success') {
				var message = $('<div id="message" class="updated"><p>' + response.message + '</p></div>');
				message.hide();
				message.insertBefore($('#wc_unzer_direct_wiki'));
				message.fadeIn('fast', function () {
					setTimeout(function () {
						message.fadeOut('fast', function () {
							message.remove();
						});
					},5000);
				});
			}
		}

        var emptyLogsButton = $('#wc_unzer_direct_logs_clear');
        emptyLogsButton.on('click', function(e) {
        	e.preventDefault();
        	emptyLogsButton.prop('disabled', true);
        	$.getJSON(ajaxurl, { action: 'unzer_direct_empty_logs' }, function (response) {
				unzerInsertAjaxResponseMessage(response);
				emptyLogsButton.prop('disabled', false);
        	});
        });

        var flushCacheButton = $('#wc_unzer_direct_flush_cache');
		flushCacheButton.on('click', function(e) {
        	e.preventDefault();
			flushCacheButton.prop('disabled', true);
        	$.getJSON(ajaxurl, { action: 'unzer_direct_flush_cache' }, function (response) {
				unzerInsertAjaxResponseMessage(response);
				flushCacheButton.prop('disabled', false);
        	});
        });
	});

	function UnzerDirect() {
		this.actionBox 	= $( '#unzer-direct-payment-actions' );
		this.postID		= $( '#post_ID' );
		this.loaderBox 	= $( '<div class="loader"></div>');
	}

    function UnzerDirectCheckAPIStatus() {
    	this.apiSettingsField = $('#wc_unzer_direct_unzer_direct_apikey');
		this.indicator = $('<span class="wc_unzer_direct_api_indicator"></span>');
	}

	function UnzerDirectPrivateKey() {
		this.field = $('#wc_unzer_direct_unzer_direct_privatekey');
		this.apiKeyField = $('#wc_unzer_direct_unzer_direct_apikey');
		this.refresh = $('<span class="wc_unzer_direct_api_indicator refresh"></span>');
	}

	UnzerDirectPrivateKey.prototype.init = function () {
		var self = this;
		this.field.parent().append(this.refresh.hide());

		this.refresh.on('click', function() {
			if ( ! self.refresh.hasClass('ok')) {
				self.refresh.addClass('is-loading');
				$.post(ajaxurl + '?action=unzer_direct_fetch_private_key', { api_key: self.apiKeyField.val() }, function(response) {
					if (response.status === 'success') {
						self.field.val(response.data.private_key);
						self.refresh.removeClass('refresh').addClass('ok');
					} else {
						self.flashError(response.message);
					}

					self.refresh.removeClass('is-loading');
				}, 'json');
			}
		});

		this.validatePrivateKey();
	}

	UnzerDirectPrivateKey.prototype.validatePrivateKey = function() {
		var self = this;
		$.post(ajaxurl + '?action=unzer_direct_fetch_private_key', { api_key: self.apiKeyField.val() }, function(response) {
			if (response.status === 'success' && self.field.val() === response.data.private_key) {
				self.refresh.removeClass('refresh').addClass('ok');
			}

			self.refresh.fadeIn();
		}, 'json');
	};

	UnzerDirectPrivateKey.prototype.flashError = function (message) {
		var message = $('<div style="color: red; font-style: italic;"><p style="font-size: 12px;">' + message + '</p></div>');
		message.hide().insertAfter(this.refresh).fadeIn('fast', function() {
			setTimeout(function () {
				message.fadeOut('fast', function() {
					message.remove();
				})
			}, 10000)
		});
	}

})(jQuery);
