/**
 * Facebook Likebox Element
 *
 * @copyright: Copyright (C) 2005-2015, fabrikar.com - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

var FbLikebox = my.Class(FbElement, {
	constructor: function (element, options) {
		this.plugin = 'fbLikebox';
		FbLikebox.Super.call(this, element, options);
	}
});