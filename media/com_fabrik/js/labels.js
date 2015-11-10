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
			var label = ($this).find('label');
			if (label.length !== 0) {
				var input = c.getElement('input');
				if (typeOf(input) === 'null') {
					input = c.getElement('textarea');
				}
				if (typeOf(input) !== 'null') {
					input.value = label[0].innerHTML;

					input.addEvent('click', function (e) {
						self.toogleLabel(e, input, label[0].innerHTML);
					});

					input.addEvent('blur', function (e) {
						self.toogleLabel(e, input, label[0].innerHTML);
					});
					label.html('');
					c.find('.fabrikLabel').remove();
				}
			}
		}.bind(this));
	},

	toogleLabel: function (e, input, label) {
		new Event(e).stop();
		if (e.type === 'click') {
			if (input.get('value') === label) {
				input.value = '';
			}
		} else {
			if (input.get('value') === '') {
				input.value = label;
			}
		}
	}

});

window.addEvent('fabrik.loaded', function () {
	new Labels();
});