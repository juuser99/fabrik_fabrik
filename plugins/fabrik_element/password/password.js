/**
 * Password Element
 *
 * @copyright: Copyright (C) 2005-2015, fabrikar.com - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

var FbPassword = my.Class(FbElement, {

	options: {
		progressbar: false
	},

	/**
	 * Constructor
	 * @param {string} element
	 * @param {object} options
	 * @returns {*}
	 */
	constructor: function (element, options) {
		FbPassword.Super.call(this, element, options);
		if (!this.options.editable) {
			return;
		}
		this.ini();
	},

	/**
	 * Initialse the element
	 */
	ini: function () {
		var self = this;
		if (this.element) {
			this.element.on('keyup', function (e) {
				self.passwordChanged(e);
			});
		}
		if (this.options.ajax_validation === true) {
			this.getConfirmationField().on('blur', function (e) {
				self.form.doElementValidation(e, false, '_check');
			});
		}

		if (this.getConfirmationField().get('value') === '') {
			this.getConfirmationField().value = this.element.value;
		}
	},

	/**
	 * Run when the element is cloned in a repeat group
	 * @param {number} c
	 */
	cloned: function (c) {
		console.log('cloned');
		FbPassword.Super.prototype.cloned(this, c);
		this.ini();
	},

	/**
	 * Password change, check its strength
	 */
	passwordChanged: function () {
		var strength = this.getContainer().find('.strength');
		if (strength.length === 0) {
			return;
		}
		var strongRegex = new RegExp('^(?=.{6,})(?=.*[A-Z])(?=.*[a-z])(?=.*[0-9])(?=.*\\W).*$', 'g'),
			mediumRegex = new RegExp('^(?=.{6,})(((?=.*[A-Z])(?=.*[a-z]))|((?=.*[A-Z])(?=.*[0-9]))|((?=.*[a-z])(?=.*[0-9]))).*$', 'g'),
			enoughRegex = new RegExp('(?=.{6,}).*', 'g'),
			pwd = this.element,
			html = '';

		// Bootstrap progress bar @todo JLayout this
		html += '<div class="bar bar-warning" style="width: 10%;"></div>';
		var tipTitle = Joomla.JText._('PLG_ELEMENT_PASSWORD_MORE_CHARACTERS');
		if (enoughRegex.test(pwd.value)) {
			html = '<div class="bar bar-info" style="width: 30%;"></div>';
			tipTitle = Joomla.JText._('PLG_ELEMENT_PASSWORD_WEAK');
		}
		if (mediumRegex.test(pwd.value)) {
			html = '<div class="bar bar-info" style="width: 70%;"></div>';
			tipTitle = Joomla.JText._('PLG_ELEMENT_PASSWORD_MEDIUM');
		}
		if (strongRegex.test(pwd.value)) {
			html = '<div class="bar bar-success" style="width: 100%;"></div>';
			tipTitle = Joomla.JText._('PLG_ELEMENT_PASSWORD_STRONG');
		}
		var options = {
			title: tipTitle
		};
		try {
			jQuery(strength).tooltip('destroy');
		} catch (e) {
			console.log(e);
		}
		jQuery(strength).tooltip(options);
		strength.html(html);
	},

	/**
	 * Get the confirmation password field
	 * @returns {jQuery}
	 */
	getConfirmationField: function () {
		return this.getContainer().find('input[name*=check]');
	}
});