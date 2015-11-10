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
		this.options = $.append(this.options, options);
		document.getElements(selector).each (function (el) {
			if (typeOf(el.get('placeholder')) !== 'null') {
				el.store('placeholder', el.get('placeholder'));
				el.store('origColor', el.getStyle('color'));
				var isPassword = el.get('type') === 'password' ? true : false;
				el.store('isPassword', isPassword);
				this.activatePlaceholder(el);
				el.addEvents({
					'focus': function() {
						this.deactivatePlaceholder(el);
					}.bind(this),
				 	'blur': function() {
						this.activatePlaceholder(el);
				 	}.bind(this)
				});

				if (el.closest('form').length > 0 && this.options.clearOnSubmit) {
					el.closest('form').addEvent('submit', function (e) {
						if (el.get('value') === el.retrieve('placeholder')) {
							el.set('value', '');
						}
					}.bind(this));
				}
			}
		}.bind(this));
	},

	activatePlaceholder: function (el) {
		if (el.get('value') === '' || el.get('value') === el.retrieve('placeholder')) {
			if (el.retrieve('isPassword')) {
				el.set('type', 'text');
			}
			el.setStyle('color', el.retrieve('origColor'));
			el.set('value', el.retrieve('placeholder'));
		}

	},
	deactivatePlaceholder: function (el) {
		if (el.get('value') === el.retrieve('placeholder')) {
			if (el.retrieve('isPassword')) {
				el.set('type', 'password');
			}
			el.set('value', '');
			el.setStyle('color', el.retrieve('origColor'));
		}
	}
});

})();