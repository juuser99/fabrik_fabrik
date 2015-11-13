/**
 * History
 *
 * @copyright: Copyright (C) 2005-2015, fabrikar.com - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

/*jshint mootools: true */
/*global Fabrik:true, fconsole:true, Joomla:true, CloneObject:true, $H:true,unescape:true */

var History = my.Class({
	constructor: function (undobutton, redobutton) {
		var self = this;
		this.recording = true;
		this.pointer = -1;
		$(undobutton).on('click', function (e) {
			self.undo(e);
		});
			$(redobutton).on('click', function (e) {
				self.redo(e);
			});
		Fabrik.addEvent('fabrik.history.on', function (e) {
			self.on(e);
		});
		Fabrik.addEvent('fabrik.history.off', function (e) {
			self.off(e);
		});
		Fabrik.addEvent('fabrik.history.add', function (e) {
			self.add(e);
		});
		this.history = [];
	},

	undo : function () {
		if (this.pointer > -1) {
			this.off();
			var h = this.history[this.pointer];
			var f = h.undofunc;
			var p = h.undoparams;
			var res = f.attempt(p, h.object);
			this.on();
			this.pointer --;
		}

	},

	redo : function () {
		if (this.pointer < this.history.length - 1) {
			this.pointer ++;
			this.off();
			var h = this.history[this.pointer];
			var f = h.redofunc;
			var p = h.redoparams;
			var res = f.attempt(p, h.object);
			this.on();
		}
	},

	add : function (obj, undofunc, undoparams, redofunc, redoparams) {
		var self = this;
		if (this.recording) {
			// remove history which is newer than current pointer location
			var newh = this.history.filter(function (h, x) {
				return x <= self.pointer;
			});
			this.history = newh;
			this.history.push({
				'object' : obj,
				'undofunc' : undofunc,
				'undoparams' : undoparams,
				'redofunc' : redofunc,
				'redoparams' : redoparams
			});
			this.pointer++;
		}
	},

	on : function () {
		this.recording = true;
	},

	off : function () {
		this.recording = false;
	}
});
