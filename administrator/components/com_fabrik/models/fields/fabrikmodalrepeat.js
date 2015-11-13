/**
 * Admin Modal Repeat Editor
 *
 * @copyright: Copyright (C) 2005-2015, fabrikar.com - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

'use strict';

var FabrikModalRepeat = my.Class({

    options: {
        j3: true
    },

    constructor: function (el, names, field, opts) {
        var self = this;
        this.names = names;
        this.field = field;
        this.content = false;
        this.setup = false;
        this.elid = el;
        this.win = {};
        this.el = {};
        this.field = {};
        this.options = $.extend(this.options, opts);

        // If the parent field is inserted via js then we delay the loading until the html is present
        if (!this.ready()) {

            var timerFn = function () {
                self.testReady.call(self, true);
            };
            this.timer = setInterval(timerFn, 500);

        } else {
            this.setUp();
        }
    },

    ready: function () {
        // Not sure why but can't use $
        return jQuery('#' + this.elid).length > 0;
    },

    testReady: function () {
        if (!this.ready()) {
            return;
        }
        if (this.timer) {
            clearInterval(this.timer);
        }
        this.setUp();
    },

    setUp: function () {
        var self = this;
        this.button = jQuery('#' + this.elid + '_button');
        this.mask = new Mask(document.body, {style: {'background-color': '#000', 'opacity': 0.4, 'z-index': 9998}});
        jQuery(document).on('click', '*[data-modal=' + this.elid + ']', function (e) {
            e.preventDefault();
            var tbl,
            // Correct when in repeating group
                id = jQuery(this).next('input').prop('id'),
                c = jQuery(this).closest('div.control-group');
            self.field[id] = $(this).next('input');
            self.origContainer = c;
            tbl = c.find('table');
            if (tbl.length > 0) {
                self.el[id] = tbl;
            }
            self.openWindow(id);
        });
    },

    openWindow: function (target) {
        var makeWin = false;
        if (!this.win[target]) {
            makeWin = true;
            this.makeTarget(target);
        }
        this.el[target].prependTo(this.win[target]);
        this.el[target].show();

        if (!this.win[target] || makeWin) {
            this.makeWin(target);
        }
        this.win[target].show();
        this.win[target].position();
        this.resizeWin();
        this.win[target].position();
        this.mask.show();
    },

    makeTarget: function (target) {
        this.win[target] = jQuery(document.createElement('div')).data(
           'modal-content', target
        ).css({
            'padding'         : '5px',
            'background-color': '#fff',
            'display'         : 'none',
            'z-index'         : 9999
        }).prependTo(document.body);

    },

    makeWin: function (target) {
        var self = this;
        var close = jQuery(document.createElement('button')).addClass('btn button btn-primary').text('close');
        close.on('click', function (e) {
            e.stopPropagation();
            self.store(target);
            self.el[target].hide();
            self.el[target].inject(self.origContainer);
            self.close();
        });
        var controls = jQuery(document.createElement('div')).addClass('controls form-actions').css({
            'text-align'   : 'right',
            'margin-bottom': 0
        }).append(close);

        this.win[target].append(controls);
        this.win[target].position();
        this.content = this.el[target];
        this.build(target);
        this.watchButtons(this.win[target], target);
    },

    resizeWin: function () {
        var self = this;
        Object.each(this.win, function (win, key) {
            //var size = self.el[key].getDimensions(true);
            win.css({'width': self.el[key].width() + 'px'});
        });
    },

    close: function () {
        Object.each(this.win, function (win) {
            win.hide();
        });
        this.mask.hide();
    },

    _getRadioValues: function (target) {
        var radioVals = [];
        this.getTrs(target).each(function () {
            var v = $(this).find('input[type=radio]:checked').val();
            radioVals.push(v);
        });
        return radioVals;
    },

    _setRadioValues: function (radiovals, target) {
        // Reapply radio button selections
        this.getTrs(target).each(function (i) {
            $(this).find('input[type=radio][value=' + radiovals[i] + ']').prop('checked', true);
        });
    },

    /**
     * Add a new row of fields
     * @param target
     * @param {jQuery} source Source of the row to copy
     */
    addRow: function (target, source) {
        // Store radio button selections
        var radioVals = this._getRadioValues(target),
            body = source.closest('table').find('tbody'),
            clone = this.tmpl.clone(true, true);
        clone.appendTo(body);
        this.stripe(target);

        this.fixUniqueAttributes(source, clone);

        // Reapply values as renaming radio buttons
        this._setRadioValues(radioVals, target);
        this.resizeWin();
        this.resetChosen(clone);
    },

    /**
     * Ensure that a new row has unique ids, names and label for properites
     *
     * @param source
     * @param row
     */
    fixUniqueAttributes: function (source, row) {
        var rowCount = source.closest('table').find('tr').length - 1;

        jQuery.each(row.find('*[name]'), function () {
            $(this).prop('name', $(this).prop('name') + '-' + rowCount);
        });
        jQuery.each(row.find('*[id]'), function () {
            $(this).prop('id', $(this).prop('id') + '-' + rowCount);
        });
        jQuery.each(row.find('label[for]'), function () {
            $(this).prop('label', $(this).prop('label') + '-' + rowCount);
        });
    },

    /**
     *
     * @param {jQuery} win
     * @param target
     */
    watchButtons: function (win, target) {
        var tr, self = this;
        win.on('click', 'a.add', function (e) {
            if (tr = self.findTr(e)) {
                self.addRow(target, tr);
            }
            win.position();
            e.stopPropagation();
        });
        win.on('click', 'a.remove', function (e) {
            if (tr = self.findTr(e)) {
                tr.remove();
            }
            self.resizeWin();
            win.position();
            e.stopPropagation();
        });
    },

    resetChosen: function (clone) {
        if (jQuery('select').chosen) {

            // Chosen reset
            clone.find('select').removeClass('chzn-done').show();

            // Assign random id
            jQuery(clone.find('select'), function (key, c) {
                $(this).prop('id', $(this).prop('id') + '_' + parseInt(Math.random() * 10000000, 10));
            });
            clone.find('.chzn-container').destroy();

            jQuery(clone).find('select').chosen({
                disable_search_threshold: 10,
                allow_single_deselect   : true
            });
        }
    },

    getTrs: function (target) {
        return this.win[target].find('tbody').find('tr');
    },

    /**
     * Stripe each of the list rows
     * @param target
     */
    stripe: function (target) {
        var trs = this.getTrs(target);
        for (var i = 0; i < trs.length; i++) {
            trs[i].removeClass('row1').removeClass('row0');
            trs[i].addClass('row' + i % 2);
        }
    },

    build: function (target) {
        if (!this.win[target]) {
            this.makeWin(target);
        }

        var a = JSON.decode(this.field[target].val());
        if (typeOf(a) === 'null') {
            a = {};
        }
        var tr = this.win[target].find('tbody').find('tr'),
            keys = Object.keys(a),
            newrow = keys.length === 0 || a[keys[0]].length === 0 ? true : false,
            rowcount = newrow ? 1 : a[keys[0]].length;

        // Build the rows from the json object
        for (var i = 1; i < rowcount; i++) {
            var clone = tr.clone();
            this.fixUniqueAttributes(tr, clone);
            clone.inject(tr, 'after');
            this.resetChosen(clone);
        }
        this.stripe(target);
        var trs = this.getTrs(target);

        // Populate the cloned fields with the json values
        for (i = 0; i < rowcount; i++) {
            keys.each(function (k) {
                jQuery.each(trs[i].find('*[name*=' + k + ']'), function (key, f) {
                    if ($(this).prop('type') === 'radio') {
                        if (f.value === a[k][i]) {
                            f.checked = true;
                        }
                    } else {
                        // Works for input,select and textareas
                        f.value = a[k][i];
                        if (f.prop('tagName') === 'SELECT') {

                            // Manually fire chosen dropdown update
                            $(f).trigger('liszt:updated');
                        }
                    }
                });
            });
        }
        this.tmpl = tr;
        if (newrow) {
            tr.remove();
        }

    },

    /**
     * Find a table <tr> node above the event.target  node
     * @param {object} e Event
     * @returns {jQuery}
     */
    findTr: function (e) {
        return $(e.target).closest('tr');
    },

    store: function (target) {
        var c = this.el[target], i, n, fields;

        // Get the current values
        var json = {};
        for (i = 0; i < this.names.length; i++) {
            n = this.names[i];
            fields = c.find('*[name*=' + n + ']');
            json[n] = this.getStoreFieldValues(fields);
        }
        // Store them in the parent field.
        this.field[target].value = JSON.encode(json);
        return true;
    },

    getStoreFieldValues: function (fields) {
        var values = [];
        fields.each(function () {
            if ($(this).prop('type') === 'radio') {
                if ($(this).prop('checked') === true) {
                    values.push($(this).val());
                }
            } else {
                values.push($(this).val());
            }
        });
        return values;
    }

});