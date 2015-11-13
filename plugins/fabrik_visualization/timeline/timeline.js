/**
 * Timeline Visualization
 *
 * @copyright: Copyright (C) 2005-2015, fabrikar.com - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

var FbVisTimeline = my.Class({

	options: {
		dateFormat: '%c',
		orientation: '0',
		urlfilters: []
	},

	constructor : function (json, options) {
		var self = this;
		this.json = eval(json);
		this.options = $.extend(this.options, options);

		this.resizeTimerID = null;
		this.tl = null;
		var dateFormat = this.options.dateFormat;

		Timeline.GregorianDateLabeller.prototype.labelPrecise = function (date)
		{
			// Crazy hackery to reset the label time to the correct one.
			// means the Z time format will not give you the correct tz
			var newdate = new Date(date.getTime() + date.getTimezoneOffset() * 60000);
			return newdate.format(dateFormat);
		};

		this.eventSource = new Timeline.DefaultEventSource();

		// TODO: theme the viz in admin
		var theme = Timeline.ClassicTheme.create();
		theme.event.bubble.width = 320;
		theme.event.bubble.height = 520;
		theme.event.track.height = 11.5;
		theme.event.track.gap = 0.1;
		theme.ether.backgroundColors = [ "#000000", "red" ];

		theme.ether.highlightColor = 'red';

		Timeline.setDefaultTheme(theme);

		var bandBase = {
				trackGap : 0.2,
				width : '70%',
				intervalUnit : Timeline.DateTime.DAY,
				intervalPixels : 50
			};

		var bandTracks = [];

		for (var b = 0; b < json.bands.length; b ++) {
			var bandClone = Object.clone(bandBase);
			bandClone.width = json.bands[b].width;
			bandClone.intervalUnit = json.bands[b].intervalUnit;
			bandClone.overview = json.bands[b].overview;
			bandClone.eventSource = this.eventSource;
			bandClone.theme = theme;
			bandTracks.push(Timeline.createBandInfo(bandClone));
		}

		// Sync the bands to scroll together
		for (b = 1; b < json.bands.length; b ++) {
			bandTracks[b].syncWith = 0;
			bandTracks[b].highlight = true;
		}

		SimileAjax.History.enabled = false;
		this.tl = Timeline.create($('#my-timeline'), bandTracks, this.options.orientation);

		this.start = 0;

		var data = {
			'option': 'com_fabrik',
			'format': 'raw',
			'task': 'ajax_getEvents',
			'view': 'visualization',
			'visualizationid': this.options.id,
			'currentList': this.options.currentList,
			setListRefFromRequest: 1,
			listref: this.options.listRef
		};
		// Add the filters the the data array.
		data = Object.merge(data, this.options.urlfilters);

		if (this.options.admin) {
			data.task = 'visualization.ajax_getEvents';
		} else {
			data.controller = 'visualization.timeline';
		}
		this.start = 0;
		this.counter = $(document.createElement('div')).addClass('timelineTotals').inject($('#my-timeline'), 'before');
		this.counter.text('loading');
		this.ajax = $.getJSON({
			url: 'index.php',
			data: data
		}).failure(function (xhr) {
			window.alert(xhr.status + ': ' + xhr.statusText);
		}).success(function (json) {
			self.start = self.start + self.options.step;
			if (self.start >= json.fabrik.total) {
				self.counter.text('loaded ' + json.fabrik.total);
			} else {
				self.counter.text('loading ' + this.start + ' / ' + json.fabrik.total);
			}

			self.eventSource.loadJSON(json.timeline.events, '');
			if (parseInt(json.fabrik.done, 10) === 0) {
				self.ajax.options.data.start = json.fabrik.next;
				self.ajax.options.data.currentList = json.fabrik.currentList;
				self.ajax.send();
			}
		});

		Fabrik.addEvent('fabrik.advancedSearch.submit', function (e) {
			console.log('cancel ajax');
			self.ajax.cancel();
		});

		this.ajax.send();

		$(window).on('resize', function () {
			if (self.resizeTimerID === null) {
				self.resizeTimerID = window.setTimeout(function () {
					self.resizeTimerID = null;
					self.tl.layout();
				}, 500);
			}
		});

		this.watchDatePicker();
	},

	watchDatePicker: function () {
		var dateEl = $('#timelineDatePicker'),
			self = this;
		if (dateEl.length === 0) {
			return;
		}
		var params = {'eventName': 'click',
				'ifFormat': this.options.dateFormat,
				'daFormat': this.options.dateFormat,
				'singleClick': true,
				'align': 'Br',
				'range': [1900, 2999],
				'showsTime': false,
				'timeFormat': '24',
				'electric': true,
				'step': 2,
				'cache': false,
				'showOthers': false,
				'advanced': false };
		var dateFmt = this.options.dateFormat;
		params.date = Date.parseDate(dateEl.value || dateEl.innerHTML, dateFmt);
		params.onClose = function (cal) {
			cal.hide();
		};
		params.onSelect = function () {
			if (self.cal.dateClicked || self.cal.hiliteToday) {
				self.cal.callCloseHandler();
				dateEl.value = self.cal.date.format(dateFmt);
				self.tl.getBand(0).setCenterVisibleDate(self.cal.date);
			}
		};

		params.inputField = dateEl;
		params.button = $('#timelineDatePicker_cal_img');
		params.align = "Tl";
		params.singleClick = true;

		this.cal = new Calendar(0,
				params.date,
				params.onSelect,
				params.onClose);

		this.cal.showsOtherMonths = params.showOthers;
		this.cal.yearStep = params.step;
		this.cal.setRange(params.range[0], params.range[1]);
		this.cal.params = params;

		this.cal.setDateFormat(dateFmt);
		this.cal.create();
		this.cal.refresh();
		this.cal.hide();

		params.button.on('click', function (e) {
			self.cal.showAtElement(params.button);
			self.cal.show();
		});
		dateEl.on('blur', function (e) {
			self.updateFromField();
		});

		dateEl.on('keyup', function (e) {
			if (e.key === 'enter') {
				self.updateFromField();
			}
		});

	},

	updateFromField: function () {
		var dateStr = $('#timelineDatePicker').val(),
		d = Date.parseDate(dateStr, this.options.dateFormat);
		this.cal.date = d;
		var newdate = new Date(this.cal.date.getTime() - (this.cal.date.getTimezoneOffset() * 60000));
		//this.tl.getBand(0).setCenterVisibleDate(newdate);
		this.tl.getBand(0).scrollToCenter(newdate);

	},

	/**
	 * Ajax advanced search filter called
	 * @TODO implement this
	 */
	submit: function () {

	}
});