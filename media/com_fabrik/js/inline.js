/**
 * Simple Inline Editor
 *
 * @copyright: Copyright (C) 2005-2015, fabrikar.com - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

/**
 * simple inline editor, double click nodes which match the selector to toggle to a field
 * esc to revert
 * enter to save
 *
 */
var inline = my.Class({

	options: {
	},

	constructor: function (selector, options)
	{
		var self = this;
		this.options = $.extend(this.options, options);
		$(document).on('dblclick', selector, function (e, target) {
			var editor;
			target.hide();
			target.store('origValue', target.get('text'));
			if (!target.retrieve('inline')) {
				editor = new Element('input');
				editor.on('keydown', function (e) {
					self.checkKey(e, target);
				});
				editor.inject(target, 'after').focus();
				editor.hide();
				target.store('inline', editor);
			} else {
				editor = target.retrieve('inline');
			}
			editor.set('value', target.get('text')).toggle().focus();
			editor.select();
		});
	},

	checkKey: function (e, target) {
		if (e.key === 'enter' || e.key === 'esc' || e.key === 'tab') {
			target.retrieve('inline').hide();
			target.show();
		}
		if (e.key === 'enter' || e.key === 'tab') {
			target.text(e.target.get('value'));
			Fabrik.trigger('fabrik.inline.save', [target, e]);
		}
	}
});
