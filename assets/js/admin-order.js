(function ($) {
	'use strict';

	var boundPairs = {};

	function refreshSelect($select) {
		if (!$select.length) {
			return;
		}

		if ($select.hasClass('select2-hidden-accessible')) {
			$select.trigger('change.select2');
		}
	}

	function loadWards($state, $city, selectedWard) {
		var maTinh = $state.val();

		if (!maTinh) {
			$city.html('<option value="">' + eqb_admin.i18n.selectWard + '</option>');
			refreshSelect($city);
			return;
		}

		$city.prop('disabled', true);

		$.post(eqb_admin.ajax_url, {
			action: 'eqb_admin_get_wards',
			nonce: eqb_admin.nonce,
			ma_tinh: maTinh
		})
			.done(function (res) {
				var html = '<option value="">' + eqb_admin.i18n.selectWard + '</option>';

				if (res.success && res.data.wards) {
					$.each(res.data.wards, function (i, ward) {
						html += '<option value="' + ward.ma_xa + '">' + ward.ten_xa + '</option>';
					});
				}

				$city.html(html).prop('disabled', false);

				if (selectedWard && $city.find('option[value="' + selectedWard + '"]').length) {
					$city.val(selectedWard);
				}

				refreshSelect($city);
			})
			.fail(function () {
				$city.html('<option value="">' + eqb_admin.i18n.error + '</option>').prop('disabled', false);
				refreshSelect($city);
			});
	}

	function bindAddressPair(stateSelector, citySelector) {
		var key = stateSelector + '|' + citySelector;
		var $state = $(stateSelector);
		var $city = $(citySelector);

		if (!$state.length || !$city.length || !$state.is('select') || !$city.is('select')) {
			return;
		}

		if (!boundPairs[key]) {
			boundPairs[key] = true;

			$state.on('change.eqbAdmin', function () {
				loadWards($state, $city, '');
			});
		}

		var savedWard = $city.data('eqb-saved-ward') || $city.val();
		if ($state.val() && $city.find('option').length <= 1) {
			loadWards($state, $city, savedWard);
		}
	}

	function initAddressPairs() {
		$('#_billing_city, #_shipping_city').each(function () {
			$(this).data('eqb-saved-ward', $(this).val());
		});

		bindAddressPair('#_billing_state', '#_billing_city');
		bindAddressPair('#_shipping_state', '#_shipping_city');
	}

	var ipv4Pattern = /^(?:(?:25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)\.){3}(?:25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)$/;
	var ipv6Pattern = /^(?:(?:[0-9a-fA-F]{1,4}:){7}[0-9a-fA-F]{1,4}|(?:[0-9a-fA-F]{1,4}:){1,7}:|(?:[0-9a-fA-F]{1,4}:){1,6}:[0-9a-fA-F]{1,4}|(?:[0-9a-fA-F]{1,4}:){1,5}(?::[0-9a-fA-F]{1,4}){1,2}|(?:[0-9a-fA-F]{1,4}:){1,4}(?::[0-9a-fA-F]{1,4}){1,3}|(?:[0-9a-fA-F]{1,4}:){1,3}(?::[0-9a-fA-F]{1,4}){1,4}|(?:[0-9a-fA-F]{1,4}:){1,2}(?::[0-9a-fA-F]{1,4}){1,5}|[0-9a-fA-F]{1,4}:(?::[0-9a-fA-F]{1,4}){1,6}|:(?::[0-9a-fA-F]{1,4}){1,7}|::(?:[fF]{4}:)?(?:(?:25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)\.){3}(?:25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)|(?:[0-9a-fA-F]{1,4}:){1,4}:(?:(?:25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)\.){3}(?:25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d))$/;

	function isValidIp(ip) {
		return ipv4Pattern.test(ip) || ipv6Pattern.test(ip);
	}

	function linkCustomerIp() {
		$('.woocommerce-Order-customerIP').each(function () {
			var $el = $(this);

			if ($el.data('eqb-ip-linked') || $el.find('a[href*="ip.echbay.com"]').length) {
				return;
			}

			var text = $.trim($el.text());
			if (!text) {
				return;
			}

			var match = text.match(/\b((?:(?:25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)\.){3}(?:25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)|(?:[0-9a-fA-F]{0,4}:){2,7}[0-9a-fA-F]{0,4})\b/);
			if (!match || !isValidIp(match[1])) {
				return;
			}

			var ip = match[1];
			var url = 'https://ip.echbay.com/?ip=' + encodeURIComponent(ip);
			var html = $el.html();
			var linked = '<a href="' + url + '" target="_blank" rel="nofollow noopener noreferrer">' + ip + '</a>';

			$el.html(html.replace(ip, linked));
			$el.data('eqb-ip-linked', true);
		});
	}

	$(function () {
		initAddressPairs();
		linkCustomerIp();

		// WooCommerce tái tạo field state khi đổi country.
		$(document.body).on('change', '#_billing_country, #_shipping_country', function () {
			setTimeout(initAddressPairs, 300);
		});

		setTimeout(initAddressPairs, 500);
		setTimeout(linkCustomerIp, 500);

		// WooCommerce HPOS có thể render panel đơn hàng sau khi DOM ready.
		if (window.MutationObserver) {
			var ipLinkTimer;

			var ipObserver = new MutationObserver(function () {
				clearTimeout(ipLinkTimer);
				ipLinkTimer = setTimeout(linkCustomerIp, 150);
			});

			ipObserver.observe(document.body, { childList: true, subtree: true });
		}
	});
})(jQuery);
