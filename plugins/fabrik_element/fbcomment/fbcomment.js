/**
 * Facebook Comment Element
 *
 * @copyright: Copyright (C) 2005-2015, fabrikar.com - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

var FbComment = my.Class(FbElement, {
	constructor: function (element, options) {
		this.plugin = 'fbComment';
		FbComment.Super.call(this, element, options);
	}
});