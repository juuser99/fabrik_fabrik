/**
 * Admin RepeatGroup Editor
 *
 * @copyright: Copyright (C) 2005-2015, fabrikar.com - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

var FbRepeatGroup = my.Class({

	options: {
		repeatmin: 1
	},

	constructor: function (element, options) {
		this.element = document.id(element);
		this.options = $.append(this.options, options);
		this.counter = this.getCounter();
		this.watchAdd();
		this.watchDelete();
	},

	repeatContainers: function () {
		return this.element.getElements('.repeatGroup');
	},

	watchAdd : function () {
		var newid;
		this.element.getElement('a[data-button=addButton]').addEvent('click', function (e) {
			e.stop();
			var div = this.repeatContainers().getLast();
			var newc = this.counter + 1;
			var id = div.id.replace('-' + this.counter, '-' + newc);
			var c = new Element('div', {'class': 'repeatGroup', 'id': id}).set('html', div.innerHTML);
			c.inject(div, 'after');
			this.counter = newc;

			// Update params ids
			if (this.counter !== 0) {
				jQuery.each(c.getElements('input, select'), function (key, i) {
					var newPlugin = false;
					var newid = '';
					var oldid = i.id;
					if (i.id !== '') {
						var a = i.id.split('-');
						a.pop();
						newid = a.join('-') + '-' + this.counter;
						i.id = newid;
					}

					this.increaseName(i);
					jQuery.each(FabrikAdmin.model.fields, function (type, plugins) {
						var newPlugin = false;
						if (typeOf(FabrikAdmin.model.fields[type][oldid]) !== 'null') {
							var plugin = FabrikAdmin.model.fields[type][oldid];
							newPlugin = Object.clone(plugin);
							try {
								newPlugin.cloned(newid, this.counter);
							} catch (err) {
								fconsole('no clone method available for ' + i.id);
							}
						}
						if (newPlugin !== false) {
							FabrikAdmin.model.fields[type][i.id] = newPlugin;
						}
					}.bind(this));


				}.bind(this));

				jQuery.each(c.getElements('img[src=components/com_fabrik/images/ajax-loader.gif]'), function (key, i) {

					var a = i.id.split('-');
					a.pop();
					var newid = a.join('-') + '-' + this.counter + '_loader';
					i.id = newid;
				}.bind(this));
			}
		}.bind(this));
	},

	getCounter : function () {
		return this.repeatContainers().length;
	},

	watchDelete : function () {
		var btns = this.element.getElements('a[data-button=deleteButton]')
		btns.removeEvents();
		jQuery.each(btns, function (x, r) {
			r.addEvent('click', function (e) {
				e.stop();
				var count = this.getCounter();
				if (count > this.options.repeatmin) {
					var u = this.repeatContainers().getLast();
					u.destroy();
				}
				this.rename(x);
			}.bind(this));
		}.bind(this));
	},

	increaseName : function (i) {
		var namebits = i.name.split('][');
		var ref = namebits[2].replace(']', '').toInt() + 1;
		namebits.splice(2, 1, ref);
		i.name = namebits.join('][') + ']';
	},

	rename : function (x) {
		jQuery.each(this.element.getElements('input, select'), function (key, i) {
			i.name = this._decreaseName(i.name, x);
		}.bind(this));
	},

	_decreaseName: function (n, delIndex) {
		var namebits = n.split('][');
		var i = namebits[2].replace(']', '').toInt();
		if (i >= 1  && i > delIndex) {
			i --;
		}
		if (namebits.length === 3) {
			i = i + ']';
		}
		namebits.splice(2, 1, i);
		var r = namebits.join('][');
		return r;
	}
});