/**
 * Nabavi.ba — global scripts.
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
		var body = document.body;
		var drawer = document.getElementById('nabavi-mobile-nav');
		var openButton = document.querySelector('.nabavi-menu-toggle');
		var closeButton = drawer ? drawer.querySelector('.nabavi-mobile-nav__close') : null;
		var overlay = drawer ? drawer.querySelector('.nabavi-mobile-nav__overlay') : null;

		function openMenu() {
			if (!drawer || !openButton) {
				return;
			}

			drawer.hidden = false;
			window.requestAnimationFrame(function () {
				drawer.classList.add('is-open');
			});
			body.classList.add('nabavi-menu-open');
			openButton.setAttribute('aria-expanded', 'true');

			if (closeButton) {
				closeButton.focus();
			}
		}

		function closeMenu(returnFocus) {
			if (!drawer) {
				return;
			}

			drawer.classList.remove('is-open');
			body.classList.remove('nabavi-menu-open');
			if (openButton) {
				openButton.setAttribute('aria-expanded', 'false');
			}

			window.setTimeout(function () {
				drawer.hidden = true;
			}, 300);

			if (returnFocus !== false && openButton) {
				openButton.focus();
			}
		}

		if (openButton) {
			openButton.addEventListener('click', function () {
				if (drawer && drawer.hidden) {
					openMenu();
				} else {
					closeMenu();
				}
			});
		}

		if (closeButton) {
			closeButton.addEventListener('click', function () {
				closeMenu();
			});
		}

		if (overlay) {
			overlay.addEventListener('click', function () {
				closeMenu();
			});
		}

		document.addEventListener('keydown', function (event) {
			if (event.key === 'Escape' && drawer && !drawer.hidden) {
				closeMenu();
			}
		});

		// Mobile submenu toggles.
		if (drawer) {
			drawer.querySelectorAll('.menu-item-has-children > a').forEach(function (link) {
				link.addEventListener('click', function (event) {
					var parent = link.parentElement;
					var submenu = parent.querySelector('.sub-menu');

					if (!submenu) {
						return;
					}

					event.preventDefault();
					parent.classList.toggle('is-open');
					submenu.hidden = !parent.classList.contains('is-open');
				});
			});
		}

		// Quantity plus/minus controls.
		function updateQuantity(button, direction) {
			var wrapper = button.closest('.nabavi-quantity');
			var input = wrapper ? wrapper.querySelector('input.qty') : null;

			if (!input) {
				return;
			}

			var min = parseFloat(wrapper.getAttribute('data-min'));
			var max = parseFloat(wrapper.getAttribute('data-max'));
			var step = parseFloat(wrapper.getAttribute('data-step')) || 1;
			var value = parseFloat(input.value) || 0;

			if (!isFinite(min)) {
				min = 0;
			}

			if (!isFinite(max)) {
				max = 0;
			}

			value = direction > 0 ? value + step : value - step;

			if (value < min) {
				value = min;
			}

			if (max > 0 && value > max) {
				value = max;
			}

			input.value = value;
			input.dispatchEvent(new Event('input', { bubbles: true }));
			input.dispatchEvent(new Event('change', { bubbles: true }));

			var cartForm = input.closest('form.woocommerce-cart-form');

			if (cartForm) {
				var updateButton = cartForm.querySelector('button[name="update_cart"]');

				if (updateButton) {
					updateButton.removeAttribute('disabled');
					window.setTimeout(function () {
						updateButton.click();
					}, 250);
				}
			}
		}

		document.addEventListener('click', function (event) {
			var button = event.target.closest('.nabavi-quantity .qty-btn');

			if (!button) {
				return;
			}

			event.preventDefault();
			updateQuantity(button, button.classList.contains('qty-plus') ? 1 : -1);
		});

		// Keep quantity input within bounds when typed manually.
		document.addEventListener('change', function (event) {
			if (!event.target.matches('.nabavi-quantity input.qty')) {
				return;
			}

			var wrapper = event.target.closest('.nabavi-quantity');
			var min = parseFloat(wrapper.getAttribute('data-min'));
			var max = parseFloat(wrapper.getAttribute('data-max'));
			var value = parseFloat(event.target.value);

			if (!isFinite(value)) {
				value = isFinite(min) ? min : 1;
			}

			if (isFinite(min) && value < min) {
				value = min;
			}

			if (isFinite(max) && max > 0 && value > max) {
				value = max;
			}

			event.target.value = value;
		});

		// Compact sticky header once the page is scrolled.
		var header = document.querySelector('.nabavi-header');

		if (header) {
			var stuck = false;

			var syncHeader = function () {
				var next = window.scrollY > 24;

				if (next !== stuck) {
					stuck = next;
					header.classList.toggle('is-stuck', stuck);
				}
			};

			syncHeader();
			window.addEventListener('scroll', syncHeader, { passive: true });
		}
	});
})();
