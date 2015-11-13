/**
 * Calendar Visualization
 *
 * @copyright: Copyright (C) 2005-2013, fabrikar.com - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

var fabrikCalendar = my.Class({
    options: {
        days           : ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'],
        shortDays      : ['Sun', 'Mon', 'Tues', 'Wed', 'Thur', 'Fri', 'Sat'],
        months         : ['January', 'Feburary', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'],
        shortMonths    : ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sept', 'Oct', 'Nov', 'Dec'],
        viewType       : 'month',
        first_week_day : 0,
        calendarId     : 1,
        tmpl           : 'default',
        'Itemid'       : 0,
        colors         : {
            'bg'          : '#F7F7F7',
            'highlight'   : '#FFFFDF',
            'headingBg'   : '#C3D9FF',
            'today'       : '#dddddd',
            'headingColor': '#135CAE',
            'entryColor'  : '#eeffff'
        },
        eventLists     : [],
        'listid'       : 0,
        'popwiny'      : 0,
        timeFormat     : '%X',
        urlfilters     : [],
        url            : {
            'add': 'index.php?option=com_fabrik&controller=visualization.calendar&view=visualization&task=getEvents&format=raw',
            'del': 'index.php?option=com_fabrik&controller=visualization.calendar&view=visualization&task=deleteEvent&format=raw'
        },
        monthday       : {'width': 90, 'height': 120},
        restFilterStart: 'na',
        j3             : false,
        showFullDetails: false,
        readonly       : false,
        readonlyMonth  : false,
        dateLimits     : {min: '', max: ''}
    },

    constructor: function (el) {
        var self = this;
        this.firstRun = true;
        this.el = $('#' + el);
        this.SECOND = 1000; // the number of milliseconds in a second
        this.MINUTE = this.SECOND * 60; // the number of milliseconds in a minute
        this.HOUR = this.MINUTE * 60; // the number of milliseconds in an hour
        this.DAY = this.HOUR * 24; // the number of milliseconds in a day
        this.WEEK = this.DAY * 7; // the number of milliseconds in a week
        this.date = new Date();//date used to display currently viewed page of calendar
        //date used to highlight appropriate parts of calendar (doesn't change when you navigate around the calendar)
        this.selectedDate = new Date();
        this.entries = {};
        this.droppables = {'month': [], 'week': [], 'day': []};
        this.fx = {};
        if (this.el.find('.calendar-message').length !== 0) {
            this.fx.showMsg = new Fx.Morph(this.el.find('.calendar-message'), {'duration': 700});
            this.fx.showMsg.set({'opacity': 0});
        }
        this.colwidth = {};
        this.windowopts = {
            'id'           : 'addeventwin',
            title          : 'add/edit event',
            loadMethod     : 'xhr',
            minimizable    : false,
            evalScripts    : true,
            width          : 380,
            height         : 320,
            onContentLoaded: function (win) {
                win.fitToContent();
            }.bind(this)
        };
        Fabrik.addEvent('fabrik.form.submitted', function (form, json) {
            self.updateEvent();
            Fabrik.Windows.addeventwin.close();
        });
    },

    /**
     * Delete an event
     */
    deleteEvent: function (data) {
        data = $.extend({
            'visualizationid': this.options.calendarId
        }, data);
        var self = this;
        $.ajax({
            url   : this.options.url.del,
            'data': data,
        }).complete(function (r) {
            r = r.stripScripts(true);
            var json = JSON.decode(r);
            self.entries = {};
            self.addEntries(json);
        });
    },
    /**
     * Update an event
     */
    updateEvent: function () {
        var self = this;
        $.ajax({
            url          : this.options.url.add,
            'data'       : d,
            'evalScripts': true,
        }).complete(function (r) {
            if (r) {
                var text = r.stripScripts(true);
                var json = JSON.decode(text);
                self.addEntries(json);
                self.showView();
            }
        });
    },

    removeFormEvents: function (formId) {
        jQuery.each(this.entries, function (k, entry) {
            if (typeof(entry) !== 'undefined' && entry.formid === formId) {
                delete this.entries[k];
            }
        }.bind(this));
        /*for(var j=this.entries.length-1;j>=0;j--) {
         // $$$ hugh - UNTESTED defensive coding as per:
         // http://fabrikar.com/forums/showthread.php?t=8263
         // ... although should probably work out why this.entries[j] is sometimes undefined in the first place
         if (typeof this.entries[j] !== 'undefined' && this.entries[j].formid === formId) {
         this.entries.dispose(this.entries[j]);
         }
         }*/
    },

    /**
     * Make the event div
     *
     * @param  object    entry   Event entry
     * @param  object    opts    Position opts
     * @param  date      aDate   Current date
     * @param  DOM node  target  Parent dom node
     */
    _makeEventRelDiv: function (entry, opts, aDate, target) {
        var x, eventCont, replace, dataContent, buttons, self = this;
        var label = entry.label;
        opts.left === opts.left ? opts.left : 0;
        opts['margin-left'] === opts['margin-left'] ? opts['margin-left'] : 0;

        var bg = (entry.colour !== '') ? entry.colour : this.options.colors.entryColor;

        if (opts.startMin === 0) {
            opts.startMin = opts.startMin + '0';
        }
        if (opts.endMin === 0) {
            opts.endMin = opts.endMin + '0';
        }

        var v = opts.view ? opts.view : 'dayView';

        var style = {
            'background-color': this._getColor(bg, aDate),
            'width'           : opts.width,
            'cursor'          : 'pointer',
            'margin-left'     : opts['margin-left'],
            'top'             : parseInt(opts.top, 10) + 'px',
            'position'        : 'absolute',
            'border'          : '1px solid #666666',
            'border-right'    : '0',
            'border-left'     : '0',
            'overflow'        : 'auto',
            'opacity'         : 0.6,
            'padding'         : '0 4px'
        };
        if (opts.height) {
            style.height = parseInt(opts.heigh, 10) + 'px';
        }
        if (opts.left) {
            style.left = parseInt(opts.left, 10) + 1 + 'px';
        }
        style['max-width'] = opts['max-width'] ? opts['max-width'] - 10 + 'px' : '';
        var id = 'fabrikEvent_' + entry._listid + '_' + entry.id;

        // Not sure this is right - at least its not for month views
        if (target && opts.view !== 'monthView') {
            id += target.className.replace(' ', '');
        }
        if (opts.view === 'monthView') {
            style.width -= 1;
        }
        buttons = '';

        if (entry._canDelete) {
            buttons += this.options.buttons.del;
        }

        if (entry._canEdit && !this.options.readonly) {
            buttons += this.options.buttons.edit;
        }

        if (entry._canView) {
            buttons += this.options.buttons.view;
        }

        replace = {
            start: Date.parse(entry.startdate_locale).format(this.options.timeFormat),
            end  : Date.parse(entry.enddate_locale).format(this.options.timeFormat)
        };
        dataContent = Joomla.JText._('PLG_VISUALIZATION_CALENDAR_EVENT_START_END').substitute(replace);

        if (buttons !== '') {
            dataContent += '<hr /><div class="btn-group" style="text-align:center;display:block">' + buttons + '</div>';
        }

        eventCont = $(document.createElement('a')).addClass('fabrikEvent label ' + entry.status)
            .attr({
                'id'                 : id,
                'styles'             : style,
                'rel'                : 'popover',
                'data-original-title': label + '<button class="close" data-popover="' + id + '">&times;</button>',
                'data-content'       : dataContent,
                'data-placement'     : 'top',
                'data-html'          : 'true',
                'data-trigger'       : 'click'
            });

        if (this.options.showFullDetails) {
            eventCont.set('data-task', 'viewCalEvent');
        } else {
            if (typeof(jQuery) !== 'undefined') {
                jQuery(eventCont).popover();
                eventCont.on('click', function (e) {
                    self.popOver = eventCont;
                });

                // Ensure new form doesn't open when we double click on the event.
                eventCont.on('dblclick', function (e) {
                    e.stopPropagation();
                });
            }
        }
        if (entry.custom) {
            label = label === '' ? 'click' : label;
            x = $(document.createElement('a')).attr({
                'href': entry.link
            }).on('click', function (e) {
                Fabrik.trigger('fabrik.viz.calendar.event', [e]);
            }).html(label);
        } else {
            x = $(document.createElement('span')).html(label);
        }
        eventCont.append(x);
        return eventCont;
    },

    doPopupEvent: function (e, entry, label) {
        var loc;
        var oldactive = this.activeHoverEvent;
        if (!this.popWin) {
            return;
        }
        this.activeHoverEvent = e.target.hasClass('fabrikEvent') ? e.target : e.target.closest('.fabrikEvent');
        if (!entry._canDelete) {
            this.popWin.find('.popupDelete').hide();
        } else {
            this.popWin.find('.popupDelete').show();
        }
        if (!entry._canEdit) {
            this.popWin.find('.popupEdit').hide();
            this.popWin.find('.popupView').show();
        } else {
            this.popWin.find('.popupEdit').show();
            this.popWin.find('.popupView').hide();
        }

        if (this.activeHoverEvent) {
            loc = this.activeHoverEvent.getCoordinates();
        } else {
            loc = {top: 0, left: 0};
        }
        // Barbara : added label in pop-up
        var popLabelElt = this.popup.find('div[class=popLabel]');
        popLabelElt.empty();

        popLabelElt.text(label);
        this.activeDay = e.target.parent();
        var newtop = loc.top - this.popWin.getSize().y;
        var fxopts = {
            'opacity': [0, 1],
            'top'    : [loc.top + 50, loc.top - 10]
        };
        this.inFadeOut = false;
        this.popWin.csss({'left': loc.left + 20, 'top': loc.top});
        this.fx.showEventActions.cancel().set({'opacity': 0}).start.delay(500, this.fx.showEventActions, fxopts);
    },

    _getFirstDayInMonthCalendar: function (firstDate) {
        var origDate = new Date(),
            backwardsDaysDelta;
        origDate.setTime(firstDate.valueOf());
        if (firstDate.getDay() !== this.options.first_week_day) {
            backwardsDaysDelta = firstDate.getDay() - this.options.first_week_day;
            if (backwardsDaysDelta < 0) {
                backwardsDaysDelta = 7 + backwardsDaysDelta;
            }
            //first day of week
            firstDate.setTime(firstDate.valueOf() - (backwardsDaysDelta * 24 * 60 * 60 * 1000));
        }
        if (origDate.getMonth() === firstDate.getMonth()) {
            var weekLength = 7 * 24 * 60 * 60 * 1000;
            //go back a day at a time till we get to the first week of this month view
            while (firstDate.getDate() > 1) {
                firstDate.setTime(firstDate.valueOf() - this.DAY);
            }
        }
        return firstDate;
    },

    /**
     * Between (end date present) or same (no end date)
     * @param {Date} date
     * @param {object} entry
     * @returns {boolean}
     */
    dateBetweenOrSame: function (date, entry) {
        var between = date.isDateBetween(entry.startdate, entry.enddate);
        return (entry.enddate !== '' && between) || (entry.enddate === '' && entry.startdate.isSameDay(date));
    },

    showMonth: function () {
        // Set the date to the first day of the month
        var firstDate = new Date(), self = this,
            height, width;
        firstDate.setTime(this.date.valueOf());
        firstDate.setDate(1);
        firstDate = this._getFirstDayInMonthCalendar(firstDate);
        var trs = this.el.find('.monthView tr');
        var c = 0; // counter
        for (var i = 1; i < trs.length; i++) {
            var tds = trs[i].find('td');
            var colcounter = 0;
            tds.each(function (td) {
                td.setProperties({'class': ''});
                td.addClass(firstDate.getTime());

                // No need to unset as this is done in setProperties above
                if (firstDate.getMonth() !== this.date.getMonth()) {
                    td.addClass('otherMonth');
                }

                if (this.selectedDate.isSameDay(firstDate)) {
                    td.addClass('selectedDay');
                }
                td.empty();
                // Barbara : added greyscaled week-ends color option
                td.append(
                    $(document.createElement('div')).addClass('date').css({
                            'background-color': this._getColor('#E8EEF7', firstDate)
                        }
                    ).appendText(firstDate.getDate())
                );
                var gridSize = 0;
                this.entries.each(function (entry) {
                    if (this.dateBetweenOrSame(firstDate, entry)) {
                        gridSize++;
                    }
                }.bind(this));

                var j = 0;
                this.entries.each(function (entry) {
                    if (this.dateBetweenOrSame(firstDate, entry)) {
                        var existingEvents = td.find('.fabrikEvent').length;

                        var dayHeadingSize = td.find('.date').getSize().y;
                        height = Math.floor((td.getSize().y - gridSize - dayHeadingSize) / (gridSize));
                        var top = (td.getSize().y * (i - 1)) + this.el.find('.monthView .dayHeading').getSize().y + dayHeadingSize;
                        this.colwidth['.monthView'] = this.colwidth['.monthView'] ? this.colwidth['.monthView'] : td.getSize().x;

                        width = this.colwidth['.monthView'];

                        top = top + (existingEvents * height);
                        var left = width * colcounter;
                        var opts = {'view': 'monthView', 'max-width': width};
                        opts.top = top;
                        if (window.ie) {
                            opts.left = left;
                        }
                        opts.startHour = entry.startdate.getHours();
                        opts.endHour = entry.enddate.getHours();
                        opts.startMin = entry.startdate.getMinutes();
                        opts.endMin = entry.enddate.getMinutes();
                        opts['margin-left'] = 0;
                        td.append(this._makeEventRelDiv(entry, opts, firstDate, td));
                    }
                    j++;
                }.bind(this));
                firstDate.setTime(firstDate.getTime() + this.DAY);
                colcounter++;
            }.bind(this));
        }

        // Watch the mouse to see if it leaves the activeArea - if it does hide the event popup
        $(document).on('mousemove', function (e) {
            var el = $(this),
                x = e.client.x,
                y = e.client.y,
                z = self.activeArea;
            if (typeOf(z) !== 'null' && typeOf(this.activeDay) !== 'null') {
                if ((x <= z.left || x >= z.right) || (y <= z.top || y >= z.bottom)) {
                    if (!self.inFadeOut) {
                        var loc = self.activeHoverEvent.getCoordinates();
                        var fxopts = {
                            'opacity': [1, 0],
                            'top'    : [loc.top - 10, loc.top + 50]
                        };
                        self.fx.showEventActions.cancel().start.delay(500, self.fx.showEventActions, fxopts);
                    }
                    self.activeDay = null;
                }
            }
        });

        this._highLightToday();
        this.el.find('.monthDisplay').html(this.options.months[this.date.getMonth()] + ' ' + this.date.getFullYear());
    },

    _makePopUpWin: function () {
        if (this.options.readonly) {
            return;
        }
        if (this.popup === null) {
            var popLabel = $(document.createElement('div')).addClass('popLabel');
            var del = $(document.createElement('div')).addClass('popupDelete').html(this.options.buttons);

            this.popup = $(document.createElement('div')).addClass('popWin').css({'position': 'absolute'})
                .append([popLabel, del]);

            this.popup.inject(document.body);
            /********** FX EVETNT *************/
            this.activeArea = null;
            this.fx.showEventActions = new Fx.Morph(this.popup, {
                duration    : 500,
                transition  : Fx.Transitions.Quad.easeInOut,
                'onCancel'  : function () {

                }.bind(this),
                'onComplete': function (e) {
                    if (this.activeHoverEvent) {
                        var x = this.popup.getCoordinates();
                        var y = this.activeHoverEvent.getCoordinates();
                        var scrolltop = window.getScrollTop();
                        var z = {};
                        z.left = (x.left < y.left) ? x.left : y.left;
                        z.top = (x.top < y.top) ? x.top : y.top;
                        z.top = z.top - scrolltop;
                        z.right = (x.right > y.right) ? x.right : y.right;
                        z.bottom = (x.bottom > y.bottom) ? x.bottom : y.bottom;
                        z.bottom = z.bottom - scrolltop;
                        this.activeArea = z;
                        this.inFadeOut = false;
                    }
                }.bind(this)
            });
        }
        return this.popup;
    },

    makeDragMonthEntry: function (item) {
    },

    /**
     * Clear all day event divs and reset td classes
     *
     * @since  3.0.7
     */
    removeWeekEvents: function () {
        var wday = this.date.getDay(),
            firstDate = new Date(),
            WeekTds = {},
            trs = this.el.find('.weekView tr'),
            i, j;
        wday = wday - parseInt(this.options.first_week_day, 10);
        firstDate.setTime(this.date.getTime() - (wday * this.DAY));
        for (i = 1; i < trs.length; i++) {
            firstDate.setHours(i - 1, 0, 0);
            if (i !== 1) {
                firstDate.setTime(firstDate.getTime() - (6 * this.DAY));
            }
            var tds = trs[i].find('td');
            for (j = 1; j < tds.length; j++) {
                if (WeekTds[j - 1] === undefined) {
                    WeekTds[j - 1] = [];
                }
                var td = tds[j];
                WeekTds[j - 1].push(td);
                if (j !== 1) {
                    firstDate.setTime(firstDate.getTime() + this.DAY);
                }

                td.addClass('day');
                if (td.data('calevents') !== undefined) {
                    td.data('calevents').each(function (evnt) {
                        evnt.destroy();
                    });
                }
                td.eliminate('calevents');
                td.className = '';
                td.addClass('day');
                td.addClass(firstDate.getTime() - this.HOUR);
                if (this.selectedDate.isSameWeek(firstDate) && this.selectedDate.isSameDay(firstDate)) {
                    td.addClass('selectedDay');
                } else {
                    td.removeClass('selectedDay');
                }
            }
        }
        return WeekTds;

    },

    gridSize: function (counterDate) {
        var maxoffsets = {}, h, startdate, enddate, opts, gridSize = 1;
        this.entries.each(function (entry) {

            // Between (end date present) or same (no end date)
            startdate = new Date(entry.startdate_locale);
            enddate = new Date(entry.enddate_locale);
            if ((entry.enddate !== '' && counterDate.isDateBetween(startdate, enddate)) ||
                (enddate === '' && startdate.isSameDay(counterDate))) {
                opts = this._buildEventOpts({
                    entry     : entry,
                    curdate   : counterDate,
                    divclass  : '.weekView',
                    'tdOffset': i
                });
                // Work out the left offset for the event - stops concurrent events overlapping each other
                for (h = opts.startHour; h <= opts.endHour; h++) {
                    maxoffsets[h] = typeOf(maxoffsets[h]) === 'null' ? 0 : maxoffsets[h] + 1;
                }
            }
        }.bind(this));

        Object.each(maxoffsets, function (o) {
            if (o > gridSize) {
                gridSize = o;
            }
        });

        return gridSize;
    },

    showWeek: function () {
        var monthHtml, i, hdiv, ht, thbg, trs, ths, td, WeekTds,
            firstDate, counterDate, lastDate, gridSize,
            wday = this.date.getDay();
        // Barbara : offset
        wday = wday - parseInt(this.options.first_week_day, 10);

        firstDate = new Date();
        firstDate.setTime(this.date.getTime() - (wday * this.DAY));

        counterDate = new Date();
        counterDate.setTime(this.date.getTime() - (wday * this.DAY));

        lastDate = new Date();
        lastDate.setTime(this.date.getTime() + ((6 - wday) * this.DAY));

        monthHtml = firstDate.getDate() + ' ' + this.options.months[firstDate.getMonth()] +
            ' ' + firstDate.getFullYear() + ' - ' +
            lastDate.getDate() + '  ' + this.options.months[lastDate.getMonth()] + ' ' + lastDate.getFullYear();
        this.el.find('.monthDisplay').html(monthHtml);
        trs = this.el.find('.weekView tr');

        // Put dates in top row
        ths = trs[0].find('th');
        WeekTds = this.removeWeekEvents();

        for (i = 0; i < ths.length; i++) {
            ths[i].className = 'dayHeading';
            ths[i].addClass(counterDate.getTime());

            thbg = ths[i].css('background-color');
            ht = this.options.shortDays[counterDate.getDay()] + ' ' +
                counterDate.getDate() + '/' + this.options.shortMonths[counterDate.getMonth()];
            hdiv = $(document.createElement('div'))
                .css({'background-color': this._getColor(thbg, counterDate)}).text(ht);

            ths[i].empty().append(hdiv);

            var eventWidth = 10;
            var offsets = {};
            var hourTds = WeekTds[i];

            // Build max offsets first
            gridSize = this.gridSize(counterDate);

            // Add event divs
            this.entries.each(function (entry) {

                var startdate = new Date(entry.startdate_locale);
                var enddate = new Date(entry.enddate_locale);

                // Between (end date present) or same (no end date)
                if ((entry.enddate !== '' && counterDate.isDateBetween(startdate, enddate)) || (enddate === '' && startdate.isSameDay(counterDate))) {
                    var opts = this._buildEventOpts({
                        entry     : entry,
                        curdate   : counterDate,
                        divclass  : '.weekView',
                        'tdOffset': i
                    });

                    // Work out the left offset for the event - stops concurrent events overlapping each other
                    for (var h = opts.startHour; h <= opts.endHour; h++) {
                        offsets[h] = typeOf(offsets[h]) === 'null' ? 0 : offsets[h] + 1;
                    }
                    var thisOffset = 0;
                    for (h = opts.startHour; h <= opts.endHour; h++) {
                        if (offsets[h] > thisOffset) {
                            thisOffset = offsets[h];
                        }
                    }
                    var startIndex = Math.max(0, opts.startHour - this.options.open);
                    td = hourTds[startIndex];

                    // Work out event div width - taking into account 1px margin between each event
                    eventWidth = Math.floor((td.getSize().x - gridSize) / (gridSize + 1));
                    opts.width = eventWidth + 'px';
                    opts['margin-left'] = thisOffset * (eventWidth + 1);
                    var div = this._makeEventRelDiv(entry, opts, null, td);
                    div.addClass('week-event');
                    div.inject(document.body);
                    var padding = parseInt(div.css('padding-left'), 10) +
                        parseInt(div.css('padding-right'), 10);
                    div.css('width', parseInt(div.css('width'), 10) - padding + 'px');
                    div.data('opts', opts);
                    div.data('relativeTo', td);
                    div.data('gridSize', gridSize);

                    var calEvents = td.data('calevents');
                    calEvents = calEvents === undefined ? [] : calEvents;
                    calEvents.push(div);
                    td.data('calevents', calEvents);
                    div.position({'relativeTo': td, 'position': 'upperLeft'});
                }
            }.bind(this));
            counterDate.setTime(counterDate.getTime() + this.DAY);
        }
    },

    _buildEventOpts: function (opts) {
        var counterDate = opts.curdate;
        var entry = new CloneObject(opts.entry, true, ['enddate', 'startdate']);//for day view to avoid dups when scrolling through days //dont clone the date objs for ie
        var trs = this.el.find(opts.divclass + ' tr');

        var startdate = new Date(entry.startdate_locale);
        var enddate = new Date(entry.enddate_locale);

        var hour = (startdate.isSameDay(counterDate)) ? startdate.getHours() - this.options.open : 0;
        hour = hour < 0 ? 0 : hour;
        var i = opts.tdOffset;

        entry.label = entry.label ? entry.label : '';
        var td = trs[hour + 1].find('td')[i + 1];
        var orighours = entry.startdate.getHours();
        var rowheight = td.getSize().y;
        //as we buildevent opts twice the sencod parse in IE gives a dif width! so store once and always use that value
        this.colwidth[opts.divclass] = this.colwidth[opts.divclass] ? this.colwidth[opts.divclass] : td.getSize().x;
        var top = this.el.find(opts.divclass).find('tr').getSize().y;

        colwidth = this.colwidth[opts.divclass];

        var left = (colwidth * i);
        left += this.el.find(opts.divclass).find('td').getSize().x;
        var duration = Math.ceil(enddate.getHours() - startdate.getHours());
        if (duration === 0) {
            duration = 1;
        }

        if (!startdate.isSameDay(enddate)) {
            duration = this.options.open !== 0 || this.options.close !== 24 ? this.options.close - this.options.open + 1 : 24;
            if (startdate.isSameDay(counterDate)) {
                duration = this.options.open !== 0 || this.options.close !== 24 ? this.options.close - this.options.open + 1 : 24 - startdate.getHours();
            } else {
                startdate.setHours(0);
                if (enddate.isSameDay(counterDate)) {
                    duration = this.options.open !== 0 || this.options.close !== 24 ? this.options.close - this.options.open : enddate.getHours();
                }
            }
        }

        top = top + (rowheight * hour);
        var height = (rowheight * duration);

        if (enddate.isSameDay(counterDate)) {
            height += (enddate.getMinutes() / 60 * rowheight);
        }
        if (startdate.isSameDay(counterDate)) {
            top += (startdate.getMinutes() / 60 * rowheight);
            height -= (startdate.getMinutes() / 60 * rowheight);
        }

        var existing = td.find('.fabrikEvent');
        var width = colwidth / (existing.length + 1);
        var marginleft = width * existing.length;
        existing.css('width', width + 'px');
        var v = opts.divclass.substr(1, opts.divclass.length);
        width -= parseInt(td.css('border-width'), 10);
        opts = {
            'z-index'         : 999,
            'margin-left'     : marginleft + 'px',
            'height'          : height,
            'view'            : 'weekView',
            'background-color': this._getColor(this.options.colors.headingBg)
        };
        opts['max-width'] = width + 'px';
        opts.left = left;
        opts.top = top;
        opts.color = this._getColor(this.options.colors.headingColor, startdate);
        opts.startHour = startdate.getHours();
        opts.endHour = opts.startHour + duration;
        opts.startMin = startdate.getMinutes();
        opts.endMin = enddate.getMinutes();
        entry.startdate.setHours(orighours);
        return opts;
    },

    /**
     * Clear all day event divs and reset td classes
     *
     * @since  3.0.7
     */
    removeDayEvents: function () {
        var firstDate = new Date();

        var tzOffset = parseInt(new Date().get('gmtoffset').replace(/0+$/, ''), 10);

        var hourTds = [];
        firstDate.setTime(this.date.valueOf());
        firstDate.setHours(0, 0);
        var trs = this.el.find('.dayView tr');

        for (var i = 1; i < trs.length; i++) {
            firstDate.setHours(i - 1 + tzOffset, 0);
            var td = trs[i].find('td')[1];

            if (typeOf(td) !== 'null') {
                hourTds.push(td);
                td.className = '';
                td.addClass('day');

                if (td.data('calevents') !== undefined) {
                    td.data('calevents').each(function (evnt) {
                        evnt.destroy();
                    });
                }

                td.eliminate('calevents');
                td.addClass(firstDate.getTime() - this.HOUR);
                td.set('data-date', firstDate);
            }
        }
        return hourTds;
    },

    /**
     * Show the days events
     */
    showDay: function () {
        var trs = this.el.find('.dayView tr'), h, td, monthHtml,

        // Put date in top row
            thbg = trs[0].childNodes[1].css('background-color');
        ht = this.options.days[this.date.getDay()];
        h = $(document.createElement('div')).css({'background-color': this._getColor(thbg, this.date)}).text(ht);
        trs[0].childNodes[1].empty().append(h);

        // Clear out old data
        var hourTds = this.removeDayEvents();

        var eventWidth = 100;
        var offsets = {};
        var maxoffsets = {};
        this.entries.each(function (entry) {

            // Between (end date present) or same (no end date)
            if ((entry.enddate !== '' && this.date.isDateBetween(entry.startdate, entry.enddate)) || (entry.enddate === '' && entry.startdate.isSameDay(firstDate))) {
                var opts = this._buildEventOpts({
                    entry     : entry,
                    curdate   : this.date,
                    divclass  : '.dayView',
                    'tdOffset': 0
                });

                // Work out the left offset for the event - stops concurrent events overlapping each other
                for (var h = opts.startHour; h <= opts.endHour; h++) {
                    maxoffsets[h] = typeOf(maxoffsets[h]) === 'null' ? 0 : maxoffsets[h] + 1;
                }
            }
        }.bind(this));

        var gridSize = 1;
        Object.each(maxoffsets, function (o) {
            if (o > gridSize) {
                gridSize = o;
            }
        });
        // Add events
        this.entries.each(function (entry) {

            // Between (end date present) or same (no end date)
            if ((entry.enddate !== '' && this.date.isDateBetween(entry.startdate, entry.enddate)) || (entry.enddate === '' && entry.startdate.isSameDay(firstDate))) {
                var opts = this._buildEventOpts({
                    entry     : entry,
                    curdate   : this.date,
                    divclass  : '.dayView',
                    'tdOffset': 0
                });

                var startIndex = Math.max(0, opts.startHour - this.options.open);
                td = hourTds[startIndex];

                // Work out event div width - taking into account 1px margin between each event
                //eventWidth = Math.floor((td.getSize().x - gridSize) / gridSize);
                eventWidth = Math.floor((td.getSize().x - gridSize) / (gridSize + 1));

                opts.width = eventWidth + 'px';

                // Work out the left offset for the event - stops concurrent events overlapping each other
                for (var h = opts.startHour; h <= opts.endHour; h++) {
                    offsets[h] = typeOf(offsets[h]) === 'null' ? 0 : offsets[h] + 1;
                }
                var maxOffset = 0;
                for (h = opts.startHour; h <= opts.endHour; h++) {
                    if (offsets[h] > maxOffset) {
                        maxOffset = offsets[h];
                    }
                }
                opts['margin-left'] = maxOffset * (eventWidth + 1);
                var div = this._makeEventRelDiv(entry, opts, null, td);
                div.addClass('day-event');
                div.data('relativeTo', td);
                div.data('gridSize', gridSize);
                div.inject(document.body);

                var padding = parseInt(div.css('padding-left'), 10) +
                    parseInt(div.css('padding-right'), 10);
                div.css('width', parseInt(div.css('width'), 10) - padding + 'px');
                div.data('opts', opts);

                var calEvents = td.data('calevents');
                calEvents = calEvents === undefined ? [] : calEvents;
                calEvents.push(div);
                td.data('calevents', calEvents);
                div.position({'relativeTo': td, 'position': 'upperLeft'});
            }
        }.bind(this));

        monthHtml = this.date.getDate() + '  ' + this.options.months[this.date.getMonth()] + ' ' +
            this.date.getFullYear();
        this.el.find('.monthDisplay').html(monthHtml);
    },

    renderMonthView: function () {
        var d, tr, daysLabels, firstWeekDay = this.options.first_week_day;
        this.fadePopWin(0);
        var firstDate = this._getFirstDayInMonthCalendar(new Date());

        // Barbara : reorganize days labels according to first day of week
        daysLabels = this.options.days.slice(firstWeekDay).concat(this.options.days.slice(0, firstWeekDay));

        // Barbara : set a tmpDate that has the same shift regarding the beginning of the week
        var tmpDate = new Date();
        tmpDate.setTime(firstDate.valueOf());
        if (firstDate.getDay() !== firstWeekDay) {
            var backwardsDaysDelta = firstDate.getDay() - firstWeekDay;
            //first day of week
            tmpDate.setTime(firstDate.valueOf() - (backwardsDaysDelta * 24 * 60 * 60 * 1000));
        }

        this.options.viewType = 'monthView';

        this.setAddButtonState();

        if (!this.mothView) {
            tbody = $(document.createElement('tbody')).addClass('viewContainerTBody');
            tr = $(document.createElement('tr'));
            // Barbara : added greyscaled week-ends color option
            for (d = 0; d < 7; d++) {
                tr.append($(document.createElement('th')).addClass('dayHeading').css({
                        'width'           : '80px',
                        'height'          : '20px',
                        'text-align'      : 'center',
                        'color'           : this._getColor(this.options.colors.headingColor, tmpDate),
                        'background-color': this._getColor(this.options.colors.headingBg, tmpDate)
                    }
                ).text(daysLabels[d]));
                // Barbara : added use of tmpDate
                tmpDate.setTime(tmpDate.getTime() + this.DAY);
            }
            tbody.appendChild(tr);

            var highLightColor = this.options.colors.highlight;
            var bgColor = this.options.colors.bg;
            var todayColor = this.options.colors.today;
            // Barbara : 6 lines are needed in some cases, when a month starts the day before the week first day.
            for (var i = 0; i < 6; i++) {
                tr = $(document.createElement('tr'));
                var parent = this;
                for (d = 0; d < 7; d++) {

                    //'display': 'table-cell', doesnt work in IE7
                    var bgCol = this.options.colors.bg;
                    var extraClass = (this.selectedDate.isSameDay(firstDate)) ? 'selectedDay' : '';
                    tr.append($(document.createElement('td'))
                        .addClass('day ' + (firstDate.getTime()) + extraClass)
                        .css({
                            'width'           : this.options.monthday.width + 'px',
                            'height'          : this.options.monthday.height + 'px',
                            'background-color': bgCol,
                            'vertical-align'  : 'top',
                            'padding'         : 0,
                            'border'          : '1px solid #cccccc'
                        }).on('mouseenter', function () {
                            $(this).css({'background-color': highLightColor});
                        }).on('mouseleave', function () {
                            this.set('morph', {duration: 500, transition: Fx.Transitions.Sine.easeInOut});
                            var toCol = (this.hasClass('today')) ? todayColor : bgColor;
                            this.morph({'background-color': [highLightColor, toCol]});
                        }).on('click', function (e) {
                            parent.selectedDate.setTime(this.className.split(' ')[1]);
                            parent.date.setTime(parent._getTimeFromClassName(this.className));
                            parent.el.find('td').each(function (td) {
                                td.removeClass('selectedDay');
                                if (td !== this) {
                                    td.csss({'background-color': '#F7F7F7'});
                                }
                            }.bind(this));
                            this.addClass('selectedDay');
                        }).on('dblclick', function (e) {
                            if (this.options.readonlyMonth === false) {
                                self.openAddEvent(e, 'month');
                            }
                        }));
                    firstDate.setTime(firstDate.getTime() + this.DAY);
                }
                tbody.appendChild(tr);
            }
            this.mothView = $(document.createElement('div')).addClass('monthView')
                .css({
                    'position': 'relative'
                }
            ).append(
                $(document.createElement('table')).css({
                        'border-collapse': 'collapse'
                    }
                ).append(
                    tbody
                )
            );
            this.el.find('.viewContainer').appendChild(this.mothView);
        }
        this.showView('monthView');
    },

    /**
     * Toggle the add event visibily button based on the view type and whether that view allows for additions
     */
    setAddButtonState: function () {
        var addButton = this.el.find('.addEventButton');
        if (typeOf(addButton) !== 'null') {
            if (this.options.viewType === 'monthView' && this.options.readonlyMonth === true) {
                addButton.hide();
            } else {
                addButton.show();
            }
        }
    },

    _getTimeFromClassName: function (n) {
        return n.replace('today', '').replace('selectedDay', '').replace('day', '').replace('otherMonth', '').trim();
    },

    /**
     * Open the add event form.
     *
     * @param e    Event
     * @param view The view which triggered the opening
     */
    openAddEvent: function (e, view) {
        var rawd, day, hour, min, m, o, now, thisDay;

        if (this.options.canAdd === false) {
            return;
        }

        if (this.options.viewType === 'monthView' && this.options.readonlyMonth === true) {
            return;
        }

        e.stopPropagation();

        if (e.target.className === 'addEventButton') {
            now = new Date();
            rawd = now.getTime();
        } else {
            rawd = this._getTimeFromClassName(e.target.className);
        }

        if (!this.dateInLimits(rawd)) {
            return;
        }

        if (e.target.get('data-date')) {
            console.log('data-date = ', e.target.get('data-date'));

        }
        this.date.setTime(rawd);
        var d = 0;
        if (!isNaN(rawd) && rawd !== '') {
            thisDay = new Date();
            thisDay.setTime(rawd);
            m = thisDay.getMonth() + 1;
            m = (m < 10) ? '0' + m : m;
            day = thisDay.getDate();
            day = (day < 10) ? '0' + day : day;

            if (view !== 'month') {
                hour = thisDay.getHours();
                hour = (hour < 10) ? '0' + hour : hour;
                min = thisDay.getMinutes();
                min = (min < 10) ? '0' + min : min;
            } else {
                hour = '00';
                min = '00';
            }

            this.doubleclickdate = thisDay.getFullYear() + '-' + m + '-' + day + ' ' + hour + ':' + min + ':00';
            d = '&jos_fabrik_calendar_events___start_date=' + this.doubleclickdate;
        }

        if (this.options.eventLists.length > 1) {
            this.openChooseEventTypeForm(this.doubleclickdate, rawd);
        } else {
            o = {};
            o.rowid = '';
            o.id = '';
            d = '&' + this.options.eventLists[0].startdate_element + '=' + this.doubleclickdate;
            o.listid = this.options.eventLists[0].value;
            this.addEvForm(o);
        }
    },

    dateInLimits: function (time) {
        var d = new Date();
        d.setTime(time);

        if (this.options.dateLimits.min !== '') {
            var min = new Date(this.options.dateLimits.min);
            if (d < min) {
                alert(Joomla.JText._('PLG_VISUALIZATION_CALENDAR_DATE_ADD_TOO_EARLY'));
                return false;
            }
        }

        if (this.options.dateLimits.max !== '') {
            var max = new Date(this.options.dateLimits.max);
            if (d > max) {
                alert(Joomla.JText._('PLG_VISUALIZATION_CALENDAR_DATE_ADD_TOO_LATE'));
                return false;
            }
        }

        return true;
    },

    openChooseEventTypeForm: function (d, rawd) {
        // Rowid is the record to load if editing
        var url = 'index.php?option=com_fabrik&tmpl=component&view=visualization&controller=visualization.calendar&task=chooseaddevent&id=' + this.options.calendarId + '&d=' + d + '&rawd=' + rawd;

        // Fix for renderContext when rendered in content plugin
        url += '&renderContext=' + this.el.id.replace(/visualization_/, '');
        this.windowopts.contentURL = url;
        this.windowopts.id = 'chooseeventwin';
        this.windowopts.onContentLoaded = function () {
            var myfx = new Fx.Scroll(window).toElement('chooseeventwin');
        };
        Fabrik.getWindow(this.windowopts);
    },

    /**
     * Create window for add event form
     *
     * @param  object  o
     */
    addEvForm: function (o) {
        if (typeof(jQuery) !== 'undefined') {
            jQuery(this.popOver).popover('hide');
        }

        this.windowopts.id = 'addeventwin';
        var url = 'index.php?option=com_fabrik&controller=visualization.calendar&view=visualization&task=addEvForm&format=raw&listid=' + o.listid + '&rowid=' + o.rowid;
        url += '&jos_fabrik_calendar_events___visualization_id=' + this.options.calendarId;
        url += '&visualizationid=' + this.options.calendarId;

        if (o.nextView) {
            url += '&nextview=' + o.nextView;
        }

        url += '&fabrik_window_id=' + this.windowopts.id;

        if (typeof(this.doubleclickdate) !== 'undefined') {
            url += '&start_date=' + this.doubleclickdate;
        }

        this.windowopts.type = 'window';
        this.windowopts.contentURL = url;
        var f = this.options.filters;

        this.windowopts.onContentLoaded = function (win) {
            f.each(function (o) {
                switch ($('#' + o.key).prop('tagName')) {
                    case 'SELECT':
                        $('#' + o.key).prop('selectedIndex', o.val);
                        break;
                    case 'INPUT':
                        $('#' + o.key).val(o.val);
                        break;
                }
            });
            win.fitToContent(false);
        }.bind(this);

        Fabrik.getWindow(this.windowopts);
    },

    _highLightToday: function () {
        var today = new Date();
        this.el.find('.viewContainerTBody td').each(
            function (td) {
                var newDate = parseInt(new Date(this._getTimeFromClassName(td.className), 10));
                if (today.equalsTo(newDate)) {
                    td.addClass('today');
                } else {
                    td.removeClass('today');
                }
            }.bind(this)
        );
    },

    centerOnToday: function () {
        this.date = new Date();
        this.showView();
    },

    renderDayView: function () {
        var tr, d, tbody, i, self = this;
        this.fadePopWin(0);
        this.options.viewType = 'dayView';
        this.setAddButtonState();

        if (!this.dayView) {
            tbody = $(document.createElement('tbody'));
            tr = $(document.createElement('tr'));
            for (d = 0; d < 2; d++) {
                if (d === 0) {
                    tr.append($(document.createElement('td')).addClass('day'));
                } else {
                    tr.append($(document.createElement('th')).addClass('dayHeading')
                        .css({
                            'width'           : '80px',
                            'height'          : '20px',
                            'text-align'      : 'center',
                            'color'           : this.options.headingColor,
                            'background-color': this.options.colors.headingBg
                        })
                        .text(this.options.days[this.date.getDay()]));
                }
            }
            tbody.appendChild(tr);

            this.options.open = this.options.open < 0 ? 0 : this.options.open;
            this.options.close = (this.options.close > 24 || this.options.close < this.options.open) ? 24 : this.options.close;

            for (i = this.options.open; i < (this.options.close + 1); i++) {
                tr = $(document.createElement('tr'));
                for (d = 0; d < 2; d++) {
                    if (d === 0) {
                        var hour = (i.length === 1) ? i + '0:00' : i + ':00';
                        tr.append($(document.createElement('td')).addClass('day').text(hour));
                    } else {
                        //'display': 'table-cell',
                        tr.append($(document.createElement('td')).addClass('day')
                                .css({
                                    'width'           : '100%',
                                    'height'          : '10px',
                                    'background-color': '#F7F7F7',
                                    'vertical-align'  : 'top',
                                    'padding'         : 0,
                                    'border'          : '1px solid #cccccc'
                                }).on('mouseenter', function () {
                                    this.css({
                                        'background-color': '#FFFFDF'
                                    });
                                }).on('mouseleave', function () {
                                    this.css({
                                        'background-color': '#F7F7F7'
                                    });
                                }).on('dblclick', function (e) {
                                    self.openAddEvent(e, 'day');
                                })
                        );
                    }
                }
                tbody.appendChild(tr);
            }
            this.dayView = $(document.createElement('div')).addClass('dayView')
                .css({
                    'position': 'relative'
                })
                .append(
                $(document.createElement('table')).css({'border-collapse': 'collapse'})
                    .append(
                    tbody
                )
            );
            this.el.find('.viewContainer').appendChild(this.dayView);
        }
        this.showView('dayView');
    },

    hideDayView: function () {
        if (this.el.find('.dayView')) {
            this.el.find('.dayView').hide();
            this.removeDayEvents();
        }
    },

    hideWeekView: function () {
        if (this.el.find('.weekView')) {
            this.el.find('.weekView').hide();
            this.removeWeekEvents();
        }
    },

    showView: function (view) {
        this.hideDayView();
        this.hideWeekView();
        if (this.el.find('.monthView')) {
            this.el.find('.monthView').hide();
        }
        this.el.find('.' + this.options.viewType).style.display = 'block';
        switch (this.options.viewType) {
            case 'dayView':
                this.showDay();
                break;
            case 'weekView':
                this.showWeek();
                break;
            default:
            case 'monthView':
                this.showMonth();
                break;
        }
        Cookie.write('fabrik.viz.calendar.view', this.options.viewType);
    },

    renderWeekView: function () {
        var i, d, tr, tbody, we, self = this;
        this.fadePopWin(0);
        we = this.options.showweekends === false ? 6 : 8;
        this.options.viewType = 'weekView';
        this.setAddButtonState();
        if (!this.weekView) {
            tbody = $(document.createElement('tbody'));
            tr = $(document.createElement('tr'));
            for (d = 0; d < we; d++) {
                if (d === 0) {
                    tr.append($(document.createElement('td')).addClass('day'));
                } else {
                    tr.append($(document.createElement('th')).addClass('dayHeading')
                        .css({
                            'width'           : this.options.weekday.width + 'px',
                            'height'          : (this.options.weekday.height - 10) + 'px',
                            'text-align'      : 'center',
                            'color'           : this.options.headingColor,
                            'background-color': this.options.colors.headingBg
                        }).on('click', function (e) {
                            e.stopPropagation();
                            self.selectedDate.setTime(parseInt($(this).prop('class').replace('dayHeading ', ''), 10));
                            var tmpdate = new Date();
                            $(this).parent().parent().find('td').each(function () {
                                var t = parseInt($(this).prop('class').replace('day ', '').replace(' selectedDay'), 10);
                                tmpdate.setTime(t);
                                if (tmpdate.getDayOfYear() === this.selectedDate.getDayOfYear()) {
                                    $(this).addClass('selectedDay');
                                } else {
                                    $(this).removeClass('selectedDay');
                                }
                            });
                        }).text(this.options.days[d - 1]));
                }
            }
            tbody.appendChild(tr);

            this.options.open = this.options.open < 0 ? 0 : this.options.open;
            (this.options.close > 24 || this.options.close < this.options.open) ? this.options.close = 24 : this.options.close;

            for (i = this.options.open; i < (this.options.close + 1); i++) {
                tr = $(document.createElement('tr'));
                for (d = 0; d < we; d++) {
                    if (d === 0) {
                        var hour = (i.length === 1) ? i + '0:00' : i + ':00';
                        tr.append($(document.createElement('td')).addClass('day').text(hour));
                    } else {
                        tr.append($(document.createElement('td')).addClass('day')
                            .css({
                                'width'           : this.options.weekday.width + 'px',
                                'height'          : this.options.weekday.height + 'px',
                                'background-color': '#F7F7F7',
                                'vertical-align'  : 'top',
                                'padding'         : 0,
                                'border'          : '1px solid #cccccc'
                            }).on('mouseenter', function () {
                                if (!$(this).hasClass('selectedDay')) {
                                    $(this).css({
                                        'background-color': '#FFFFDF'
                                    });
                                }
                            }).on('mouseleave', function () {
                                if (!$(this).hasClass('selectedDay')) {
                                    $(this).css({
                                        'background-color': '#F7F7F7'
                                    });
                                }
                            }).on('dblclick', function (e) {
                                self.openAddEvent(e, 'week');
                            }));
                    }
                }
                tbody.appendChild(tr);
            }
            this.weekView = $(document.createElement('div')).addClass('weekView').css({
                'position': 'relative'
            }).append(
                $(document.createElement('table')).css({
                    'border-collapse': 'collapse'
                }).append(
                    tbody
                )
            );

            this.el.find('.viewContainer').appendChild(this.weekView);
        }
        this.showWeek();
        this.showView('weekView');
    },

    repositionEvents: function () {
        $('a.week-event, a.day-event').each(function () {
            var td = $(this).data('relativeTo');
            $(this).position({'relativeTo': td, 'position': 'upperLeft'});
            var gridSize = $(this).data('gridSize');
            var eventWidth = Math.floor((td.getSize().x - gridSize) / gridSize);
            var padding = parseInt($(this).css('padding-left'), 10) + parseInt($(this).css('padding-right'), 10);
            eventWidth = eventWidth - padding;
            $(this).css('width', eventWidth + 'px');
        });
    },

    render: function (options) {
        var self = this;
        this.setOptions(options);

        // Resize week & day events when the window re-sizes
        $(window).on('resize', function () {
            self.repositionEvents();
        });

        // Get the container height
        this.y = this.el.getPosition().y;
        var refreshDocHeight = function () {
            var y = this.el.getPosition().y;
            if (y !== this.y) {
                this.y = y;
                this.repositionEvents();
            }
        }.bind(this);

        // update the height every 200ms
        window.setInterval(refreshDocHeight, 200);

        $(document).on('click', 'button[data-task=deleteCalEvent], a[data-task=deleteCalEvent]', function (event) {
            event.preventDefault();
            self.deleteEntry();
        });

        $(document).on('click', 'button[data-task=editCalEvent], a[data-task=editCalEvent]', function (event) {
            event.preventDefault();
            self.editEntry();
        });

        $(document).on('click', 'button[data-task=viewCalEvent], a[data-task=viewCalEvent]', function (event) {
            event.preventDefault();

            // If opening directly from a calendar entery activeHoverEvent is not yet set.
            if (!self.activeHoverEvent) {
                self.activeHoverEvent = $(this).hasClass('fabrikEvent') ? $(this) : $(this).closest('.fabrikEvent');
            }
            self.viewEntry();
        });

        $(document).on('click', 'a.fabrikEvent)', function (e) {
            self.activeHoverEvent = $(this).hasClass('fabrikEvent') ? $(this) : $(this).closest('.fabrikEvent');
        });

        this.windowopts.title = Joomla.JText._('PLG_VISUALIZATION_CALENDAR_ADD_EDIT_EVENT');
        this.windowopts.y = this.options.popwiny;
        this.popWin = this._makePopUpWin();
        var d = this.options.urlfilters;
        d.visualizationid = this.options.calendarId;
        if (this.firstRun) {
            this.firstRun = false;
            d.resetfilters = this.options.restFilterStart;
        }


        this.el.find('.addEventButton').on('click', function (e) {
            self.openAddEvent(e);
        });
        var bs = [];
        var nav = $(document.createElement('div')).addClass('calendarNav').append(
            $(document.createElement('ul')).addClass('viewMode').append(bs));

        this.el.appendChild(nav);
        //position relative messes up the drag of events
        this.el.appendChild($(document.createElement('div')).addClass('viewContainer'));

        if (typeOf(Cookie.read('fabrik.viz.calendar.date')) !== 'null') {
            this.date = new Date(Cookie.read('fabrik.viz.calendar.date'));
        }
        var startview = typeOf(Cookie.read('fabrik.viz.calendar.view')) === 'null' ? this.options.viewType : Cookie.read('fabrik.viz.calendar.view');
        switch (startview) {
            case 'dayView':
                this.renderDayView();
                break;
            case 'weekView':
                this.renderWeekView();
                break;
            default:
            case 'monthView':
                this.renderMonthView();
                break;
        }

        //this.showView();

        this.el.find('.nextPage').on('click', function (e) {
            self.nextPage(e);
        });
        this.el.find('.previousPage').on('click', function (e) {
            self.previousPage(e);
        });

        if (this.options.show_day) {
            this.el.find('.dayViewLink').on('click', function (e) {
                self.renderDayView(e);
            });
        }
        if (this.options.show_week) {
            this.el.find('.weekViewLink').on('click', function (e) {
                self.renderWeekView(e);
            });
        }
        if (this.options.show_week || this.options.show_day) {
            this.el.find('.monthViewLink').on('click', function (e) {
                self.renderMonthView(e);
            });
        }
        this.el.find('.centerOnToday').on('click', function (e) {
            self.centerOnToday(e);
        });
        this.showMonth();
        this.updateEvent();
    },

    showMessage: function (m) {
        this.el.find('.calendar-message').html(m);
        this.fx.showMsg.start({
            'opacity': [0, 1]
        }).chain(
            function () {
                this.start.delay(2000, this, {'opacity': [1, 0]});
            }
        );
    },

    addEntry: function (key, o) {
        var d, d2, m, time;
        //test if time was passed as well
        if (o.startdate) {
            d = o.startdate.split(' ');
            d = d[0];
            if (d.trim() === '') {
                return;
            }
            time = o.startdate.split(' ');
            time = time[1];
            time = time.split(':');
            d = d.split('-');
            d2 = new Date();
            m = parseInt(d[1], 10) - 1;
            //setFullYear produced a stack overflow in ie7 go figure? and recursrive error in ff6 - reverting to setYear()
            d2.setYear(d[0]);
            d2.setMonth(m, d[2]);
            d2.setDate(d[2]);
            d2.setHours(parseInt(time[0], 10));
            d2.setMinutes(parseInt(time[1], 10));
            d2.setSeconds(parseInt(time[2], 10));
            o.startdate = d2;
            this.entries[key] = o;

            if (o.enddate) {
                d = o.enddate.split(' ');
                d = d[0];
                if (d.trim() === '') {
                    return;
                }
                if (d === '0000-00-00') {
                    o.enddate = o.startdate;
                    return;
                }
                time = o.enddate.split(' ');
                time = time[1];
                time = time.split(':');

                d = d.split('-');
                d2 = new Date();
                m = parseInt(d[1], 10) - 1;
                //setFullYear produced a stack overflow in ie7 go figure? and recursrive error in ff6 - reverting to setYear()
                d2.setYear(d[0]);
                d2.setMonth(m, d[2]);
                d2.setDate(d[2]);
                d2.setHours(parseInt(time[0], 10));
                d2.setMinutes(parseInt(time[1], 10));
                d2.setSeconds(parseInt(time[2], 10));
                o.enddate = d2;
            }
        }

    },

    deleteEntry: function () {
        var key = this.activeHoverEvent.id.replace('fabrikEvent_', '');
        var i = key.split('_');
        var listid = i[0];
        if (!this.options.deleteables.contains(listid)) {
            //doesnt have acess to delete
            return;
        }

        if (confirm(Joomla.JText._('PLG_VISUALIZATION_CALENDAR_CONF_DELETE'))) {
            this.deleteEvent({'id': i[1], 'listid': listid});
            $('#' + this.activeHoverEvent).fade('out');
            this.fx.showEventActions.start({'opacity': [1, 0]});
            this.removeEntry(key);
            this.activeDay = null;
        }
    },

    editEntry: function (e) {
        var o = {};
        o.id = this.options.formid;
        var i = this.activeHoverEvent.id.replace('fabrikEvent_', '').split('_');
        o.rowid = i[1];
        o.listid = i[0];
        if (e) {
            e.stopPropagation();
        }
        this.addEvForm(o);
    },

    viewEntry: function () {
        var o = {};
        o.id = this.options.formid;
        var i = this.activeHoverEvent.id.replace('fabrikEvent_', '').split('_');
        o.rowid = i[1];
        o.listid = i[0];
        o.nextView = 'details';
        this.addEvForm(o);
    },

    addEntries: function (a) {
        jQuery.each(a, function (key, obj) {
            this.addEntry(key, obj);
        }.bind(this));
        this.showView();
    },

    removeEntry: function (eventId) {
        delete this.entries[eventId];
        this.showView();
    },

    nextPage: function () {
        this.fadePopWin(0);
        switch (this.options.viewType) {
            case 'dayView':
                this.date.setTime(this.date.getTime() + this.DAY);
                this.showDay();
                break;
            case 'weekView':
                this.date.setTime(this.date.getTime() + this.WEEK);
                this.showWeek();
                break;
            case 'monthView':
                this.date.setDate(1);
                this.date.setMonth(this.date.getMonth() + 1);
                this.showMonth();
                break;
        }
        Cookie.write('fabrik.viz.calendar.date', this.date);
    },

    fadePopWin: function (o) {
        if (this.popWin) {
            this.popWin.css('opacity', o);
        }
    },

    previousPage: function () {
        this.fadePopWin(0);
        switch (this.options.viewType) {
            case 'dayView':
                this.date.setTime(this.date.getTime() - this.DAY);
                this.showDay();
                break;
            case 'weekView':
                this.date.setTime(this.date.getTime() - this.WEEK);
                this.showWeek();
                break;
            case 'monthView':
                this.date.setMonth(this.date.getMonth() - 1);
                this.showMonth();
                break;
        }
        Cookie.write('fabrik.viz.calendar.date', this.date);
    },

    addLegend: function (a) {
        var ul = $(document.createElement('ul'));
        a.each(function (l) {
            var li = $(document.createElement('li'));
            li.append($(document.createElement('div')).css({'background-color': l.colour}),
                $(document.createElement('span')).text(l.label)
            );
            ul.appendChild(li);
        }.bind(this));
        $(document.createElement('div')).addClass('calendar-legend').append([
            $(document.createElement('h3')).text(Joomla.JText._('PLG_VISUALIZATION_CALENDAR_KEY')),
            ul
        ]).inject(this.el, 'after');
    },

    /**
     * Barbara : commonly used RGB to greyscale formula.
     * Param : #RRGGBB string.
     * Returns : #RRGGBB string.
     */
    _getGreyscaleFromRgb: function (rgbHexa) {
        // convert to decimal
        var r = parseInt(rgbHexa.substring(1, 3), 16);
        var g = parseInt(rgbHexa.substring(3, 5), 16);
        var b = parseInt(rgbHexa.substring(5), 16);
        var greyVal = parseInt(0.3 * r + 0.59 * g + 0.11 * b, 10);
        return '#' + greyVal.toString(16) + greyVal.toString(16) + greyVal.toString(16);
    },

    /**
     * Barbara : returns greyscaled color of param color if :
     * - greyscaledweekend option is set
     * - and param date is not null (i.e. we are in month view) and corresponds to a Saturday or Sunday.
     * Params : #RRGGBB color string, optional date
     * Returns : #RRGGBB param or greyscale converted color string.
     */
    _getColor: function (aColor, aDate) {
        if (!this.options.greyscaledweekend) {
            return aColor;
        }
        var c = new Color(aColor);
        if (typeOf(aDate) !== 'null' && (aDate.getDay() === 0 || aDate.getDay() === 6)) {
            return this._getGreyscaleFromRgb(aColor);
        } else {
            return aColor;
        }
    }

});

// BEGIN: DATE OBJECT PATCHES

/** Adds the number of days array to the Date object. */
Date._MD = new Array(31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31);

/** Constants used for time computations */
/* milliseconds */
Date.SECOND = 1000;
Date.MINUTE = 60 * Date.SECOND;
Date.HOUR = 60 * Date.MINUTE;
Date.DAY = 24 * Date.HOUR;
Date.WEEK = 7 * Date.DAY;

/** Returns the number of days in the current month */
Date.prototype.getMonthDays = function (month) {
    var year = this.getFullYear();
    if (typeof month === 'undefined') {
        month = this.getMonth();
    }
    if (((0 === (year % 4)) && ((0 !== (year % 100)) || (0 === (year % 400)))) && month === 1) {
        return 29;
    } else {
        return Date._MD[month];
    }
};

Date.prototype.isSameWeek = function (date) {
    return ((this.getFullYear() === date.getFullYear()) &&
    (this.getMonth() === date.getMonth()) &&
    (this.getWeekNumber() === date.getWeekNumber()));
};

Date.prototype.isSameDay = function (date) {
    return ((this.getFullYear() === date.getFullYear()) &&
    (this.getMonth() === date.getMonth()) &&
    (this.getDate() === date.getDate()));
};

Date.prototype.isSameHour = function (date) {
    return ((this.getFullYear() === date.getFullYear()) &&
    (this.getMonth() === date.getMonth()) &&
    (this.getDate() === date.getDate()) &&
    (this.getHours() === date.getHours()));
};

/* Barbara : checks that the date is between two dates (ignores time) */
Date.prototype.isDateBetween = function (startdate, enddate) {
    var strStartDate = startdate.getFullYear() * 10000 + (startdate.getMonth() + 1) * 100 + startdate.getDate();
    var strEndDate = enddate.getFullYear() * 10000 + (enddate.getMonth() + 1) * 100 + enddate.getDate();
    var strCurrentDate = this.getFullYear() * 10000 + (this.getMonth() + 1) * 100 + this.getDate();
    return strStartDate <= strCurrentDate && strCurrentDate <= strEndDate;
};