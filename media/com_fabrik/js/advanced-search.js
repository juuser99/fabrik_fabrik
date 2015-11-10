/**
 * Advanced Search
 *
 * @copyright: Copyright (C) 2005-2013, fabrikar.com - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

/*jshint mootools: true */
/*global Fabrik:true, fconsole:true, Joomla:true, CloneObject:true, $H:true,unescape:true */

AdvancedSearch = my.Class({

	options: {
		'ajax': false,
		'controller': 'list',
		'parentView': '',
		'defaultStatement': '='
	},

	constructor: function (options) {
		this.options = $.append(this.options, options);
		this.form = document.id('advanced-search-win' + this.options.listref).getElement('form');
		this.trs = Array.from([]);
		if (this.form.getElement('.advanced-search-add')) {
			this.form.getElement('.advanced-search-add').removeEvents('click');
			this.form.getElement('.advanced-search-add').addEvent('click', function (e) {
				this.addRow(e);
			}.bind(this));
			this.form.getElement('.advanced-search-clearall').removeEvents('click');
			this.form.getElement('.advanced-search-clearall').addEvent('click', function (e) {
				this.resetForm(e);
			}.bind(this));
			this.trs.each(function (tr) {
				tr.inject(this.form.getElement('.advanced-search-list').getElements('tr').getLast(), 'after');
			}.bind(this));
		}

		this.form.addEvent('click:relay(tr)', function (e, target) {
			this.form.getElements('tr').removeClass('fabrikRowClick');
			target.addClass('fabrikRowClick');
		}.bind(this));
		this.watchDelete();
		this.watchApply();
		this.watchElementList();
		Fabrik.trigger('fabrik.advancedSearch.ready', this);
	},

	watchApply: function () {

		this.form.getElement('.advanced-search-apply').addEvent('click', function (e) {
			Fabrik.trigger('fabrik.advancedSearch.submit', this);
			var filterManager = Fabrik['filter_' + this.options.parentView];

			// Format date advanced search fields to db format before posting
			if (typeOf(filterManager) !== 'null') {
				filterManager.onSubmit();
			}
			/* Ensure that we clear down other advanced searches from the session.
			 * Otherwise, filter on one element and submit works, but changing the filter element and value
			 * will result in 2 filters applied (not one)
			 * @see http://fabrikar.com/forums/index.php?threads/advanced-search-remembers-value-of-last-dropdown-after-element-change.34734/#post-175693
			 */
			var list = this.getList();
			new Element('input', {
				'name': 'resetfilters',
				'value': 1,
				'type': 'hidden'
			}).inject(this.form);

			if (!this.options.ajax) {
				return;
			}
			e.stop();

			list.submit(this.options.controller + '.filter');
		}.bind(this));
	},

	getList: function () {
		var list = Fabrik.blocks['list_' + this.options.listref];
		if (typeOf(list) === 'null') {
			list = Fabrik.blocks[this.options.parentView];
		}
		return list;
	},

	watchDelete: function () {
		//should really just delegate these events from the adv search table
		this.form.getElements('.advanced-search-remove-row').removeEvents();
		this.form.getElements('.advanced-search-remove-row').addEvent('click', function (e) {
			this.removeRow(e);
		}.bind(this));
	},

	watchElementList: function () {
		this.form.getElements('select.key').removeEvents();
		this.form.getElements('select.key').addEvent('change', function (e) {
			this.updateValueInput(e);
		}.bind(this));
	},

	/**
	 * called when you choose an element from the filter dropdown list
	 * should run ajax query that updates value field to correspond with selected
	 * element
	 * @param {Object} e event
	 */

	updateValueInput: function (e) {
		var row = $(e.target).closest('tr');
		Fabrik.loader.start(row);
		var v = e.target.get('value');
		var update = $(e.target).parent().parent().getElements('td')[3];
		if (v === '') {
			update.set('html', '');
			return;
		}
		var url = 'index.php?option=com_fabrik&task=list.elementFilter&format=raw';
		var eldata = this.options.elementMap[v];
		new Request.HTML({'url': url,
			'update': update,
			'data': {'element': v, 'id': this.options.listid, 'elid': eldata.id, 'plugin': eldata.plugin, 'counter': this.options.counter,
				'listref':  this.options.listref, 'context': this.options.controller,
				'parentView': this.options.parentView},
			'onComplete': function () {
				Fabrik.loader.stop(row);
			}}).send();
	},

	addRow: function (e) {
		this.options.counter ++;
		e.stop();
		var tr = this.form.getElement('.advanced-search-list').getElement('tbody').getElements('tr').getLast();
		var clone = tr.clone();
		clone.removeClass('oddRow1').removeClass('oddRow0').addClass('oddRow' + this.options.counter % 2);
		clone.inject(tr, 'after');
		clone.getElement('td').empty().set('html', this.options.conditionList);
		var tds = clone.getElements('td');
		tds[1].empty().set('html', this.options.elementList);
		tds[1].adopt([
			new Element('input', {'type': 'hidden', 'name': 'fabrik___filter[list_' + this.options.listref + '][search_type][]', 'value': 'advanced'}),
			new Element('input', {'type': 'hidden', 'name': 'fabrik___filter[list_' + this.options.listref + '][grouped_to_previous][]', 'value': '0'})
		]);
		tds[2].empty().set('html', this.options.statementList);
		tds[3].empty();
		this.watchDelete();
		this.watchElementList();
		Fabrik.trigger('fabrik.advancedSearch.row.added', this);
	},

	removeRow: function (e) {
		e.stop();
		if (this.form.getElements('.advanced-search-remove-row').length > 1) {
			this.options.counter --;
			var tr = e.target.findUp('tr');
			var fx = new Fx.Morph(tr, {
				duration: 800,
				transition: Fx.Transitions.Quart.easeOut,
				onComplete: function () {
					tr.dispose();
				}
			});
			fx.start({
				'height': 0,
				'opacity': 0
			});
		}
		Fabrik.trigger('fabrik.advancedSearch.row.removed', this);
	},

	/**
	 * removes all rows except for the first one, whose values are reset to empty
	 */
	resetForm: function () {
		var table = this.form.find('.advanced-search-list'),
			self = this;
		if (!table) {
			return;
		}
		table.find('tbody tr').each(function (i) {
			if (i >= 1) {
				$(this).dispose();
			}
			if (i === 0) {
				$(this).find('.inputbox').each(function () {
					if ($(this).id.test(/condition$/))
					{
						$(this).value = self.options.defaultStatement;
					}
					else
					{
						$(this).selectedIndex = 0;
					}
				});
				$(this).find('input').each(function () {
					$(this).value = '';
				});
			}
		});
		this.watchDelete();
		this.watchElementList();
		Fabrik.trigger('fabrik.advancedSearch.reset', this);
	},

	deleteFilterOption: function (e) {
		event.target.removeEvent('click', function (e) {
			this.deleteFilterOption(e);
		}.bind(this));
		var tr = event.target.parentNode.parentNode;
		var table = tr.parentNode;
		table.removeChild(tr);
		e.stop();
	}

});