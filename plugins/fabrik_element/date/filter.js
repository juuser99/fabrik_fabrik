/**
 * Date Element Filter
 *
 * @copyright: Copyright (C) 2005-2015, fabrikar.com - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

var DateFilter = my.Class({

	options: {
		'calendarSetup': {
			'eventName': 'click',
			'ifFormat': "%Y/%m/%d",
			'daFormat': "%Y/%m/%d",
			'singleClick': true,
			'align': "Br",
			'range': [1900, 2999],
			'showsTime': false,
			'timeFormat': '24',
			'electric': true,
			'step': 2,
			'cache': false,
			'showOthers': false
		}
	},

	initialize: function (opts) {
		this.options = $.append(this.options, opts);
		this.cals = {};
		for (var i = 0; i < this.options.ids.length; i ++) {
			this.makeCalendar(this.options.ids[i], this.options.buttons[i]);
		}
	},

	makeCalendar: function (id, button) {
		var self = this;
		if (this.cals[id]) {
			this.cals[id].show();
			return;
		}
		button = $('#' + button);
		if (button.length === 0) {
			return;
		}
		this.addEventToCalOpts();
		var params = Object.clone(this.options.calendarSetup);
		var tmp = ['displayArea', 'button'];

		params.inputField = $('#' + id);
		var dateEl = params.inputField || params.displayArea;
		var dateFmt = params.inputField ? params.ifFormat : params.daFormat;
		this.cals[id] = null;//Fabrik.calendar;
		if (dateEl) {
			params.date = Date.parseDate(dateEl.value || dateEl.innerHTML, dateFmt);
		}

		this.cals[id] = new Calendar(params.firstDay,
			params.date,
			params.onSelect,
			params.onClose);

		this.cals[id].setDateStatusHandler(params.dateStatusFunc);
		this.cals[id].setDateToolTipHandler(params.dateTooltipFunc);
		this.cals[id].showsTime = params.showsTime;
		this.cals[id].time24 = (params.timeFormat.toString() === "24");
		this.cals[id].weekNumbers = params.weekNumbers;

		this.cals[id].showsOtherMonths = params.showOthers;
		this.cals[id].yearStep = params.step;
		this.cals[id].setRange(params.range[0], params.range[1]);
		this.cals[id].params = params;
		this.cals[id].params.button = button;
		this.cals[id].getDateText = params.dateText;
		this.cals[id].setDateFormat(dateFmt);
		this.cals[id].create();
		this.cals[id].refresh();
		this.cals[id].hide();
		button.on('click', function (e) {
			e.stop();
			if (!self.cals[id].params.position) {
				self.cals[id].showAtElement(self.cals[id].params.button || self.cals[id].params.displayArea ||
					self.cals[id].params.inputField, self.cals[id].params.align);
			} else {
				self.cal.showAt(self.cals[id].params.position[0], params[id].position[1]);
			}
			self.cals[id].show();
		});

		// $$$ hugh - need to update cal's date when date is entered by hand in input field
		this.cals[id].params.inputField.on('blur', function (e) {
			var date_str = self.cals[id].params.inputField.value;
			if (date_str !== '') {
				var d = Date.parseDate(date_str, self.cals[id].params.ifFormat);
				self.cals[id].date = d;
			}
		});

		//chrome wierdness where we need to delay the hiding if the date picker is hidden
		var h = function () {
			this.cals[id].hide();
		};
		h.delay(100, this);
		return this.cals[id];
	},

	/**
	 * run when calendar poped up - goes over each date and should return true if you dont want the date to be
	 * selectable
	 */
	dateSelect: function (date)
	{
		return false;
	},

	calSelect: function (calendar, date) {
		this.update(calendar, calendar.date.format('db'));
		if (calendar.dateClicked) {
			calendar.callCloseHandler();
		}
	},

	calClose: function (calendar) {
		calendar.hide();
	},

	update: function (calendar, date) {
		if (date) {
			if (typeOf(date) === 'string') {
				date = Date.parse(date);
			}
			calendar.params.inputField.value = date.format(this.options.calendarSetup.ifFormat);
		}
	},

	addEventToCalOpts: function () {
		this.options.calendarSetup.onSelect = function (calendar, date) {
			this.calSelect(calendar, date);
		}.bind(this);

		this.options.calendarSetup.dateStatusFunc = function (date) {
			return this.dateSelect(date);
		}.bind(this);

		this.options.calendarSetup.onClose = function (calendar) {
			this.calClose(calendar);
		}.bind(this);
	},

	onSubmit: function () {
		jQuery.each(this.cals, function (key, c) {
			if (c.params.inputField.value !== '') {
				c.params.inputField.value = c.date.format('db');
			}
		}.bind(this));
	},

	onUpdateData: function () {
		jQuery.each(this.cals, function (key, c) {
			if (c.params.inputField.value !== '') {
				this.update(c, c.date);
			}
		}.bind(this));
	}
});