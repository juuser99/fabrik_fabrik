/**
 * Pages
 *
 * @copyright: Copyright (C) 2005-2015, fabrikar.com - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

var Pages = my.Class({
	constructor: function (container, editable) {
		this.editable = editable;
		var self = this;
		$(document).on('mousedown', function (e) {
			self.clearActive(e);
		});
		Fabrik.addEvent('fabrik.page.add', function (e) {
			self.makeActive(e);
		});
		this.pages = {};
		this.activePage = null;
		this.container = document.id(container);
		Fabrik.addEvent('fabrik.tab.add', function (e) {
			this.add(e);
		}.bind(this));
		Fabrik.addEvent('fabrik.tab.click', function (e) {
			this.show(e);
		}.bind(this));
		Fabrik.addEvent('fabrik.tab.remove', function (e) {
			this.remove(e);
		}.bind(this));
		Fabrik.addEvent('fabrik.keynav', function (e) {
			this.moveItem(e);
		}.bind(this));
		Fabrik.addEvent('fabrik.inline.save', function (e) {
			this.updateTabKey(e);
		}.bind(this));
	},

	/* todo perhaps makeActive and clearActive should be a mixin? */
	makeActive: function (c) {
		this.clearActive();
		c.addClass('active');
		this.active = c;
		var zindexes = document.getElements('.itemPlaceHolder').getStyle('z-index').sort();
		var max = zindexes.getLast().toInt() + 1;
		document.getElements('.itemPlaceHolder').each(function (i) {
			i.setStyle('zindex', i.getStyle('z-index').toInt() - 1);
		});
		c.setStyle('z-index', max);
	},

	clearActive: function () {
		delete this.active;
		document.getElements('.itemPlaceHolder').removeClass('active');
	},

	moveItem: function (k, shift) {
		if (this.active && this.editable) {
			shift = shift ? 10 : 0;
			var p = this.active.getCoordinates(this.getActivePage().page);
			switch (k) {
			case 37: //left
				this.active.setStyle('left', p.left - 2 - shift);
				break;
			case 38: //up
				this.active.setStyle('top', p.top - 2 - shift);
				break;
			case 39: //right
				this.active.setStyle('left', p.left + 1 + shift);
				break;
			case 40: //down
				this.active.setStyle('top', p.top + 1 + shift);
				break;
			}
		}
	},

	add: function (tabs, t) {
		var page = new Page(t, this.editable);
		this.container.adopt(page.page);
		page.show();
		this.pages[t] = page;
		this.show();
	},

	remove: function (tabs, t) {
		t = t.retrieve('ref');
		//this.pages[t].remove();
		delete this.pages[t];
	},

	show: function (tab) {
		jQuery.each(this.pages, function (key, page) {
			page.hide();
		});
		try {
			this.pages[tab].show();
			this.activePage = tab;
		} catch (err) {
			var k = Object.keys(this.pages);
			if (k.length > 0) {
				tab = k[0];
				this.pages[tab].show();
				this.activePage = tab;
			}
		}
	},

	getHTMLPages: function () {
		var r = [];
		jQuery.each(this.pages, function (key, p) {
			r.push(p.page);
		});
		return r;
	},

	getActivePage: function () {
		if (!this.activePage) {
			this.activePage = 0;
		}
		return this.pages[this.activePage];
	},

	fromJSON: function (layout) {
		jQuery.each(layout, function (page, items) {
			if (this.pages[page]) {
				jQuery.each(items, function (id, item) {
					this.pages[page].insert(item.id, item.label, item.type, item.dimensions);
				}.bind(this));
			}
		}.bind(this));
	},

	toJSON: function () {
		var r = {};
		jQuery.each(this.pages, function (k, p) {
			var o = {};
			jQuery.each(p.page.getElements('.itemPlaceHolder'), function (key, e) {
				p.page.show(); //needed to get coords
				var type = e.id.split('_')[0];
				var label = e.getElement('.handlelabel').get('text');
				o[e.id] = {'dimensions': e.getCoordinates(p.page), 'label': label, 'type': type, 'id': e.id};
			});
			r[k.trim()] = o;
		});
		return r;
	},

	/**
	 * called when tab label is changed
	 */
	updateTabKey: function (editor) {
		var origKey = editor.retrieve('origValue').trim();
		var orig = this.pages[origKey];
		this.pages[editor.get('text').trim()] = orig;
		delete this.pages[origKey];
	}
});

Page = my.Class({
	constructor: function (t, editable) {
		this.editable = editable;
		this.page = new Element('div', {'class': 'page', 'styles': {'display': 'none'}});
		if (this.editable) {
			Fabrik.addEvent('fabrik.item.resized', function (e) {
				this.saveCoords(e);
			}.bind(this));
			Fabrik.addEvent('fabrik.item.moved', function (e) {
				this.saveCoords(e);
			}.bind(this));
		}
	},

	show: function () {
		this.page.show();
	},

	hide: function () {
		this.page.hide();
	},

	remove: function () {
		this.page.destroy();
	},

	removeItem: function (e, id) {
		e.stop();
		if (confirm('Do you really want to delete')) {
			document.id(id).destroy();
			Fabrik.trigger('fabrik.page.block.delete', [id]);
		}
	},

	insert: function (id, label, type, dimensions) {
		Fabrik.trigger('fabrik.page.insert', [this, id, label, type, dimensions]);
	},

	saveCoords: function (e) {
	}

});