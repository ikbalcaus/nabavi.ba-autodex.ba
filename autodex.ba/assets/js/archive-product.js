/**
 * Autodex.ba — shop filters and price range.
 */
(function () {
	'use strict';

	function ready(fn) {
		if (document.readyState !== 'loading') {
			fn();
		} else {
			document.addEventListener('DOMContentLoaded', fn);
		}
	}

	ready(function () {
		var sidebar = document.getElementById('custom-shop-sidebar');
		var overlay = document.getElementById('shop-sidebar-overlay');
		var openButton = document.getElementById('open-mobile-filters');
		var closeButton = document.getElementById('close-mobile-filters');
		var body = document.body;

		function openFilters() {
			if (!sidebar || !overlay) {
				return;
			}

			sidebar.classList.add('active');
			overlay.classList.add('active');
			body.style.overflow = 'hidden';

			if (openButton) {
				openButton.setAttribute('aria-expanded', 'true');
			}

			if (closeButton) {
				closeButton.focus();
			}
		}

		function closeFilters() {
			if (!sidebar || !overlay) {
				return;
			}

			sidebar.classList.remove('active');
			overlay.classList.remove('active');
			body.style.overflow = '';

			if (openButton) {
				openButton.setAttribute('aria-expanded', 'false');
				openButton.focus();
			}
		}

		if (openButton) {
			openButton.addEventListener('click', openFilters);
		}

		if (closeButton) {
			closeButton.addEventListener('click', closeFilters);
		}

		if (overlay) {
			overlay.addEventListener('click', closeFilters);
		}

		document.addEventListener('keydown', function (event) {
			if (event.key === 'Escape' && sidebar && sidebar.classList.contains('active')) {
				closeFilters();
			}
		});

		window.addEventListener('resize', function () {
			if (window.innerWidth > 767 && sidebar && sidebar.classList.contains('active')) {
				sidebar.classList.remove('active');
				overlay.classList.remove('active');
				body.style.overflow = '';
			}
		});

		var minRange = document.getElementById('min-price');
		var maxRange = document.getElementById('max-price');
		var minInput = document.getElementById('input-min-price');
		var maxInput = document.getElementById('input-max-price');
		var submit = document.getElementById('price-filter-submit');

		if (!minRange || !maxRange || !minInput || !maxInput || !submit) {
			return;
		}

		function toNumber(value, fallback) {
			var parsed = parseInt(value, 10);
			return isNaN(parsed) ? fallback : parsed;
		}

		function syncDisplay() {
			var min = toNumber(minRange.value, 0);
			var max = toNumber(maxRange.value, 0);

			if (min > max) {
				min = max;
				minRange.value = String(min);
			}

			if (max < min) {
				max = min;
				maxRange.value = String(max);
			}

			minInput.value = String(min);
			maxInput.value = String(max);

			var dmin = document.querySelector('.drange .dmin');
			var dmax = document.querySelector('.drange .dmax');

			if (dmin) {
				dmin.textContent = String(min);
			}

			if (dmax) {
				dmax.textContent = String(max);
			}
		}

		minRange.addEventListener('input', syncDisplay);
		maxRange.addEventListener('input', syncDisplay);

		minInput.addEventListener('input', function () {
			var min = toNumber(minInput.value, toNumber(minRange.min, 0));
			var max = toNumber(maxRange.value, toNumber(maxRange.max, 0));

			if (min < toNumber(minRange.min, 0)) {
				min = toNumber(minRange.min, 0);
			}

			if (min > max) {
				min = max;
			}

			minRange.value = String(min);
			syncDisplay();
		});

		maxInput.addEventListener('input', function () {
			var max = toNumber(maxInput.value, toNumber(maxRange.max, 0));
			var min = toNumber(minRange.value, toNumber(minRange.min, 0));

			if (max > toNumber(maxRange.max, 0)) {
				max = toNumber(maxRange.max, 0);
			}

			if (max < min) {
				max = min;
			}

			maxRange.value = String(max);
			syncDisplay();
		});

		submit.addEventListener('click', function (event) {
			event.preventDefault();

			var url = new URL(window.location.href);
			var min = toNumber(minInput.value, toNumber(minRange.min, 0));
			var max = toNumber(maxInput.value, toNumber(maxRange.max, 0));

			url.searchParams.set('min_price', String(min));
			url.searchParams.set('max_price', String(max));
			url.searchParams.delete('paged');

			window.location.href = url.toString();
		});

		syncDisplay();
	});
})();
