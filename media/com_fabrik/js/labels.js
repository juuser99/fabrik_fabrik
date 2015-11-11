/**
 * Labels
 *
 * @copyright: Copyright (C) 2005-2015, fabrikar.com - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

/*jshint mootools: true */
/*global Fabrik:true, fconsole:true, Joomla:true, CloneObject:true, $H:true,unescape:true,head:true */

var Labels = my.Class({

	constructor: function () {
		var self = this;
		$('.fabrikElementContainer').each(function (c) {
			var label = $(this).find('label');
			if (label.length !== 0) {
				var input = c.getElement('input');
				if (input.length === 0) {
					input = c.getElement('textarea');
				}
				if (input.length !== 0) {
					input.val(label.html());

					input.on('click', function (e) {
						self.toogleLabel(e, input, label.html());
					});

					input.on('blur', function (e) {
						self.toogleLabel(e, input, label.html());
					});
					label.html('');
					c.find('.fabrikLabel').remove();
				}
			}
		}.bind(this));
	},

	toogleLabel: function (e, input, label) {
		e.stop();
		if (e.type === 'click') {
			if (input.val() === label) {
				input.val('');
			}
		} else {
			if (input.val() === '') {
				input.val(label);
			}
		}
	}

});

window.addEvent('fabrik.loaded', function () {
	new Labels();
});