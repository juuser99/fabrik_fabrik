/**
 * List Webservice
 *
 * @copyright: Copyright (C) 2005-2015, fabrikar.com - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

var FbListWebservice = my.Class(FbListPlugin, {
	constructor: function (options) {
		this.parent(options);
	},

	buttonAction: function () {
		this.list.submit('list.doPlugin');
	}
});