/**
 * List Update Column
 *
 * @copyright: Copyright (C) 2005-2015, fabrikar.com - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

/**
 * Simple store for element js objects
 * Need to be able to trigger onSave on things like date elements to get correct format
 */
UpdateColSelect = my.Class({

	constructor: function () {
		this.updates = {};
	},

	/**
	 * As we are piggybacking on top of the advanced search code addFilter is called when the
	 * ajax request returns.
	 */
	addFilter: function (pluginType, filter) {
		if (!this.updates[pluginType]) {
			this.updates[pluginType] = [];
		}
		this.updates[pluginType].push(filter);
	},

	/**
	 * Ensure that date elements set themselves to the correct date format
	 */
	onSumbit: function () {
		if (this.updates.date) {
			this.updates.date.each(function (f) {
				f.onSubmit();
			});
		}
	}
});

var FbListUpdateCol = my.Class(FbListPlugin, {
	constructor: function (options) {
		FbListUpdateCol.Super.call(this, options);
		if (this.options.userSelect) {
			Fabrik['filter_update_col' + this.options.ref + '_' + this.options.renderOrder] = new UpdateColSelect();
			this.makeUpdateColWindow();
		}
	},

	buttonAction: function () {
		if (this.options.userSelect) {
			this.win.open();
		} else {
			this.list.submit('list.doPlugin');
		}
	},

	makeUpdateColWindow: function () {
		var tds, tr_clone, i, self = this;
		this.windowopts = {
			'id': 'update_col_win_' + this.options.ref + '_' + this.options.renderOrder,
			title: Joomla.JText._('PLG_LIST_UPDATE_COL_UPDATE'),
			loadMethod: 'html',
			content: this.options.form,
			width: 400,
			destroy: false,
			height: 300,
			onOpen: function () {
				this.fitToContent(false);
			},
			onContentLoaded: function (win) {
				var form = $('#update_col' + this.options.ref + '_' + this.options.renderOrder);

				// Add a row
				form.on('click', 'a.add', function (e, target) {
					e.preventDefault();
					var tr;
					var thead = target.closest('thead');
					if (thead) {
						tr = form.find('tbody tr').getLast();
					} else {
						tr = target.closest('tr');
					}
					if (tr.css('display') === 'none') {
						tds = tr.find('td');
						tds[0].find('select').selectedIndex = 0;
						tds[1].empty();
						tr.show();
					} else {
						tr_clone = tr.clone();
						tds = tr_clone.find('td');
						tds[0].find('select').selectedIndex = 0;
						tds[1].empty();
						tr_clone.inject(tr, 'after');
					}

				});

				// Delete a row
				form.on('click', 'a.delete', function (e, target) {
					e.preventDefault();
					var trs = form.find('tbody tr');
					if (trs.length === 1) {
						trs.getLast().hide();
					} else {
						target.closest('tr').destroy();
					}
				});

				// Select an element plugin and load it
				form.on('change', 'select.key', function (e, target) {
					var els = $(this).closest('tbody').find('.update_col_elements');
					for (i = 0; i < els.length; i++) {
						if (els[i] === target) {
							continue;
						}
						if (els[i].selectedIndex === target.selectedIndex) {
							// @TODO language
							window.alert('This element has already been selected!');
							return;
						}
					}
					var opt = target.options[target.selectedIndex];
					var row = target.closest('tr');
					Fabrik.loader.start(row);
					var update = row.find('td.update_col_value');
					var v = $(this).val();
					var plugin = opt.get('data-plugin');
					var id = opt.get('data-id');
					var counter = 0;

					// Piggy backing on the list advanced search code to get an element and its js
					var url = 'index.php?option=com_fabrik&task=list.elementFilter&format=raw';

					// It looks odd - but to get the element js code to load in correct we need to set the context to a visualization
					$.ajax({'url': url,
						'update': update,
						'data': {
							'element': v,
							'id': self.options.listid,
							'elid': id,
							'plugin': plugin,
							'counter': counter,
							'listref':  self.options.ref,
							'context': 'visualization',
							'parentView': 'update_col' + self.options.ref + '_' + self.options.renderOrder,
							'fabrikIngoreDefaultFilterVal': 1
						},
					}).done(function () {
						Fabrik.loader.stop(row);
						win.fitToContent(false);
					});
				});

				// Submit the update
				form.find('input[type=button]').on('click', function (e) {
					e.stopPropagation();
					var i;
					Fabrik['filter_update_col'  + self.options.ref + '_' + self.options.renderOrder].onSumbit();

					var listForm = $('#listform_' + self.options.ref);

					// Grab all the update settings and put them in a hidden field for later extraction within the update_col php code.
					i = $(document.createElement('input')).attr({'type': 'hidden', 'value': form.toQueryString(), 'name': 'fabrik_update_col'});
					i.inject(listForm, 'bottom');
					this.list.submit('list.doPlugin');

				});
			}
		};
		this.win = Fabrik.getWindow(this.windowopts);
		this.win.close();
	}
});
