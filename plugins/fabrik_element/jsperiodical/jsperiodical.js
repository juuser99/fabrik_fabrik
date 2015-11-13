/**
 * JS Periodical Element
 *
 * @copyright: Copyright (C) 2005-2015, fabrikar.com - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

FbJSPeriodical = my.Class({
	options: {
		code : '',
		period : 1000
	},

	/**
	 * Constructor
	 * @param {string} element
	 * @param {object} options
	 * @returns {*}
	 */
	constructor: function (element, options) {
		this.plugin = 'fabrikPeriodical';
		FbJSPeriodical.Super.call(this, element, options);
		var periodical;

		this.fx = function () {
			eval(this.options.code);
		}.bind(this);
		this.fx();
		var periodical = this.fx.periodical(this.options.period, this);
	}
});