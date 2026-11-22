/**
 * Nabavi.ba — contact form UX.
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
		var form = document.querySelector('.nabavi-contact-form');

		if (form) {
			form.addEventListener('submit', function () {
				var button = form.querySelector('button[type="submit"]');

				if (button) {
					button.disabled = true;
					button.textContent = 'Slanje…';
				}
			});
		}

		var notice = document.querySelector('.nabavi-notice');

		if (notice) {
			notice.scrollIntoView({ behavior: 'smooth', block: 'center' });
		}
	});
})();
