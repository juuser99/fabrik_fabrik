/**
 * Button Element
 *
 * @copyright: Copyright (C) 2005-2015, fabrikar.com - All rights reserved.
 * @license: GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

var FbButton = my.Class(FbElement, {
	constructor: function (element, options) {
		this.plugin = 'fabrikButton';
		FbButton.Super.call(this, element, options);
	},

	addNewEventAux: function (action, js) {
		this.element.on(action, function (e) {

			// Unlike element addNewEventAux we need to stop the event otherwise the form is submitted
			if (e) {
				e.stopPropagation();
			}
			$.type(js) === 'function' ? js.delay(0, this, this) : eval(js);
		}.bind(this));
	}
});