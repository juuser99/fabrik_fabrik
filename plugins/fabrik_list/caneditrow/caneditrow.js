/**
 * List Can Edit Row
 *
 * @copyright: Copyright (C) 2005-2015, fabrikar.com - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

var FbListCanEditRow = my.Class(FbListPlugin, {

	constructor: function (options) {
		FbListCanEditRow.Super.call(this, options);
		var self = this;
		Fabrik.addEvent('onCanEditRow', function (list, args) {
			self.onCanEditRow(list, args);
		});
	},

	onCanEditRow: function (list, rowid) {
		rowid = rowid[0];
		list.result = this.options.acl[rowid];
	}
});