/**
 * List Filter View
 *
 * @copyright: Copyright (C) 2005-2015, fabrikar.com - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

var FbListFilterView = my.Class(FbListPlugin, {
	constructor : function (options) {
		this.parent(options);
		$('.filter_view').find('ul.floating-tip').each(function (ul) {
			var c = ul.clone();
			c.fade('hide');
			c.inject(document.body);
			c.setStyles({'position': 'absolute'});
			var trigger = ul.getPrevious();
			trigger.data('target', c);
			trigger.on('click', function (e) {
				e.stop();
				var c = trigger.retrieve('target');
				c.css('top', trigger.getTop());
				c.css('left', trigger.getLeft() + trigger.getWidth() / 1.5);
				c.fade('toggle');
			});
			ul.dispose();
		});
		document.getElements('.fabrik_filter_view').on('click', 'a', function (e) {
			var href = e.target.get('href');
		});
	}
});