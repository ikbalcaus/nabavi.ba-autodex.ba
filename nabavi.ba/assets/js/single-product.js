/**
 * Nabavi.ba — product gallery and tabs.
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
		var gallery = document.querySelector('.nabavi-gallery');
		var mainImage = gallery ? gallery.querySelector('.nabavi-gallery__image') : null;
		var thumbs = gallery ? gallery.querySelectorAll('.nabavi-gallery__thumb') : [];

		if (!gallery || !mainImage || !thumbs.length) {
			return;
		}

		thumbs.forEach(function (thumb) {
			thumb.addEventListener('click', function () {
				var image = thumb.getAttribute('data-image');
				var srcset = thumb.getAttribute('data-srcset');

				if (!image) {
					return;
				}

				mainImage.src = image;

				if (srcset) {
					mainImage.srcset = srcset;
				} else {
					mainImage.removeAttribute('srcset');
				}

				thumbs.forEach(function (item) {
					item.classList.remove('is-active');
				});

				thumb.classList.add('is-active');
			});
		});

		// Fallback for environments where WooCommerce's tab script is not loaded.
		if (window.jQuery && jQuery.fn && typeof jQuery.fn.wcSingleProductTabs === 'function') {
			return;
		}

		var tabLinks = document.querySelectorAll('.woocommerce-tabs ul.tabs li a');

		if (!tabLinks.length) {
			return;
		}

		tabLinks.forEach(function (link) {
			link.addEventListener('click', function (event) {
				var target = link.getAttribute('href');

				if (!target || target.charAt(0) !== '#') {
					return;
				}

				var panel = document.querySelector(target);

				if (!panel) {
					return;
				}

				event.preventDefault();

				tabLinks.forEach(function (item) {
					item.parentElement.classList.remove('active');
					item.setAttribute('aria-selected', 'false');
				});

				document.querySelectorAll('.woocommerce-tabs .woocommerce-Tabs-panel').forEach(function (item) {
					item.style.display = 'none';
				});

				link.parentElement.classList.add('active');
				link.setAttribute('aria-selected', 'true');
				panel.style.display = 'block';
			});
		});
	});
})();
