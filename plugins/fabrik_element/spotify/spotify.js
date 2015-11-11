/**
 * YouTube Element
 *
 * @copyright: Copyright (C) 2005-2013, fabrikar.com - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

var FbSpotify = my.Class(FbElement, {
	constructor: function (element, options) {
		this.plugin = 'spotify';
		FbSpotify.Super.call(this, element, options);
	}
});