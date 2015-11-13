/**
 * Radio Button Element
 *
 * @copyright: Copyright (C) 2005-2015, fabrikar.com - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

var FbRadio = my.Class(FbElementList, {

	options: {
		btnGroup: true
	},

	type: 'radio', // sub element type

	constructor: function (element, options) {
		this.plugin = 'fabrikradiobutton';
		FbRadio.Super.call(this, element, options);
		this.btnGroup();
	},

	btnGroup: function () {
		// Seems slighly skewy in admin as the j template does the same code
		if (!this.options.btnGroup) {
			return;
		}
		// Turn radios into btn-group
		this.btnGroupRelay();

		var c = this.getContainer();
		if (!c) {
			return;
		}
		c.find('.radio.btn-group label').addClass('btn');

		c.find('.btn-group input[checked=checked]').each(function (input) {
			var label = input.closest('label'), v;
			if (label.length === 0) {
				// J3.2 button group markup - label is after input (no longer the case)
				label = input.getNext();
			}
			v = input.val();
			if (v === '') {
				label.addClass('active btn-primary');
			} else if (v === '0') {
				label.addClass('active btn-danger');
			} else {
				label.addClass('active btn-success');
			}
		});
	},

	btnGroupRelay: function () {
		var c = this.getContainer(), self = this,
			id, input;
		if (!c) {
			return;
		}
		c.find('.radio.btn-group label').addClass('btn');
		c.on('click', '.btn-group label', function (e, label) {
			id = $(this).prop('for'), input;
			if (id !== '') {
				input = $('#' + id);
			}
			if (input.length === 0) {
				input = $(this).find('input');
			}
			self.setButtonGroupCSS(input);
		});
	},

	/**
	 *
	 * @param {jQuery} input
	 */
	setButtonGroupCSS: function (input) {
		var label;
		if (input.prop('id') !== '') {
			label = $('label[for=' + input.id + ']');
		}
		if (label.length === 0) {
			label = input.closest('label.btn');
		}
		var v = input.val();
		var fabchecked = parseInt(input.data('fabchecked'), 10);

		// Protostar in J3.2 adds its own btn-group js code -
		// need to thus apply this section even after input has been unchecked
		if (!input.get('checked') || fabchecked === 1) {
			if (label) {
				label.closest('.btn-group').find('label').removeClass('active')
					.removeClass('btn-success').removeClass('btn-danger').removeClass('btn-primary');
				if (v === '') {
					label.addClass('active btn-primary');
				} else if (parseInt(v, 10) === 0) {
					label.addClass('active btn-danger');
				} else {
					label.addClass('active btn-success');
				}
			}
			input.set('checked', true);

			if (typeof(fabchecked) === null) {
				input.set('fabchecked', 1);
			}
		}
	},

	watchAddToggle: function () {
		var c = this.getContainer(),
			self = this,
			d = c.find('div.addoption'),
			a = c.find('.toggle-addoption');
		if (this.mySlider) {
			// Copied in repeating group so need to remove old slider html first
			var clone = d.clone();
			var fe = c.find('.fabrikElement');
			d.parent().destroy();
			fe.append(clone);
			d = c.find('div.addoption');
			d.css('margin', 0);
		}
		$(d).slideUp(500);
		this.mySlider.hide();
		a.on('click', function (e) {
			e.stopPropagation();
			$(d).slideToggle();
		});
	},

	getValue: function () {
		if (!this.options.editable) {
			return this.options.value;
		}
		var v = '';
		this._getSubElements().each(function (sub) {
			if (sub.checked) {
				v = sub.get('value');
				return v;
			}
			return null;
		});
		return v;
	},

	setValue: function (v) {
		if (!this.options.editable) {
			return;
		}
		this._getSubElements().each(function (sub) {
			if (sub.value === v) {
				sub.checked = 'checked';
			}
		});
	},

	update: function (val) {
		if (!this.options.editable) {
			if (val === '') {
				this.element.html('');
				return;
			}
			this.element.html(this.options.data[val]);
			return;
		} else {
			var els = this._getSubElements();
			if ($.isArray(val) === 'array') {
				els.each(function (el) {
					if (val.contains(el.value)) {
						this.setButtonGroupCSS(el);
					}
				}.bind(this));
			} else {
				els.each(function (el) {
					if (el.value === val) {
						this.setButtonGroupCSS(el);
					}
				}.bind(this));
			}
		}
	},

	cloned: function (c) {
		if (this.options.allowadd === true && this.options.editable !== false) {
			this.watchAddToggle();
			this.watchAdd();
		}
		FbRadio.Super.prototype.cloned(this, c);
		this.btnGroup();
	},

	getChangeEvent: function () {
		return this.options.changeEvent;
	}

});
