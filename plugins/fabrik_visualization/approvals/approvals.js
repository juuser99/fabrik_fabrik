/**
 * Approvals Visualization
 *
 * @copyright: Copyright (C) 2005-2015, fabrikar.com - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

var fbVisApprovals = my.Class({
	options: {},
	constructor: function (el, options) {
		this.setOptions(options);
		this.el = $('#' + el);
		$(document).on('click', 'a.approve', function (e) {
			var el = $(this);
			e.stop();
			if ($(this).prop('tagName') !== 'A') {
				el = $(this).closest('a');
			}
			new Request.HTML({'url': el.prop('href'),
				'onSuccess': function () {
					el.closest('tr').remove();
				}
			}).send();

		});
		$(document).on('click', 'a.disapprove', function (e) {
			var el = $(this);
			e.stop();
			if (el.prop('tagName') !== 'A') {
				el = el.closest('a');
			}
			new Request.HTML({'url': el.prop('href'),
				'onSuccess': function () {
					el.closest('tr').remove();
				}
			}).send();

		});

		new FloatingTips('.approvalTip', {
			position: 'right',
			content: function (e) {
				var r = e.getNext();
				r.store('trigger', e);
				return r;
			},
			hideOn: 'mousedown'
		});
	}
});