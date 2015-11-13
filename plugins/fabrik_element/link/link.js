/**
 * Link Element
 *
 * @copyright: Copyright (C) 2005-2015, fabrikar.com - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

var FbLink = my.Class(FbElementList, {

	/**
	 * Constructor
	 * @param {string} element
	 * @param {object} options
	 * @returns {*}
	 */
	constructor: function (element, options) {
		this.plugin = 'fabrikLink';
		FbLink.Super.call(this, element, options);
		this.subElements = this._getSubElements();
	},

	/**
	 * Update the element's value
	 * @param {string|object|array} val
	 */
	update: function (val) {
		this.getElement();
		var subs = this.element.find('.fabrikinput');
		if (typeof(val) === 'object') {
			subs[0].value = val.label;
			subs[1].value = val.link;
		} else {
			subs.each(function () {
				$(this).val(val);
			});
		}
	},

	/**
	 * Get the element value
	 * @returns {Array}
	 */
	getValue : function () {
		var s = this._getSubElements();
		var a = [];
		s.each(function () {
			a.push($(this).val());
		});
		return a;
	}

});
