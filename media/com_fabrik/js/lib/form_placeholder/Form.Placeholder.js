/*
---
description: Provides a fallback for the placeholder property on input elements for older browsers.

license:
  - MIT-style license

authors:
  - Matthias Schmidt (http://www.m-schmidt.eu)

version:
  - 1.2

requires:
  core/1.2.5: '*'

provides:
  - Form.Placeholder

...
*/
(function(){

if (!this.Form) this.Form = {};

var supportsPlaceholder = ('placeholder' in document.createElement('input'));
if (!('supportsPlaceholder' in this) && this.supportsPlaceholder !== false && supportsPlaceholder) {
	this.Form.Placeholder = my.Class({});
	return;
}

this.Form.Placeholder = my.Class({
	options: {
		color: '#A3A3A3',
		clearOnSubmit: true
	},
	constructor: function (selector, options) {
		var self = this;
		this.options = $.extend(this.options, options);
		document.getElements(selector).each (function (el) {
			if (el.data('placeholder') !== undefined) {
				el.data('placeholder', el.get('placeholder'));
				el.store('origColor', el.getStyle('color'));
				var isPassword = el.prop('type') === 'password' ? true : false;
				el.data('isPassword', isPassword);
				this.activatePlaceholder(el);
				el.on('focus', function() {
					self.deactivatePlaceholder(el);
				});
				el.on('blur', function () {
					self.activatePlaceholder(el);
				});

				if (el.closest('form').length > 0 && this.options.clearOnSubmit) {
					el.closest('form').on('submit', function () {
						if (el.val() === el.data('placeholder')) {
							el.val('');
						}
					});
				}
			}
		});
	},

	activatePlaceholder: function (el) {
		if (el.val() === '' || el.val() === el.data('placeholder')) {
			if (el.data('isPassword')) {
				el.data('type', 'text');
			}
			el.css('color', el.data('origColor'));
			el.data('value', el.data('placeholder'));
		}

	},
	deactivatePlaceholder: function (el) {
		if (el.val() === el.data('placeholder')) {
			if (el.data('isPassword')) {
				el.data('type', 'password');
			}
			el.data('value', '');
			el.css('color', el.data('origColor'));
		}
	}
});

})();