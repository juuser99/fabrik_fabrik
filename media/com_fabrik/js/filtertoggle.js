/**
 * FilterToggle
 *
 * @copyright: Copyright (C) 2005-2015, fabrikar.com - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

/*jshint mootools: true */
/*global Fabrik:true, fconsole:true, Joomla:true, CloneObject:true, $H:true,unescape:true */

/* can be used to hide filters and show then when the list title is clicked
 * also puts the clear filter and go button underneath the focused filter
 */
FabFilterToggle = my.Class({
	constructor: function (ref) {
		var list = $('#list_' + ref),
			form = $('#listform_' + ref);
		Fabrik.addEvent('fabrik.list.update', function (l) {
			if (l.id === ref) {
				list.find('.fabrik___heading span.filter').hide();
			}
			return true;
		});

		list.find('span.heading').each(function (h) {
			var f = h.getNext(), i;
			if (f) {
				h.addClass('filtertitle');
				h.css('cursor', 'pointer');
				if (i = f.find('input')) {
					i.set('placeholder', h.get('text'));
				}
				f.hide();
			}
		});
		list.on('click', 'span.heading', function (e) {
			var f = $(this).getNext();
			if (f.length > 0) {
				f.toggle();
				var i = form.find('.fabrikFilterContainer');
				var offsetP = list.getOffsetParent() ? list.getOffsetParent() : document.body;
				var p = f.getPosition(offsetP);
				i.setPosition({'x': p.x - 5, 'y': p.y + f.getSize().y});
				if (f.css('display') === 'none') {
					i.hide();
				} else {
					i.show();
				}
			}
		});

		var c = form.find('.clearFilters');
		if (typeOf(c) !== 'null') {
			c.on('click', function () {
				form.find('.fabrikFilterContainer').hide();
				form.find('.fabrik___heading .filter').hide();
			});
		}
		var s = form.find('.fabrik_filter_submit');
		if (typeOf(s) !== 'null') {
			s.on('click', function () {
				form.find('.fabrikFilterContainer').hide();
				form.find('.fabrik___heading .filter').hide();
			});
		}
	}
});