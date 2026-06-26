(function ($) {
	'use strict';

	var STORAGE_KEY_PROVINCE = 'eqb_ma_tinh';
	var STORAGE_KEY_WARD = 'eqb_ma_xa';

	var fieldOrder = [
		'first_name',
		'phone',
		'email',
		'state',
		'city',
		'address_1'
	];

	var wardLoadSeq = {};
	var initTimer = null;
	var provinceRetryTimers = {};

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

	function fixCountry(prefix) {
		var $country = $('#' + prefix + '_country');
		if ($country.length && $country.val() !== 'VN') {
			$country.val('VN');
		}
	}

	function refreshSelect($select) {
		if (!$select.length) {
			return;
		}

		if ($select.hasClass('select2-hidden-accessible')) {
			$select.trigger('change.select2');
		}
	}

	function stateHasProvinceOption($state, provinceCode) {
		return !!(provinceCode && $state.length && $state.find('option[value="' + provinceCode + '"]').length);
	}

	function selectProvince($state, provinceCode) {
		if (!stateHasProvinceOption($state, provinceCode)) {
			return false;
		}

		if ($state.val() !== provinceCode) {
			$state.val(provinceCode);
		}

		refreshSelect($state);
		return true;
	}

	function clearProvinceRetry(prefix) {
		if (provinceRetryTimers[prefix]) {
			clearTimeout(provinceRetryTimers[prefix]);
			delete provinceRetryTimers[prefix];
		}
	}

	function scheduleProvinceRestore(prefix, attempt) {
		attempt = attempt || 0;

		if (attempt > 25) {
			return;
		}

		var savedProvince = getStoredValue(STORAGE_KEY_PROVINCE);
		if (!savedProvince) {
			return;
		}

		var $state = $('#' + prefix + '_state');
		var $city = $('#' + prefix + '_city');

		if (!$state.length || !$city.length || !document.body.contains($state[0])) {
			return;
		}

		if (selectProvince($state, savedProvince)) {
			clearProvinceRetry(prefix);
			restoreWardSelection(prefix, savedProvince);
			return;
		}

		provinceRetryTimers[prefix] = setTimeout(function () {
			scheduleProvinceRestore(prefix, attempt + 1);
		}, 200);
	}

	function fixStatePlaceholder($state) {
		if (!$state.length || !$state.is('select')) {
			return;
		}

		var $first = $state.find('option').first();
		if ($first.length && ($first.val() === '' || $first.text().toLowerCase().indexOf('select') !== -1)) {
			$first.text(eqb_checkout.i18n.selectProvince);
		}
	}

	function reorderFields(prefix) {
		var $wrapper = $('#' + prefix + '_state_field').closest('.woocommerce-' + prefix + '-fields__field-wrapper');
		if (!$wrapper.length) {
			$wrapper = $('.woocommerce-' + prefix + '-fields__field-wrapper').first();
		}
		if (!$wrapper.length) {
			return;
		}

		fieldOrder.forEach(function (suffix) {
			var $field = $('#' + prefix + '_' + suffix + '_field');
			if ($field.length) {
				$wrapper.append($field);
			}
		});
	}

	function loadWards($state, $city, selectedWard, maTinhOverride) {
		var maTinh = maTinhOverride || $state.val();
		var stateId = $state.attr('id') || '';

		if (!maTinh) {
			$city.html('<option value="">' + eqb_checkout.i18n.selectWard + '</option>').prop('disabled', true);
			return;
		}

		wardLoadSeq[stateId] = (wardLoadSeq[stateId] || 0) + 1;
		var seq = wardLoadSeq[stateId];

		$city.prop('disabled', true).html(
			'<option value="">' + eqb_checkout.i18n.loadingWards + '</option>'
		);

		$.post(eqb_checkout.ajax_url, {
			action: 'eqb_get_wards',
			nonce: eqb_checkout.nonce,
			ma_tinh: maTinh
		})
			.done(function (res) {
				if (seq !== wardLoadSeq[stateId]) {
					return;
				}

				if (!$state.length || !$city.length || !document.body.contains($state[0])) {
					return;
				}

				var html = '<option value="">' + eqb_checkout.i18n.selectWard + '</option>';

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
				if (seq !== wardLoadSeq[stateId]) {
					return;
				}

				if (!$city.length || !document.body.contains($city[0])) {
					return;
				}

				$city.html('<option value="">' + eqb_checkout.i18n.error + '</option>').prop('disabled', false);
			});
	}

	function selectWardIfPresent($city, wardCode) {
		if (!wardCode || !$city.length) {
			return false;
		}

		if ($city.find('option[value="' + wardCode + '"]').length) {
			$city.val(wardCode);
			return true;
		}

		return false;
	}

	function restoreWardSelection(prefix, maTinh) {
		var $state = $('#' + prefix + '_state');
		var $city = $('#' + prefix + '_city');

		if (!$state.length || !$city.length || !maTinh) {
			return;
		}

		var savedWard = getStoredValue(STORAGE_KEY_WARD);
		var currentWard = $city.val();
		var currentWardValid = currentWard && $city.find('option[value="' + currentWard + '"]').length;
		var wardToSelect = currentWardValid ? currentWard : savedWard;

		if (wardToSelect && selectWardIfPresent($city, wardToSelect)) {
			refreshSelect($city);
			return;
		}

		if (wardToSelect || $city.find('option').length <= 1) {
			loadWards($state, $city, wardToSelect, maTinh);
		}
	}

	function restoreSavedAddress(prefix) {
		var $state = $('#' + prefix + '_state');
		var $city = $('#' + prefix + '_city');

		if (!$state.length || !$city.length) {
			return;
		}

		var savedProvince = getStoredValue(STORAGE_KEY_PROVINCE);
		var provinceSelected = false;

		if (savedProvince) {
			provinceSelected = selectProvince($state, savedProvince);
			if (!provinceSelected) {
				scheduleProvinceRestore(prefix, 0);
			} else {
				clearProvinceRetry(prefix);
			}
		}

		var maTinh = $state.val() || savedProvince;
		if (!maTinh) {
			return;
		}

		restoreWardSelection(prefix, maTinh);
	}

	function bindPair(prefix) {
		var $state = $('#' + prefix + '_state');
		var $city = $('#' + prefix + '_city');

		if (!$state.length || !$city.length) {
			return;
		}

		fixStatePlaceholder($state);
		restoreSavedAddress(prefix);

		$state.off('change.eqbCheckout').on('change.eqbCheckout', function () {
			var maTinh = $(this).val();
			setStoredValue(STORAGE_KEY_PROVINCE, maTinh);
			setStoredValue(STORAGE_KEY_WARD, '');
			loadWards($state, $city, '');
		});

		$city.off('change.eqbCheckoutWard').on('change.eqbCheckoutWard', function () {
			setStoredValue(STORAGE_KEY_WARD, $(this).val());
		});
	}

	function initCheckoutAddress() {
		['billing', 'shipping'].forEach(function (prefix) {
			fixCountry(prefix);
			reorderFields(prefix);
			bindPair(prefix);
		});
	}

	function scheduleInitCheckoutAddress() {
		clearTimeout(initTimer);
		initTimer = setTimeout(initCheckoutAddress, 120);
	}

	function saveAddressToStorage() {
		var maTinh = $('#billing_state').val();
		var maXa = $('#billing_city').val();
		setStoredValue(STORAGE_KEY_PROVINCE, maTinh);
		setStoredValue(STORAGE_KEY_WARD, maXa);
	}

	$(function () {
		scheduleInitCheckoutAddress();
		$(document.body).on('init_checkout updated_checkout country_to_state_changed', scheduleInitCheckoutAddress);
		$(document.body).on('checkout_place_order', saveAddressToStorage);
	});
})(jQuery);
