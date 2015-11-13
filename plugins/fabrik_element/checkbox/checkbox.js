/**
 * Checkbox Element
 *
 * @copyright: Copyright (C) 2005-2015, fabrikar.com - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

var FbCheckBox = my.Class(FbElementList, {

	type: 'checkbox', // Sub element type

	constructor: function (element, options) {
		this.plugin = 'fabrikcheckbox';
		FbCheckBox.Super.call(this, element, options);
		this._getSubElements();
	},

	watchAddToggle: function () {
		var c = this.getContainer(),
			self = this,
			d = c.find('div.addoption'),
			a = c.find('.toggle-addoption'), clone, fe;
		if (this.mySlider) {
			// Copied in repeating group so need to remove old slider html first
			clone = d.clone();
			fe = c.find('.fabrikElement');
			d.parent().destroy();
			fe.append(clone);
			d = c.find('div.addoption');
			d.css('margin', 0);
		}

		$(d).slideUp(500);
		a.on('click', function (e) {
			e.stopPropagation();
			$(d).slideToggle();
		});
	},

	getValue: function () {
		if (!this.options.editable) {
			return this.options.value;
		}
		var ret = [];
		if (!this.options.editable) {
			return this.options.value;
		}
		this._getSubElements().each(function (el) {
			if (el.checked) {
				ret.push(el.get('value'));
			}
		});
		return ret;
	},

	numChecked: function () {
		return this._getSubElements().filter(function (c) {
			return c.checked;
		}).length;
	},

	update: function (val) {
		var self = this;
		this.find();
		if (typeof(val) === 'string') {
			val = val === '' ? [] : JSON.decode(val);
		}
		if (!this.options.editable) {
			this.element.html('');
			if (val === '') {
				return;
			}
			jQuery.each(val, function (key, v) {
				self.element.html(self.element.html() + self.options.data[v] + '<br />');
			});
			return;
		}
		this._getSubElements();
		jQuery.each(this.subElements, function (key, el) {
			var chx = false;
			jQuery.each(val, function (key, v) {
				if (v === el.value) {
					chx = true;
				}
			});
			el.checked = chx;
		});
	},

	cloned: function (c) {
		if (this.options.allowadd === true && this.options.editable !== false) {
			this.watchAddToggle();
			this.watchAdd();
		}
		FbCheckBox.Super.prototype.cloned(this, c);
	}

});
