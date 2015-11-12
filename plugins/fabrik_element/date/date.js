/**
 * Date Element
 *
 * @copyright: Copyright (C) 2005-2015, fabrikar.com - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

var FbDateTime = my.Class(FbElement, {

    /**
     * master date/time stored in this.cal (the js widget)
     * upon save we get a db formatted version of this date and put it into the date field
     * this dramitcally simplifies storing dates (no longer have to take account of formatting rules and/or
     * translations on the server side, as the widget has already handled it for us
     */
    options: {
        'dateTimeFormat': '',
        'watchElement'  : '',
        'calendarSetup' : {
            'eventName'    : 'click',
            'ifFormat'     : '%Y/%m/%d',
            'daFormat'     : '%Y/%m/%d',
            'singleClick'  : true,
            'align'        : 'Tl',
            'range'        : [1900, 2999],
            'showsTime'    : false,
            'timeFormat'   : '24',
            'electric'     : true,
            'step'         : 2,
            'cache'        : false,
            'showOthers'   : false,
            'advanced'     : false,
            'allowedDates' : [],
            'dateAllowFunc': ''
        }
    },

    constructor: function (element, options) {
        if (!FbDateTime.Super.call(this, element, options)) {
            return false;
        }
        this.hour = '0';
        this.plugin = 'fabrikdate';
        this.minute = '00';
        this.startElement = element;
        this.setUpDone = false;
        this.convertAllowedDates();
        this.setUp();
    },

    /**
     * Convert allowed date strings into Date objects
     */
    convertAllowedDates: function () {
        for (var i = 0; i < this.options.allowedDates.length; i++) {
            this.options.allowedDates[i] = new Date(this.options.allowedDates[i]);
        }
    },

    setUp: function () {
        var self = this;
        // Was also test on && !options.hidden but that stopped hidden elements from being saved correctly
        // @see http://fabrikar.com/forums/showthread.php?t=27992
        if (this.options.editable) {
            this.watchButtons();
            if (this.options.typing === false) {
                this.disableTyping();
            } else {
                this.getDateField().on('blur', function () {
                    var date_str = this.getDateField().val();
                    if (date_str !== '') {
                        var d;
                        //this is the calendar native parseDate call, but it doesnt take into account seconds
                        // $$$ hugh - yup, but if we don't use parseDate() with the iFormat, a simple Date.parse()
                        // hoses up anything but standard 'db' format.  So we HAVE to use parseDate() here.
                        if (self.options.advanced) {
                            d = Date.parseExact(date_str, Date.normalizeFormat(self.options.calendarSetup.ifFormat));
                        }
                        else {
                            d = Date.parseDate(date_str, self.options.calendarSetup.ifFormat);
                        }
                        self.setTimeFromField(d);
                        self.update(d);
                    }
                    else {
                        self.options.value = '';
                    }
                });
            }
            this.makeCalendar();

            // Chrome wierdness where we need to delay the hiding if the date picker is hidden
            var h = function () {
                this.cal.hide();
            };
            h.delay(100, this);
            this.getCalendarImg().on('click', function (e) {
                e.stopPropagation();
                if (!self.cal.params.position) {
                    self.cal.showAtElement(self.cal.params.button || self.cal.params.displayArea || self.cal.params.inputField, self.cal.params.align);
                } else {
                    self.cal.showAt(self.cal.params.position[0], params.position[1]);
                }

                // Needed to re-run the dateStatusFunc() to enable/disable dates
                self.cal._init(self.cal.firstDayOfWeek, self.cal.date);
                self.cal.show();
            });
            Fabrik.addEvent('fabrik.form.submit.failed', function () {
                // Fired when form failed after AJAX submit
                self.afterAjaxValidation();
            });
        }

    },

    /**
     * Once the element is attached to the form, observe the ajax trigger element
     */
    attachedToForm: function () {
        FbDateTime.Super.prototype.attachedToForm(this);
        this.watchAjaxTrigger();
    },

    /**
     * Observe the ajax trigger element, used for updatiing allowed dates
     */
    watchAjaxTrigger: function () {
        if (this.options.watchElement === '') {
            return;
        }
        var el = this.form.elements[this.options.watchElement],
            self = this;
        if (el) {
            el.on('change', function () {
                var data = {
                    'option'    : 'com_fabrik',
                    'format'    : 'raw',
                    'task'      : 'plugin.pluginAjax',
                    'plugin'    : 'date',
                    'method'    : 'ajax_getAllowedDates',
                    'element_id': self.options.id,
                    'v'         : el.get('value'),
                    'formid'    : self.form.id
                };
                new $.getJSON({
                    url   : '',
                    method: 'post',
                    'data': data
                }).success(function (json) {
                        self.options.allowedDates = json;
                        self.convertAllowedDates();
                    });
            });
        }
    },

    /**
     * Image to open calendar can be <img> (J2.5) or <i> (J3)
     *
     * @return {jQuery} dom node
     */
    getCalendarImg: function () {
        return this.element.find('.calendarbutton');
    },

    /**
     * Run when calendar poped up - goes over each date and should return true if you dont want the date to be
     * selectable
     */
    dateSelect: function (date) {
        // Check PHP events.
        var allowed = this.options.allowedDates, i,
            fn = this.options.calendarSetup.dateAllowFunc,
            matched = false;
        if (allowed.length > 0) {
            for (i = 0; i < allowed.length; i++) {
                if (allowed[i].format('%Y%m%d') === date.format('%Y%m%d')) {
                    matched = true;
                }
            }
            if (!matched) {
                return true;
            }
        }

        if (fn !== '') {
            eval(fn);
        }
    },

    /**
     * Run when a button is pressed on the calendar - may not be a date though (could be 'next month' button)
     */
    calSelect: function (calendar, date) {

        // Test the date is selectable...
        if (calendar.dateClicked && !this.dateSelect(calendar.date)) {
            var d = this.setTimeFromField(calendar.date);
            this.update(d.format('db'));
            this.getDateField().trigger('change');
            if (this.timeButton) {
                this.getTimeField().trigger('change');
            }
            this.cal.callCloseHandler();
            $(window).trigger('fabrik.date.select', this);
            Fabrik.trigger('fabrik.date.select', this);
        }
    },

    calClose: function () {
        this.cal.hide();
        $(window).trigger('fabrik.date.close', this);
        if (this.options.validations) {
            //if we have a validation on the element run it when the calendar closes itself
            //this ensures that alert messages are removed if the new data meets validation criteria
            this.form.doElementValidation(this.options.element);
        }
    },

    /**
     * Called from FbFormSubmit
     *
     * @param {function} cb  Callback function to run when the element is
     * in an acceptable state for the form processing to continue
     *
     * @return  void
     */
    onsubmit: function (cb) {
        //convert the date back into mysql format before submitting - saves all sorts of shenanigans
        //processing dates on the server.
        var v = this.getValue();
        if (v !== '') {
            // $$$ hugh - pretty sure we don't need to call update(), as getValue() is already returning
            // in MySQL format.  If we call update(), it fires a 'change' event, which puts us in an
            // infinite loop in some situations, like on a calc element update.
            // So just setting the date field to v should be enough.
            //this.update(v);
            if (this.options.editable) {
                this.getDateField().val(v);
            }
        }
        FbDateTime.Super.prototype.onsubmit(this, cb);
    },

    /**
     * As ajax validations call onsubmit to get the correct date, we need to
     * reset the date back to the display date when the validation is complete
     */
    afterAjaxValidation: function () {
        // Don't fire change events though - as we're simply resetting the date back to the correct format
        this.update(this.getValue(), []);
    },

    makeCalendar: function () {
        var i,
            params = this.options.calendarSetup,
            tmp = ['displayArea', 'button'];

        if (this.cal) {
            this.cal.show();
            return;
        }

        this.addEventToCalOpts();

        for (i = 0; i < tmp.length; i++) {
            if (typeof params[tmp[i]] === 'string') {
                params[tmp[i]] = document.getElementById(params[tmp[i]]);
            }
        }

        params.inputField = this.getDateField();
        var dateEl = params.inputField || params.displayArea;
        var dateFmt = params.inputField ? params.ifFormat : params.daFormat;
        this.cal = null;
        if (dateEl.length > 0) {
            if (this.options.advanced) {

                // If its blank dont try to format
                if (dateEl.val() === '') {
                    params.date = '';
                } else {
                    params.date = Date.parseExact(dateEl.val() || dateEl.html(), Date.normalizeFormat(dateFmt));

                    // If using format %b-%Y in Spanish (may be other langs as well)
                    // See http://fabrikar.com/forums/index.php?threads/problem-with-dates-on-a-form.39088/#post-196600
                    if (params.date === null) {
                        params.date = this.options;
                    }
                }
            } else {
                params.date = Date.parseDate(dateEl.val() || dateEl.html(), dateFmt);
            }
        }

        this.cal = new Calendar(params.firstDay,
            params.date,
            params.onSelect,
            params.onClose);

        this.cal.setDateStatusHandler(params.dateStatusFunc);
        this.cal.setDateToolTipHandler(params.dateTooltipFunc);
        this.cal.showsTime = params.showsTime;
        this.cal.time24 = (params.timeFormat.toString() === '24');
        this.cal.weekNumbers = params.weekNumbers;

        if (params.multiple) {
            cal.multiple = {};
            for (i = params.multiple.length; --i >= 0;) {
                var d = params.multiple[i];
                var ds = d.print('%Y%m%d');
                this.cal.multiple[ds] = d;
            }
        }
        this.cal.showsOtherMonths = params.showOthers;
        this.cal.yearStep = params.step;
        if (params.range !== undefined) {
            this.cal.setRange(params.range[0], params.range[1]);
        }
        this.cal.params = params;

        this.cal.getDateText = params.dateText;
        this.cal.setDateFormat(dateFmt);
        this.cal.create();
        this.cal.refresh();
        this.cal.hide();
    },

    disableTyping: function () {
        var self = this;
        if (this.element.length === 0) {
            fconsole(this.element + ': not date element container - is this a custom template with a missing $element->containerClass div/li surrounding the element?');
            return;
        }
        // yes we really can set the none existant 'readonly' property of the
        // subelement container
        // and get it when checking the validations - cool or what?
        this.element.prop('readonly', 'readonly');
        this.element.find('.fabrikinput').each(function (f) {
            f.on('focus', function (e) {
                self._disabledShowCalTime(f, e);
            });
            f.on('click', function (e) {
                self._disabledShowCalTime(f, e);
            });
        });
    },

    /**
     * Show either the calender or time picker, when input field activated
     *
     * @param   DOM Node  f  Field
     * @param   Event     e  focus/click event
     */
    _disabledShowCalTime: function (f, e) {
        if (typeof(e) !== 'object') {
            return;
        }
        if ($(e.target).hasClass('timeField')) {
            this.getContainer().find('.timeButton').trigger('click');
        } else {
            this.options.calendarSetup.inputField = e.target.id;
            this.options.calendarSetup.button = this.element.prop('id') + '_img';
            //this.addEventToCalOpts();
            this.cal.showAtElement(f, this.cal.params.align);
            if (this.cal.wrapper !== undefined) {
                this.cal.wrapper.parent().position({'relativeTo': this.cal.params.inputField, 'position': 'topLeft'});
            }
        }
    },

    /**
     * Returns the date and time in mySQL formatted string
     */
    getValue: function () {
        var v;
        if (!this.options.editable) {
            return this.options.value;
        }
        this.find();
        if (this.cal) {
            var dateFieldValue = this.getDateField().val();
            if (dateFieldValue === '') {
                return '';
            }
            // User can press back button in which case date may already be in correct format and calendar date incorrect
            var re = new RegExp('\\d{4}-\\d{2}-\\d{2} \\d{2}:\\d{2}:\\d{2}');
            if (dateFieldValue.match(re) !== null) {
                return dateFieldValue;
            }

            v = this.cal.date;
        } else {
            if (this.dateIsEmpty(this.options.value)) {
                return '';
            }
            v = new Date.parse(this.options.value);
        }
        v = this.setTimeFromField(v);
        return v.format('db');
    },

    /**
     * Is a string an 'empty' date
     * @param v
     * @returns {boolean}
     */
    dateIsEmpty: function (v) {
        return v === '' || v === null || v === '0000-00-00 00:00:00';
    },

    /**
     * Does the date time format contains a seconds format string
     * @returns {boolean}
     */
    hasSeconds: function () {
        if (this.options.showtime === true && this.timeElement) {
            if (this.options.dateTimeFormat.contains('%S')) {
                return true;
            }
            if (this.options.dateTimeFormat.contains('%T')) {
                return true;
            }
            if (this.options.dateTimeFormat.contains('s')) {
                return true;
            }
        }
        return false;
    },

    /**
     * Set time from field
     * @param  date
     */
    setTimeFromField: function (d) {
        if ($.type(d) !== 'date') {
            return;
        }

        if (this.options.showtime === true && this.timeElement) {
            var time = this.timeElement.get('value').toUpperCase();
            var afternoon = time.contains('PM') ? true : false;
            time = time.replace('PM', '').replace('AM', '');

            var t = time.split(':');
            var h = t[0] ? t[0].toInt() : 0;
            if (afternoon) {
                h += 12;
            }
            var m = t[1] ? t[1].toInt() : 0;

            d.setHours(h);
            d.setMinutes(m);

            if (t[2] && this.hasSeconds()) {
                var s = t[2] ? t[2].toInt() : 0;
                d.setSeconds(s);
            } else {
                d.setSeconds(0);
            }
        }
        return d;
    },

    watchButtons: function () {
        var self = this;
        if (this.options.showtime && this.options.editable) {
            this.getTimeField();
            this.getTimeButton();
            if (this.timeButton) {
                this.timeButton.off('click');
                this.timeButton.on('click', function (e) {
                    e.stopPropagation();
                    self.showTime();
                });
                if (!this.setUpDone) {
                    if (this.timeElement) {
                        this.dropdown = this.makeDropDown();
                        this.setActive();
                        this.dropdown.find('a.close-time').on('click', function (e) {
                            e.stopPropagation();
                            self.hideTime();
                        });
                        this.setUpDone = true;
                    }
                }
            }
        }
    },

    addNewEventAux: function (action, js) {
        if (action === 'change') {
            Fabrik.addEvent('fabrik.date.select', function (w) {
                if (w.baseElementId === this.baseElementId) {
                    var e = 'fabrik.date.select';
                    typeof(js) === 'function' ? js.delay(0, this, this) : eval(js);
                }
            }.bind(this));
        }
        else {
            this.element.find('input').each(function () {
                var input = $(this);
                input.on(action, function (e) {
                    if (typeof(e) === 'object') {
                        e.stopPropagation();
                    }
                    typeof(js) === 'function' ? js.delay(0, this, this) : eval(js);
                });
            }.bind(this));
        }
    },

    /**
     * takes a date object or string
     *
     * @param   mixed  val     Date, string or date object
     * @param   array  events  Events to fire defaults to ['change']
     */
    update: function (val, events) {
        events = events ? events : ['change'];
        this.find();

        if (val === 'invalid date') {
            fconsole(this.element.id + ': date not updated as not valid');

            return;
        }

        var date;

        if (typeOf(val) === 'string') {
            // $$$ hugh - if val is empty string, like from a clearForm(), the Date.parse() is
            // going to return null, swhich will then blow up in a few lines.
            date = Date.parse(val);

            if (date === null) {
                // Yes, but we still need to clear the fields! (e.g. from reset())
                this._getSubElements().each(function (subEl) {
                    subEl.val('');
                });

                if (this.cal) {
                    /*
                     * Can't set this.cal.date to a blank string as it expects a date object
                     * So, defaulting to todays date, not sure we can do anything else?
                     */
                    this.cal.date = new Date();
                }

                if (!this.options.editable) {
                    this.element.html(val);
                }

                return;
            }
        } else {
            date = val;
        }

        var f = this.options.calendarSetup.ifFormat;

        if (this.options.dateTimeFormat !== '' && this.options.showtime) {
            f += ' ' + this.options.dateTimeFormat;
        }

        if (events.length > 0) {
            this.fireEvents(events);
        }

        if (typeOf(val) === 'null' || val === false) {
            return;
        }

        if (!this.options.editable) {
            this.element.html(date.format(f));

            return;
        }

        if (this.options.hidden) {
            //if hidden but form set to show time format dont split up the time as we don't
            // have a time field to put it into
            date = date.format(f);
            this.getDateField().val(date);

            return;
        } else {
            // have to reset the time element as update is called (via reset) in
            // duplicate group code
            // before cloned() method called
            this.getTimeField();
            this.hour = date.get('hours');
            this.minute = date.get('minutes');
            this.second = date.get('seconds');
            this.stateTime();
        }
        this.cal.date = date;
        this.getDateField().val(date.format(this.options.calendarSetup.ifFormat));
    },

    /**
     * get the date field input
     */
    getDateField: function () {
        return this.element.find('.fabrikinput');
    },

    /**
     * get time time field input
     */
    getTimeField: function () {
        this.timeElement = this.getContainer().find('.timeField');
        return this.timeElement;
    },

    /**
     * Get time time button img
     *
     * @return  {jQuery} Time button
     */
    getTimeButton: function () {
        this.timeButton = this.getContainer().find('.timeButton');
        return this.timeButton;
    },

    getAbsolutePos: function (el) {
        var r = {
            x: el.offsetLeft,
            y: el.offsetTop
        };

        if (el.offsetParent) {
            var tmp = this.getAbsolutePos(el.offsetParent);
            r.x += tmp.x;
            r.y += tmp.y;
        }

        return r;
    },

    /**
     * Make the time picker
     */
    makeDropDown: function () {
        var h = null, self = this, i;
        var handle = $(document.createElement('div')).addClass('draggable modal-header').css({
            'height' : '20px',
            'curor'  : 'move',
            'padding': '2px;'
        }).attr({
            'id': this.startElement + '_handle'
        }).html('<i class="icon-clock"></i> ' + this.options.timelabel +
            '<a href="#" class="close-time pull-right" ><i class="icon-cancel"></i></a>');
        var d = $(document.createElement('div')).addClass('fbDateTime fabrikWindow').css({
            'z-index': 999999,
            display  : 'none',
            width    : '300px',
            height   : '180px'
        });

        d.appendChild(handle);
        var padder = $(document.createElement('div')).addClass('itemContentPadder');
        padder.adopt($(document.createElement('p')).text('Hours'));
        padder.adopt(this.hourButtons(0, 12));
        padder.adopt(this.hourButtons(12, 24));
        padder.adopt($(document.createElement('p')).text('Minutes'));
        var d2 = $(document.createElement('div')).addClass('btn-group').css({
            clear     : 'both',
            paddingTop: '5px'
        });

        var clickEvent = function (e) {
                self.minute = self.formatMinute($(e.target).html());
                self.stateTime();
                self.setActive();
            },
            overEvent = function (e) {
                if (self.minute !== this.formatMinute($(e.target).html())) {
                    $(e.target).addClass('btn-info');
                }
            },
            outEvent = function (e) {
                if (this.minute !== this.formatMinute($(e.target).html())) {
                    $(e.target).removeClass('btn-info');
                }
            };

        for (i = 0; i < 12; i++) {
            h = $(document.createElement('a')).addClass('btn fbdateTime-minute btn-mini').css({'width': '10px'});
            h.html(i * 5);
            d2.appendChild(h);
            h.on('click', clickEvent);
            h.on('mouseover', overEvent);
            h.on('mouseout', outEvent);
        }

        padder.appendChild(d2);
        d.appendChild(padder);

        $(document).on('click', function (e) {
            if (self.timeActive) {
                if (this !== self.timeButton && this !== self.timeElement) {
                    if (!this.within(self.dropdown)) {
                        self.hideTime();
                    }
                }
            }
        });

        d.inject(document.body);
        var mydrag = new Drag.Move(d);

        var closeTime = handle.find('a.close');
        closeTime.on('click', function (e) {
            e.stopPropagation();
            self.hideTime();
        });

        return d;
    },

    hourButtons: function (start, end) {
        var v = this.getValue(), h, i,
            hrGroup = $(document.createElement('div')).addClass('btn-group'),
            self = this,
            mouseOut = function () {
                return function () {
                    if (self.hour !== $(this).html()) {
                        $(this).removeClass('btn-info');
                    }
                };
            },
            mouseOver = function () {
                return function (e) {
                    if (self.hour !== $(this).html()) {
                        $(this).addClass('btn-info');
                    }
                };
            },
            click = function () {
                return function () {
                    self.hour = $(this).html();
                    self.stateTime();
                    self.setActive();
                    $(this).addClass('btn-successs').removeClass('badge-info');
                };
            };
        if (v === '') {
            this.hour = 0;
            this.minute = 0;
        } else {
            var date = Date.parse(v);
            this.hour = date.get('hours');
            this.minute = date.get('minutes');
        }

        for (i = start; i < end; i++) {
            h = $(document.createElement('a')).addClass('btn btn-mini fbdateTime-hour').css({'width': '10px'}).html(i);
            hrGroup.appendChild(h);
            h.on('click', click());
            h.on('mouseover', mouseOver());
            h.on('mouseout', mouseOut());
        }
        return hrGroup;
    },

    toggleTime: function () {
        if (this.dropdown.css('display') === 'none') {
            this.doShowTime();
        } else {
            this.hideTime();
        }
    },

    doShowTime: function () {
        this.dropdown.show();
        this.timeActive = true;
        Fabrik.trigger('fabrik.date.showtime', this);
    },

    hideTime: function () {
        this.timeActive = false;
        this.dropdown.hide();
        if (this.options.validations !== false) {
            this.form.doElementValidation(this.element.id);
        }
        Fabrik.trigger('fabrik.date.hidetime', this);
        Fabrik.trigger('fabrik.date.select', this);
        $(window).trigger('fabrik.date.select', this);
    },

    formatMinute: function (m) {
        m = m.replace(':', '');
        m.pad('2', '0', 'left');
        return m;
    },

    stateTime: function () {
        if (this.timeElement) {
            var newv = this.hour.toString().pad('2', '0', 'left') + ':' + this.minute.toString().pad('2', '0', 'left');
            if (this.second) {
                newv += ':' + this.second.toString().pad('2', '0', 'left');
            }
            var changed = this.timeElement.val() !== newv;
            this.timeElement.val(newv);
            if (changed) {
                this.fireEvents(['change']);
            }
        }
    },

    showTime: function () {
        this.dropdown.position({relativeTo: this.timeElement, 'position': 'topRight'});
        this.toggleTime();
        this.setActive();
    },

    setActive: function () {
        var hours = this.dropdown.getElements('.fbdateTime-hour');
        hours.removeClass('btn-success').removeClass('btn-info');
        var mins = this.dropdown.getElements('.fbdateTime-minute');
        mins.removeClass('btn-success').removeClass('btn-info');
        mins[this.minute / 5].addClass('btn-success');
        var active = hours[this.hour.toInt()];
        active.addClass('btn-success');
    },

    addEventToCalOpts: function () {
        this.options.calendarSetup.onSelect = function (calendar, date) {
            this.calSelect(calendar, date);
        }.bind(this);

        this.options.calendarSetup.dateStatusFunc = function (date) {
            return this.dateSelect(date);
        }.bind(this);

        this.options.calendarSetup.onClose = function () {
            this.calClose();
        };

    },

    cloned: function (c) {
        this.setUpDone = false;
        this.hour = 0;
        delete this.cal;
        var button = this.element.find('img');
        if (button) {
            button.id = this.element.id + '_cal_img';
        }
        var datefield = this.element.find('input');
        datefield.id = this.element.id + '_cal';
        this.options.calendarSetup.inputField = datefield.id;
        this.options.calendarSetup.button = this.element.id + '_img';

        this.makeCalendar();
        this.cal.hide();
        this.setUp();
        FbDateTime.Super.prototype.cloned(this, c);
    }
});

/// you can add custom events with:
/*
 * Fabrik.addEvent('fabrik.date.select', function () {
 console.log('trigger custom date event');
 })
 */