/**
 * Facebook Display Element
 *
 * @copyright: Copyright (C) 2005-2015, fabrikar.com - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

var FbDisplay = my.Class(FbElement, {
	constructor: function (element, options) {
		this.parent(element, options);
	},

	update: function (val) {
		if (this.getElement()) {
			this.element.innerHTML = val;
		}
	}
});