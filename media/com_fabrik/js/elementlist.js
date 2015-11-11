/**
 * Element List
 *
 * @copyright: Copyright (C) 2005-2013, fabrikar.com - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

/*jshint mootools: true */
/*global Fabrik:true, fconsole:true, Joomla:true, CloneObject:true, $H:true,unescape:true,Asset:true */
FbElementList = my.Class(FbElement, {

	type: 'text', // Sub element type

	constructor: function (element, options) {
		FbElementList.Super.call(this, element, options);
		this.addSubClickEvents();
		this._getSubElements();
		if (this.options.allowadd === true && this.options.editable !== false) {
			this.watchAddToggle();
			this.watchAdd();
		}
	},

	// Get the sub element which are the checkboxes themselves

	_getSubElements: function () {
		var element = this.find();
		if (element.length === 0) {
			this.subElements = [];
		} else {
			this.subElements = element.find('input[type=' + this.type+ ']');
		}
		return this.subElements;
	},

	addSubClickEvents: function () {
		this._getSubElements().each(function (el) {
			$(this).on('click', function (e) {
				Fabrik.trigger('fabrik.element.click', [this, e]);
			});
		});
	},

	addNewEvent: function (action, js) {
		var r, delegate, uid, c,
			self = this;
		if (action === 'load') {
			this.loadEvents.push(js);
			this.runLoadEvent(js);
		} else {
			c = this.form.form;

			// Added name^= for http://fabrikar.com/forums/showthread.php?t=30563 (js events to show hide multiple groups)
			delegate = action + ':input[type=' + this.type + '][name^=' + this.options.fullName + ']';
			if (typeOf(this.form.events[action]) === 'null') {
				this.form.events[action] = {};
			}

			// Could be added via a custom js file.
			if (typeof(js) === 'function') {
				uid = Math.random(100) * 1000;
			} else {
				r = new RegExp('[^a-z|0-9]', 'gi');
				uid = delegate + js.replace(r, '');
			}
			if (this.form.events[action][uid] === undefined) {
				this.form.events[action][uid] = true;

				c.on(action, 'input[type=' + this.type + '][name^=' + this.options.fullName + ']', function (event, target) {
					// As we are delegating the event, and reference to 'this' in the js will refer to the first element
					// When in a repeat group we want to replace that with a reference to the current element.
					var elid = target.closest('.fabrikSubElementContainer').id;
					var that = self.form.formElements[elid];
					var subEls = that._getSubElements();
					if (subEls.contains(target)) {

						// Replace this with that so that the js code runs on the correct element
						if (typeof(js) !== 'function') {
							js = js.replace(/this/g, 'that');
							eval(js);
						} else {
							js.delay(0);
						}
					}
				});
			}
		}
	},

	checkEnter: function (e) {
		if (e.key === 'enter') {
			e.stop();
			this.startAddNewOption();
		}
	},

	startAddNewOption: function () {
		var c = this.getContainer(), val;
		var l = c.find('input[name=addPicklistLabel]');
		var v = c.find('input[name=addPicklistValue]');
		var label = l.value;
		if (v) {
			val = v.value;
		} else {
			val = label;
		}
		if (val === '' || label === '') {
			alert(Joomla.JText._('PLG_ELEMENT_CHECKBOX_ENTER_VALUE_LABEL'));
		}
		else {
			var r = this.subElements.getLast().findClassUp('fabrikgrid_' + this.type).clone();
			var i = r.find('input');
			i.value = val;
			i.checked = 'checked';
			if (this.type === 'checkbox') {

				// Remove the last [*] from the checkbox sub option name (seems only these use incremental []'s)
				var name = i.name.replace(/^(.*)\[.*\](.*?)$/, '$1$2');
				i.name = name + '[' + (this.subElements.length) + ']';
			}
			r.find('.' + this.type + ' span').text(label);
			r.inject(this.subElements.getLast().findClassUp('fabrikgrid_' + this.type), 'after');

			var index = 0;
			if (this.type === 'radio') {
				index = this.subElements.length;
			}
			var is = $('input[name=' + i.name + ']');
			$('#' + this.form.form).trigger('change', {target: is[index]});

			this._getSubElements();
			if (v) {
				v.value = '';
			}
			l.value = '';
			this.addNewOption(val, label);
			if (this.mySlider) {
				this.mySlider.toggle();
			}
		}
	},

	watchAdd: function () {
		var self = this;
		if (this.options.allowadd === true && this.options.editable !== false) {
			var c = this.getContainer();
			c.find('input[name=addPicklistLabel], input[name=addPicklistValue]').on('keypress', function (e) {
				self.checkEnter(e);
			});
			c.find('input[type=button]').on('click', function (e) {
				e.stop();
				self.startAddNewOption();
			});
			$(document).on('keypress', function (e) {
				if (e.key === 'esc' && self.mySlider) {
					self.mySlider.slideOut();
				}
			});
		}
	}

});
