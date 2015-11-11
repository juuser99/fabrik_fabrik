/**
 * Rating Element
 *
 * @copyright: Copyright (C) 2005-2015, fabrikar.com - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

var FbRating = my.Class(FbElement, {
	constructor: function (element, options) {
		this.field = $('#' + element);
		this.parent(element, options);
		if (this.options.canRate === false) {
			return;
		}
		if (this.options.mode === 'creator-rating' && this.options.view === 'details') {

			// Deactivate if in detail view and only the record creator can rate
			return;
		}
		this.rating = this.options.rating;
		Fabrik.addEvent('fabrik.form.refresh', function (e) {
			this.setup(e);
		}.bind(this));
		this.setup(this.options.row_id);
		this.setStars();
	},

	setup : function (rowid) {
		var self = this;
		this.options.row_id = rowid;
		this.element = $('#' + this.options.element + '_div');
		this.spinner = new Asset.image(Fabrik.liveSite + 'media/com_fabrik/images/ajax-loader.gif', {
			'alt': 'loading',
			'class': 'ajax-loader'
		});
		this.stars = this.element.getElements('.starRating');
		this.ratingMessage = this.element.getElement('.ratingMessage');
		this.stars.each(function (i) {
			i.on('mouseover', function (e) {
				self.stars.each(function (ii) {
					if (self._getRating(i) >= self._getRating(ii)) {
						ii.removeClass('icon-star-empty').addClass('icon-star');
					} else {
						ii.addClass('icon-star-empty').removeClass('icon-star');
					}
				});
				self.ratingMessage.html(i.data('rating'));
			});
		});

		this.stars.each(function (i) {
			i.on('mouseout', function () {
				self.stars.each(function (ii) {
					ii.removeClass('icon-star').addClass('icon-star-empty');
				});
			});
		});

		this.stars.each(function (i) {
			i.on('click', function () {
				self.rating = self._getRating(i);
				self.field.val(self.rating);
				self.doAjax();
				self.setStars();
			});
		});
		var clearButton = this.getClearButton();
		this.element.on('mouseout', function (e) {
			self.setStars();
		});

		this.element.on('mouseover', function (e) {
			clearButton.setStyles({
				visibility : 'visible'
			});
		});

		clearButton.on('mouseover', function (e) {
			self.ratingMessage.html(Joomla.JText._('PLG_ELEMENT_RATING_NO_RATING'));
		});

		clearButton.on('mouseout', function (e) {
			if (self.rating !== -1) {
				e.target.src = this.options.clearoutsrc;
			}
		});

		clearButton.on('click', function () {
			self.rating = -1;
			self.field.val('');
			self.stars.each(function (ii) {
					ii.removeClass('icon-star').addClass('icon-star-empty');
			});
			self.doAjax();
		});
		this.setStars();
	},

	doAjax : function () {
		var self = this;
		if (this.options.canRate === false) {
			return;
		}
		if (this.options.editable === false) {
			this.spinner.inject(this.ratingMessage);
			var data = {
				'option': 'com_fabrik',
				'format': 'raw',
				'task': 'plugin.pluginAjax',
				'plugin': 'rating',
				'method': 'ajax_rate',
				'g': 'element',
				'element_id': this.options.elid,
				'formid': this.options.formid,
				'row_id': this.options.row_id,
				'elementname': this.options.elid,
				'userid': this.options.userid,
				'rating': this.rating,
				'listid': this.options.listid
			};

			$.ajax({
				url: '',
				'data': data
			}).done(function () {
				self.spinner.dispose();
			});
		}
	},

	_getRating : function (i) {
		var r = i.get('data-rating');
		return r.toInt();
	},

	setStars : function () {
		var self = this;
		this.stars.each(function (ii) {
			var starScore = self._getRating(ii);
			if (starScore <= this.rating) {
				ii.removeClass('icon-star-empty').addClass('icon-star');
			} else {
				ii.removeClass('icon-star').addClass('icon-star-empty');
			}
		});
		var clearButton = this.getClearButton();
		clearButton.prop('sr', this.rating !== -1 ? this.options.clearoutsrc : this.options.clearinsrc);
	},

	getClearButton: function () {
		return this.element.getElement('i[data-rating=-1]');
	},

	update : function (val) {
		this.rating = val.toInt().round();
		this.field.value = this.rating;
		this.element.getElement('.ratingScore').text(val);
		this.setStars();
	}
});