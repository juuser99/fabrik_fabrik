/**
 * Fabrik Dropdown Element
 *
 * @copyright: Copyright (C) 2005-2013, fabrikar.com - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

var FbDropdown = my.Class(FbElement, {
	constructor: function (element, options) {
		this.plugin = 'fabrikdropdown';
		FbDropdown.Super.call(this, element, options);
		if (this.options.allowadd === true && this.options.editable !== false) {
			this.watchAddToggle();
			this.watchAdd();
		}
	},

	watchAddToggle : function () {
		var self = this,
			c = this.getContainer(),
			d = c.find('div.addoption'),
			a = c.find('.toggle-addoption'),
			clone, fe, ad;
		if (this.mySlider) {
			//copied in repeating group so need to remove old slider html first
			clone = d.clone();
			fe = c.find('.fabrikElement');
			d.parent().destroy();
			fe.adopt(clone);
			d = c.find('div.addoption');
			d.css('margin', 0);
			ad = d.find('input[name*=_additions]');
			ad.id = this.element.id + '_additions';
			ad.name = this.element.id + '_additions';

		}
		$(d).slideUp(500);
		a.on('click', function (e) {
			e.stopPropagation();
			$(d).slideToggle();
		});
	},

	addClick: function (e) {
		var c = this.getContainer(), val;
		var l = c.find('input[name=addPicklistLabel]');
		var v = c.find('input[name=addPicklistValue]');
		var label = l.val();
		if (v.length > 0) {
			val = v.val();
		} else {
			val = label;
		}
		if (val === '' || label === '') {
			window.alert(Joomla.JText._('PLG_ELEMENT_DROPDOWN_ENTER_VALUE_LABEL'));
		}
		else {
			$(document.createElement('option')).attr({
				'selected': 'selected',
				'value': val
			}).text(label).prependTo($('#' + this.element.id));
			e.stopPropagation();
			if (v) {
				v.value = '';
			}
			l.value = '';
			this.addNewOption(val, label);
			$('#' + this.element.id).trigger('change', {stop: function () {}});
			if (this.mySlider) {
				this.mySlider.toggle();
			}
			if (this.options.advanced)
			{
				$('#' + this.element.id).trigger('liszt:updated');
			}
		}
	},

	watchAdd: function () {
		if (this.options.allowadd === true && this.options.editable !== false) {
			var c = this.getContainer();
			if (this.addClickEvent) {
				c.find('input[type=button]').removeEvent('click', this.addClickEvent);
			}
			this.addClickEvent = this.addClick.bind(this);
			c.find('input[type=button]').on('click', this.addClickEvent);
		}
	},

	getValue: function () {
		if (!this.options.editable) {
			return this.options.value;
		}
		if (this.element.val() === null) {
			return '';
		}
		if (this.options.multiple) {
			var r = [];
			this.element.find('option').each(function (opt) {
				if (opt.selected) {
					r.push(opt.value);
				}
			});
			return r;
		}
		return this.element.val();
	},

	reset: function ()
	{
		var v = this.options.defaultVal;
		this.update(v);
	},

	update: function (val) {
		var opts = [],
			self = this;
		if  ((typeof(val) === 'string') && (JSON.validate(val))) {
			val = JSON.decode(val);
		}
		if (val === undefined) {
			val = [];
		}

		this.find();

		this.options.element = this.element.id;
		if (!this.options.editable) {
			this.element.html('');
			jQuery.each(val, function () {
				self.element.html(self.element.html() + self.options.data[this] + '<br />');
			});
			return;
		}
		opts = this.element.find('option');
		if (typeof(val) === 'number') {

			// Numbers dont have indexOf() methods so ensure they are strings
			val = val.toString();
		}
		for (var i = 0; i < opts.length; i++) {
			if (val.indexOf(opts[i].value) !== -1) {
				opts[i].selected = true;
			} else {
				opts[i].selected = false;
			}
		}
		this.watchAdd();
	},

	cloned: function (c)
	{
		if (this.options.allowadd === true && this.options.editable !== false) {
			this.watchAddToggle();
			this.watchAdd();
		}
		FbDropdown.Super.prototype.cloned(this, c);
	}

});