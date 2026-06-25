(function ($) {
	'use strict';

	var $overlay = $('#eqb-overlay');
	var $content = $('#eqb-popup-content');
	var $loading = $overlay.find('.eqb-popup-loading');
	var STORAGE_KEY_PROVINCE = 'eqb_ma_tinh';
	var STORAGE_KEY_WARD = 'eqb_ma_xa';
	var STORAGE_KEY_CUSTOMER = 'eqb_customer';
	var CUSTOMER_FIELDS = ['name', 'phone', 'email', 'address', 'note'];

	function getStoredValue(key) {
		try {
			return localStorage.getItem(key) || '';
		} catch (e) {
			return '';
		}
	}

	function setStoredValue(key, value) {
		try {
			if (value) {
				localStorage.setItem(key, value);
			} else {
				localStorage.removeItem(key);
			}
		} catch (e) {
			// Ignore storage errors (private mode, quota, etc.).
		}
	}

	function getStoredCustomer() {
		try {
			var raw = localStorage.getItem(STORAGE_KEY_CUSTOMER);
			return raw ? JSON.parse(raw) : {};
		} catch (e) {
			return {};
		}
	}

	function setStoredCustomerField(field, value) {
		var data = getStoredCustomer();
		value = $.trim(value || '');

		if (value) {
			data[field] = value;
		} else {
			delete data[field];
		}

		try {
			if ($.isEmptyObject(data)) {
				localStorage.removeItem(STORAGE_KEY_CUSTOMER);
			} else {
				localStorage.setItem(STORAGE_KEY_CUSTOMER, JSON.stringify(data));
			}
		} catch (e) {
			// Ignore storage errors (private mode, quota, etc.).
		}
	}

	function restoreSavedCustomer($popup) {
		var data = getStoredCustomer();

		$.each(CUSTOMER_FIELDS, function (i, field) {
			if (data[field]) {
				$popup.find('[name="' + field + '"]').val(data[field]);
			}
		});
	}

	function saveCustomerFromForm($form) {
		$.each(CUSTOMER_FIELDS, function (i, field) {
			setStoredCustomerField(field, $form.find('[name="' + field + '"]').val());
		});
	}

	function openOverlay() {
		$overlay.removeClass('eqb-hidden').attr('aria-hidden', 'false');
		$('body').addClass('eqb-modal-open');
	}

	function closeOverlay() {
		$overlay.addClass('eqb-hidden').attr('aria-hidden', 'true');
		$content.empty();
		$('body').removeClass('eqb-modal-open');
	}

	function showLoading(show) {
		$loading.toggleClass('eqb-hidden', !show);
	}

	function updateTotal($popup) {
		var unit = parseFloat($popup.data('unit-price')) || 0;
		var qty = parseInt($popup.find('[data-eqb-qty]').val(), 10) || 1;
		var $total = $popup.find('[data-eqb-total]');
		if (!$total.length) {
			return;
		}
		var total = unit * qty;
		var formatted = total.toLocaleString('vi-VN');
		var suffix = $total.data('suffix') || 'đ';
		if (!$total.data('suffix')) {
			var txt = $total.text();
			var m = txt.match(/[^\d.,\s]+$/);
			if (m) {
				suffix = $.trim(m[0]);
				$total.data('suffix', suffix);
			}
		}
		$total.text(formatted + ' ' + suffix);
	}

	function initVariations($popup) {
		var $varForm = $popup.find('.eqb-variations');
		if (!$varForm.length || typeof $.fn.wc_variation_form !== 'function') {
			return;
		}

		$varForm.wc_variation_form();

		$varForm.on('found_variation', function (event, variation) {
			$popup.find('[data-eqb-variation-id]').val(variation.variation_id || 0);
			if (variation.price_html) {
				$popup.find('[data-eqb-price]').html(variation.price_html);
			}
			if (typeof variation.display_price !== 'undefined') {
				$popup.data('unit-price', parseFloat(variation.display_price) || 0);
				updateTotal($popup);
			}
		});

		$varForm.on('reset_data hide_variation', function () {
			$popup.find('[data-eqb-variation-id]').val(0);
		});
	}

	function loadWards($popup, maTinh, selectedWard) {
		var $ward = $popup.find('[data-eqb-ward]');
		$ward.prop('disabled', true).html(
			'<option value="">' + eqb_vars.i18n.loadingWards + '</option>'
		);

		if (!maTinh) {
			$ward.html('<option value="">' + eqb_vars.i18n.selectWard + '</option>');
			return;
		}

		$.post(eqb_vars.ajax_url, {
			action: 'eqb_get_wards',
			nonce: eqb_vars.nonce,
			ma_tinh: maTinh
		})
			.done(function (res) {
				var html = '<option value="">' + eqb_vars.i18n.selectWard + '</option>';
				if (res.success && res.data.wards) {
					$.each(res.data.wards, function (i, w) {
						html += '<option value="' + w.ma_xa + '">' + w.ten_xa + '</option>';
					});
				}
				$ward.html(html).prop('disabled', false);
				if (selectedWard && $ward.find('option[value="' + selectedWard + '"]').length) {
					$ward.val(selectedWard);
				}
			})
			.fail(function () {
				$ward.html('<option value="">' + eqb_vars.i18n.error + '</option>');
			});
	}

	function restoreSavedAddress($popup) {
		var savedProvince = getStoredValue(STORAGE_KEY_PROVINCE);
		var savedWard = getStoredValue(STORAGE_KEY_WARD);
		var $province = $popup.find('[data-eqb-province]');

		if (!savedProvince || !$province.find('option[value="' + savedProvince + '"]').length) {
			return;
		}

		$province.val(savedProvince);
		loadWards($popup, savedProvince, savedWard);
	}

	function bindPopupEvents() {
		var $popup = $content.find('.eqb-popup');
		if (!$popup.length) {
			return;
		}

		initVariations($popup);
		restoreSavedCustomer($popup);
		restoreSavedAddress($popup);

		$popup.on('click', '[data-eqb-close]', function (e) {
			e.preventDefault();
			closeOverlay();
		});

		$popup.on('click', '[data-eqb-qty-minus]', function () {
			var $input = $popup.find('[data-eqb-qty]');
			var val = Math.max(1, (parseInt($input.val(), 10) || 1) - 1);
			$input.val(val);
			updateTotal($popup);
		});

		$popup.on('click', '[data-eqb-qty-plus]', function () {
			var $input = $popup.find('[data-eqb-qty]');
			var val = (parseInt($input.val(), 10) || 1) + 1;
			$input.val(val);
			updateTotal($popup);
		});

		$popup.on('change input', '[data-eqb-qty]', function () {
			var val = Math.max(1, parseInt($(this).val(), 10) || 1);
			$(this).val(val);
			updateTotal($popup);
		});

		$popup.on('change', '[data-eqb-province]', function () {
			var maTinh = $(this).val();
			setStoredValue(STORAGE_KEY_PROVINCE, maTinh);
			setStoredValue(STORAGE_KEY_WARD, '');
			loadWards($popup, maTinh);
		});

		$popup.on('change', '[data-eqb-ward]', function () {
			setStoredValue(STORAGE_KEY_WARD, $(this).val());
		});

		$popup.on('input change', '[name="name"], [name="phone"], [name="email"], [name="address"], [name="note"]', function () {
			setStoredCustomerField(this.name, $(this).val());
		});

		var $form = $popup.find('[data-eqb-form]');
		$form.on('change', '[data-eqb-consent]', function () {
			var $source = $form.find('[data-eqb-hp-source]');
			var $btn = $form.find('.eqb-form__submit');
			var checked = $(this).is(':checked');

			if ($source.length) {
				$source.val(checked ? window.location.href : '');
			}
			$btn.toggleClass('eqb-form__submit--ready', checked);
			if (checked) {
				$form.find('.eqb-form__consent').removeClass('eqb-form__consent--error');
				$form.find('[data-eqb-message]').addClass('eqb-hidden').removeClass('eqb-form__message--success eqb-form__message--error');
			}
		});

		$popup.on('submit', '[data-eqb-form]', function (e) {
			e.preventDefault();
			submitOrder($popup, $(this));
		});
	}

	function lockOrderForm($form) {
		$('.eqb-order-form-ui').addClass('eqb-hidden');
		$form.find('input, select, textarea, button').not('[data-eqb-done-close]').prop('disabled', true);
		$form.find('[data-eqb-done-close]').removeClass('eqb-hidden');
	}

	function submitOrder($popup, $form) {
		var $msg = $form.find('[data-eqb-message]');
		var $btn = $form.find('.eqb-form__submit');
		var $consent = $form.find('[data-eqb-consent]');

		$msg.addClass('eqb-hidden').removeClass('eqb-form__message--success eqb-form__message--error');
		$form.find('.eqb-form__consent').removeClass('eqb-form__consent--error');

		if (!$consent.is(':checked')) {
			$msg.removeClass('eqb-hidden').addClass('eqb-form__message--error').text(eqb_vars.i18n.consentRequired);
			$form.find('.eqb-form__consent').addClass('eqb-form__consent--error');
			$consent.trigger('focus');
			return;
		}

		var name = $.trim($form.find('[name="name"]').val());
		var phone = $.trim($form.find('[name="phone"]').val());
		var maTinh = $form.find('[name="ma_tinh"]').val();
		var maXa = $form.find('[name="ma_xa"]').val();
		var variationId = parseInt($form.find('[data-eqb-variation-id]').val(), 10) || 0;

		if (!name || !phone || !maTinh || !maXa) {
			$msg.removeClass('eqb-hidden').addClass('eqb-form__message--error').text(eqb_vars.i18n.required);
			return;
		}

		if ($popup.data('is-variable') === 1 || $popup.data('is-variable') === '1') {
			if (variationId <= 0) {
				$msg.removeClass('eqb-hidden').addClass('eqb-form__message--error').text(eqb_vars.i18n.selectVariant);
				return;
			}
		}

		if (!$form[0].checkValidity()) {
			$form[0].reportValidity();
			return;
		}

		$btn.prop('disabled', true);
		$msg.removeClass('eqb-hidden eqb-form__message--error').addClass('eqb-form__message--success').text(eqb_vars.i18n.submitting);

		setStoredValue(STORAGE_KEY_PROVINCE, maTinh);
		setStoredValue(STORAGE_KEY_WARD, maXa);
		saveCustomerFromForm($form);

		var postData = {
			action: 'eqb_create_order',
			nonce: eqb_vars.nonce,
			product_id: $popup.data('product-id'),
			variation_id: variationId,
			quantity: $popup.find('[data-eqb-qty]').val() || 1,
			name: name,
			phone: phone,
			email: $form.find('[name="email"]').val() || '',
			ma_tinh: maTinh,
			ma_xa: maXa,
			address: $form.find('[name="address"]').val() || '',
			note: $form.find('[name="note"]').val() || '',
			payment_method: $form.find('[name="payment_method"]:checked').val() || ''
		};

		var $hp = $form.find('[data-eqb-hp]');
		if ($hp.length) {
			postData[$hp.attr('name')] = $hp.val();
		}

		var $hpSource = $form.find('[data-eqb-hp-source]');
		if ($hpSource.length) {
			postData[$hpSource.attr('name')] = $hpSource.val();
		}

		postData[$consent.attr('name')] = $consent.val();

		if (eqb_vars.debug_note === '1') {
			postData.debug_page_url = window.location.href;
			postData.debug_referrer = document.referrer || '';
			postData.debug_screen = window.screen ? (window.screen.width + 'x' + window.screen.height) : '';
			try {
				postData.debug_timezone = Intl.DateTimeFormat().resolvedOptions().timeZone || '';
			} catch (e) {
				postData.debug_timezone = '';
			}
		}

		$.post(eqb_vars.ajax_url, postData)
			.done(function (res) {
				if (!res.success) {
					var err = (res.data && res.data.message) ? res.data.message : eqb_vars.i18n.error;
					$msg.removeClass('eqb-form__message--success').addClass('eqb-form__message--error').text(err);
					return;
				}

				$msg.removeClass('eqb-hidden eqb-form__message--error').addClass('eqb-form__message--success').text(res.data.message);
				lockOrderForm($form);

				if (res.data.redirect_url) {
					setTimeout(function () {
						window.location.href = res.data.redirect_url;
					}, 800);
				}
			})
			.fail(function (xhr) {
				var err = eqb_vars.i18n.error;
				if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
					err = xhr.responseJSON.data.message;
				}
				$msg.removeClass('eqb-form__message--success').addClass('eqb-form__message--error').text(err);
			})
			.always(function () {
				if (!$msg.hasClass('eqb-form__message--success')) {
					$btn.prop('disabled', false);
				}
			});
	}

	$(document).on('click', '.eqb-buy-btn', function () {
		var productId = $(this).data('product-id');
		if (!productId) {
			return;
		}

		openOverlay();
		showLoading(true);
		$content.empty();

		$.post(eqb_vars.ajax_url, {
			action: 'eqb_load_popup',
			nonce: eqb_vars.nonce,
			product_id: productId
		})
			.done(function (res) {
				if (!res.success || !res.data.html) {
					$content.html('<p class="eqb-form__message eqb-form__message--error">' + eqb_vars.i18n.error + '</p>');
					return;
				}
				$content.html(res.data.html);
				bindPopupEvents();
			})
			.fail(function () {
				$content.html('<p class="eqb-form__message eqb-form__message--error">' + eqb_vars.i18n.error + '</p>');
			})
			.always(function () {
				showLoading(false);
			});
	});

})(jQuery);
