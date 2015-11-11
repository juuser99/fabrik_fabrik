/**
 * Controller object for admin interface
 *
 * @copyright: Copyright (C) 2005-2015, fabrikar.com - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

AdminPackage = my.Class({

	constructor: function (opts) {
		this.simpleUI();
	},

	simpleUI: function () {
		var source = $('#list-pick'),
		target = $('#blockslist'),
		addBtn = $('#add-list'),
		removeBtn = $('#remove-list');

		this._swaplistIni(addBtn, removeBtn, source, target);

		source = $('#form-pick');
		target = $('#blocksform');
		addBtn = $('#add-form');
		removeBtn = $('#remove-form');
		this._swaplistIni(addBtn, removeBtn, source, target);
	},

	_swaplistIni: function (addBtn, removeBtn, source, target) {
		var self = this;
		addBtn.on('click', function (e) {
			e.stopPropagation();
			self._swaplist(source, target);
		});

		removeBtn.on('click', function (e) {
			e.stopPropagation();
			self._swaplist(target, source);
		});
	},

	_swaplist: function (source, target) {
		var sel = source.getElements('option').filter(function (o) {
			return o.selected;
		});
		sel.each(function (o) {
			o.clone().inject(target);
			o.destroy();
		});
	},

	prepareSave: function () {

		// Ensure all added options are selected
		jQuery.each(document.getElements('#blockslist option'), function (key, opt) {
			opt.selected = true;
		});

		jQuery.each(document.getElements('#blocksform option'), function (key, opt) {
			opt.selected = true;
		});

		return true;
	}
});