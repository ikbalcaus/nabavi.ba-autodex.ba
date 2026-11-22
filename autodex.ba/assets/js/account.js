/**
 * Autodex.ba — account password visibility.
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
		document.querySelectorAll('.show-password-input').forEach(function (button) {
			button.addEventListener('click', function () {
				var wrapper = button.closest('.password-input');
				var input = wrapper ? wrapper.querySelector('input') : null;

				if (!input) {
					return;
				}

				var isPassword = input.getAttribute('type') === 'password';
				input.setAttribute('type', isPassword ? 'text' : 'password');
				button.setAttribute('aria-pressed', isPassword ? 'true' : 'false');
				button.setAttribute('aria-label', isPassword ? 'Sakrij lozinku' : 'Prikaži lozinku');
			});
		});
	});
})();
