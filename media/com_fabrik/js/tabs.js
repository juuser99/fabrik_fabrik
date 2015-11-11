/**
 * Tabs
 *
 * @copyright: Copyright (C) 2005-2015, fabrikar.com - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

var Tabs = my.Class({
	constructor : function (el, tabs, editable) {
		this.editable = editable;
		this.iconGen = new IconGenerator({scale: 0.5});
		this.el = document.id(el);
		this.tabs = {};
		this.build(tabs);
	},

	build: function (tabs) {
		Fabrik.trigger('fabrik.history.off', this);
		if (this.editable) {

			var a = new Element('a', {
				'href': '#',
				'events': {
					'click': function (e) {
						this.addWindow(e);
					}.bind(this)
				}
			});

			art = this.iconGen.create(icon.plus, {fill: {color: ['#40B53E', '#378F36']}});
			art.inject(a);

			this.el.adopt(new Element('li', {
				'class': 'add',
				'events': {
					'click': function (e) {
						this.addWindow(e);
					}.bind(this)
				}
			}).adopt([new Element('span').text('add'), a]));
		}
		tabs.each(function (t) {
			this.add(t);
		}.bind(this));
		this.setActive(tabs[0]);
		var fn = function () {
			Fabrik.trigger('fabrik.history.on', this);
		};
		fn.delay(500);
	},

	remove: function (e) {
		var n;
		if (typeOf(e) === 'event') {
			n = e.target.closest('li').find('span').get('text').trim();
			e.stop();
		} else {
			n = e;
		}
		if (window.confirm('Delete tab?')) {
			if (Object.keys(this.tabs).length <= 1) {
				window.alert('you can not remove all tabs');
				return;
			}
			var t = this.tabs[n];
			Fabrik.trigger('fabrik.tab.remove', [ this, t ]);
			delete this.tabs[n];
			t.destroy();
			var newkey = Object.keys(this.tabs)[0];
			this.setActive(this.tabs[newkey]);
		}
	},

	addWindow: function (e) {
		var c = new Element('form');
		c.adopt(new Element('input', {
			'name' : 'label',
			'events': {
				'keydown': function (e) {
					if (e.key === 'enter') {
						e.stop();
					}
				}
			}
		}), new Element('br'), new Element('input', {
			'class' : 'button',
			'type' : 'button',
			'events' : {
				'click' : function (e) {
					var name = e.target.parent().getElement('input[name=label]').get('value');
					if (name === '') {
						window.alert('please supply a tab label');
						return false;
					}
					this.add(name);
					Fabrik.Windows[this.windowopts.id].close();
				}.bind(this)
			},
			'value' : 'add'
		}));
		this.windowopts = {
			'id': 'addTab',
			'type': 'modal',
			title: 'Add',
			content: c,
			width: 200,
			height: 200,
			'minimizable': false,
			'collapsible': true
		};
		var mywin = Fabrik.getWindow(this.windowopts);
	},

	add: function (t) {
		var li = new Element('li', {

			'events': {
				'click': function (e) {
					this.setActive(li);
				}.bind(this),

				'mouseover': function (e) {
					Fabrik.trigger('fabrik.tab.hover', [ t ]);
				}
			}
		});
		li.adopt(new Element('span').text(t + ' '));

		var a = new Element('a', {
			'href': '#',
			'events': {
				'click': function (e) {
					this.remove(e);
				}.bind(this)
			}
		});

		if (this.editable) {
			art = this.iconGen.create(icon.cross);
			art.inject(a);
			li.adopt(a);
		}
		li.store('ref', t);
		if (this.editable) {
			var add = this.el.getElement('li.add');
			li.inject(add, 'before');
		} else {
			li.inject(this.el, 'inside');
		}
		this.setActive(li);
		this.tabs[t] = li;
		Fabrik.trigger('fabrik.history.add', [this, this.remove, t, this.add, t]);
		Fabrik.trigger('fabrik.tab.add', [this, t]);
	},

	setActive: function (a) {
		var tname = typeOf(a) === 'string' ? a : a.retrieve('ref');
		var active = a;
		Fabrik.trigger('fabrik.tab.click', tname);
		jQuery.each(this.tabs, function (key, t) {
			t.removeClass('active');
			t.addClass('inactive');
			if (t.retrieve('ref') === tname) {
				active = t;
			}
		});
		active.addClass('active');
		active.removeClass('inactive');
	},

	reorder: function () {

	}
});