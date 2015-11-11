/**
 * Admin Listfields Dropdown Editor
 *
 * @copyright: Copyright (C) 2005-2015, fabrikar.com - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

var ListFieldsElement = my.Class({

	addWatched: false,

	options: {
		conn: null,
		highlightpk: false,
		showAll: 1,
		mode: 'dropdown',
		defaultOpts: [],
		addBrackets: false
	},

	constructor: function (el, options) {
		this.strEl = el;
		var label, els;
		this.el = el;
		this.options = $.extend(this.options, options);

		if (this.options.defaultOpts.length > 0) {
			this.el = $('#' + this.el);
			if (this.options.mode === 'gui') {
				this.select = this.el.parent().find('select.elements');
				els = [this.select];
				if ($('#' + this.options.conn).length === 0) {
					this.watchAdd();
				}
			} else {
				els = document.getElementsByName(this.el.name);
				this.el.empty();
				$('#' + this.strEl).empty();
			}
			var opts = this.options.defaultOpts;

			Array.each(els, function (el) {
				$('#' + el).empty();
			});

			opts.each(function (opt) {
				var o = {'value': opt.value};
				if (opt.value === this.options.value) {
					o.selected = 'selected';
				}
				Array.each(els, function (el) {
					label = opt.label ? opt.label : opt.text;
					$(document.createElement('option')).attr(o).text(label).inject(el);
				});
			}.bind(this));
		} else {
			if ($('#' + this.options.conn).length === 0) {
				this.cnnperiodical = setInterval(function () {
					this.getCnn.call(this, true);
				}, 500);
			} else {
				this.setUp();
			}
		}
	},

	/**
	 * Triggered when a fieldset is repeated (e.g. in googlemap viz where you can
	 * select more than one data set)
	 */
	cloned: function (newid, counter)
	{
		this.strEl = newid;
		this.el = $('#' + newid);
		this._cloneProp('conn', counter);
		this._cloneProp('table', counter);
		this.setUp();
	},

	/**
	 * Helper method to update option HTML id's on clone()
	 */
	_cloneProp: function (prop, counter) {
		var bits = this.options[prop].split('-');
		bits = bits.splice(0, bits.length - 1);
		bits.push(counter);
		this.options[prop] = bits.join('-');
	},

	getCnn: function () {
		if ($('#' + this.options.conn).length === 0) {
			return;
		}
		this.setUp();
		clearInterval(this.cnnperiodical);
	},

	setUp: function () {
		var self = this;
		this.el = $('#' + this.el);
		if (this.options.mode === 'gui') {
			this.select = this.el.parent().find('select.elements');
		}

		$('#' + this.options.conn).on('change', function () {
			self.updateMe();
		});
		$('#' + this.options.table).on('change', function () {
			self.updateMe();
		});

		// See if there is a connection selected
		var v = $('#' + this.options.conn).val();
		if (v !== '' && v !== -1) {
			this.periodical = setInterval(function () {
				this.updateMe.call(this, true);
			}, 500);
		}
		this.watchAdd();
	},

	watchAdd: function () {
		var self = this;
		if (this.addWatched === true) {
			return;
		}
		console.log('watch add', this);
		this.addWatched = true;
		var add = this.el.parent().find('button');

		add.on('mousedown', function (e) {
			e.stopPropagation();
			self.addPlaceHolder();
		});
		add.on('click', function (e) {
			e.stopPropagation();
		});
	},

	/**
	 *
	 * @param {event} e
	 */
	updateMe: function (e) {
		var self = this;
		if (typeof(e) === 'object') {
			e.stopPropagation();
		}
		$('#' + this.el.id + '_loader').show();
		var cid = $('#' + this.options.conn).val(),
			tid = $('#' + this.options.table).val();
		if (!tid) {
			return;
		}
		clearInterval(this.periodical);
		var url = 'index.php?option=com_fabrik&format=raw&task=plugin.pluginAjax&g=element&plugin=field&method=ajax_fields&showall=' + this.options.showAll + '&cid=' + cid + '&t=' + tid;
		var myAjax = $.ajax({
			url: url,
			method: 'get',
			data: {
				'highlightpk': this.options.highlightpk,
				'k': 2
			}
		}).done(function (r) {
			var els;

			// Googlemap inside repeat group & modal repeat
			if ($('#' + self.strEl).length !== 0) {
				self.el = $('#' + self.strEl);
			}
			if (self.options.mode === 'gui') {
				els = [self.select];
			} else {
				els = document.getElementsByName(self.el.name);
				self.el.empty();
				$('#' + self.strEl).empty();
			}
			var opts = eval(r);

			Array.each(els, function (el) {
				$('#' + el).empty();
			});

			opts.each(function (opt) {
				var o = {'value': opt.value};
				if (opt.value === self.options.value) {
					o.selected = 'selected';
				}
				Array.each(els, function (el) {
					$(document.createElement('option')).attr(o).text(opt.label).inject(el);
				});
			});
			$('#' + self.el.id + '_loader').hide();
		});
		Fabrik.requestQueue.add(myAjax);
	},

	/**
	 * If rendering with mode=gui then add button should insert selected element placeholder into
	 * text area
	 */
	addPlaceHolder: function () {
		var list = this.el.parent().find('select');
		var v = list.get('value');
		if (this.options.addBrackets) {
			v = v.replace(/\./, '___');
			v = '{' + v + '}';
		}
		this.insertTextAtCaret(this.el, v);
	},

	/**
	 * Start of text insertion code - taken from
	 * http://stackoverflow.com/questions/3510351/how-do-i-add-text-to-a-textarea-at-the-cursor-location-using-javascript
	 */
	getInputSelection: function (el) {
		var start = 0, end = 0, normalizedValue, range,
		textInputRange, len, endRange;

		if (typeof el.selectionStart === 'number' && typeof el.selectionEnd === 'number') {
			start = el.selectionStart;
			end = el.selectionEnd;
		} else {
			range = document.selection.createRange();

			if (range && range.parentElement() === el) {
				len = el.value.length;
				normalizedValue = el.value.replace(/\r\n/g, '\n');

				// Create a working TextRange that lives only in the input
				textInputRange = el.createTextRange();
				textInputRange.moveToBookmark(range.getBookmark());

				// Check if the start and end of the selection are at the very end
				// of the input, since moveStart/moveEnd doesn't return what we want
				// in those cases
				endRange = el.createTextRange();
				endRange.collapse(false);

				if (textInputRange.compareEndPoints('StartToEnd', endRange) > -1) {
					start = end = len;
				} else {
					start = -textInputRange.moveStart('character', -len);
					start += normalizedValue.slice(0, start).split('\n').length - 1;

					if (textInputRange.compareEndPoints('EndToEnd', endRange) > -1) {
						end = len;
					} else {
						end = -textInputRange.moveEnd('character', -len);
						end += normalizedValue.slice(0, end).split('\n').length - 1;
					}
				}
			}
		}

		return {
			start: start,
			end: end
		};
	},

	offsetToRangeCharacterMove: function (el, offset) {
		return offset - (el.value.slice(0, offset).split('\r\n').length - 1);
	},

	setSelection: function (el, start, end) {
		if (typeof el.selectionStart === 'number' && typeof el.selectionEnd === 'number') {
			el.selectionStart = start;
			el.selectionEnd = end;
		} else if (typeof el.createTextRange !== 'undefined') {
			var range = el.createTextRange();
			var startCharMove = this.offsetToRangeCharacterMove(el, start);
			range.collapse(true);
			if (start === end) {
				range.move('character', startCharMove);
			} else {
				range.moveEnd('character', this.offsetToRangeCharacterMove(el, end));
				range.moveStart('character', startCharMove);
			}
			range.select();
		}
	},

	insertTextAtCaret: function (el, text) {
		var pos = this.getInputSelection(el).end;
		var newPos = pos + text.length;
		var val = el.value;
		el.value = val.slice(0, pos) + text + val.slice(pos);
		this.setSelection(el, newPos, newPos);
	}
});



/*
function insertTextAtCaret(el, text) {

}

var textarea = document.getElementById("your_textarea");
insertTextAtCaret(textarea, "[INSERTED]");*/