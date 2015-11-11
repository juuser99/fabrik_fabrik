/**
 * Timestamp Element
 *
 * @copyright: Copyright (C) 2005-2015, fabrikar.com - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

FbTimestamp = my.Class(FbElement, {
	initialize: function (element, options) {
		this.plugin = 'fabriktimestamp';
		FbTimestamp.Super.call(this, element, options);
	}
});