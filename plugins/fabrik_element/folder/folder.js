/**
 * Folder Element
 *
 * @copyright: Copyright (C) 2005-2015, fabrikar.com - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

var FbFolder = my.Class(FbElement, {
	constructor: function (element, options) {
		this.plugin = 'fabrikfolder';
		FbFolder.Super.call(this, element, options);
	}
});