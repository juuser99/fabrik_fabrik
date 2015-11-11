/**
 * Ratings Element - List
 *
 * @copyright: Copyright (C) 2005-2013, fabrikar.com - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

var FbRatingList = my.Class({

	options: {
		'userid': 0,
		'mode' : '',
		'formid': 0
	},

	constructor: function (id, options) {
		var self = this;
		options.element = id;
		this.setOptions(options);
		this.options = $.extend(this.options, options);
		if (this.options.canRate === false) {
			return;
		}
		if (this.options.mode === 'creator-rating') {
			return;
		}
		this.col = $('.' + id);
		this.origRating = {};
		this.col.each(function (tr) {
			var stars = tr.getElements('.starRating');

			stars.each(function (star) {
				star.on('mouseover', function (e) {
					self.origRating[tr.id] = parseInt(star.closest('.fabrik_element').find('.ratingMessage').html(), 10);
					stars.each(function (ii) {
						if (self._getRating(star) >= self._getRating(ii)) {
							ii.removeClass('icon-star-empty').addClass('icon-star');
						} else {
							ii.addClass('icon-star-empty').removeClass('icon-star');
						}
					});
					star.closest('.fabrik_element').find('.ratingMessage').html(star.data('fabrik-rating'));
				});

				star.on('mouseout', function (e) {
					stars.each(function (ii) {
						if (self.origRating[tr.id] >= self._getRating(ii)) {
							ii.removeClass('icon-star-empty').addClass('icon-star');
						} else {
								ii.addClass('icon-star-empty').removeClass('icon-star');
						}
					});
					star.closest('.fabrik_element').find('.ratingMessage').html(this.origRating[tr.id]);
				});
			});

			stars.each(function (star) {
				star.on('click', function (e) {
					self.doAjax(e, star);
				});
			});

		});

	},

	/**
	 *
	 * @param {jQuery} i
	 * @returns {*}
	 * @private
	 */
	_getRating: function (i) {
		var r = i.data('fabrik-rating');
		return r.toInt();
	},

	doAjax : function (e, star) {
		var self = this;
		e.stopPropagation();
		this.rating = this._getRating(star);
		var ratingmsg = star.closest('.fabrik_element').find('.ratingMessage');
		Fabrik.loader.start(ratingmsg);

		var starRatingCover = $(document.createElement('div')).attr({id: 'starRatingCover'})
			.css({bottom: 0, top: 0, right: 0, left: 0, position: 'absolute', cursor: 'progress'});
		var starRatingContainer = star.closest('.fabrik_element').find('div');
		starRatingContainer.grab(starRatingCover, 'top');

		var row = $('#' + star).closest('.fabrik_row');
		var rowid = row.id.replace('list_' + this.options.listRef + '_row_', '');
		var data = {
			'option': 'com_fabrik',
			'format': 'raw',
			'task': 'plugin.pluginAjax',
			'plugin': 'rating',
			'g': 'element',
			'method': 'ajax_rate',
			'formid': this.options.formid,
			'element_id': this.options.elid,
			'row_id' : rowid,
			'elementname' : this.options.elid,
			'userid' : this.options.userid,
			'rating' : this.rating,
			'mode' : this.options.mode
		};
		new $.ajax({url: '',
			'data': data
		}).done(function (r) {
				r = r.toInt();
				self.rating = r;
				ratingmsg.html(this.rating);
				Fabrik.loader.stop(ratingmsg);
				star.closest('.fabrik_element').find('i').each(function (i, x) {
					if (x < r) {
						$(this).removeClass('icon-star-empty').addClass('icon-star');
					} else {
						$(this).addClass('icon-star-empty').removeClass('icon-star');
					}
				});
				$('#starRatingCover').destroy();
			});
	}
});