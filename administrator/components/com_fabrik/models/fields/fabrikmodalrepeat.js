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
        this.names = names;
        this.field = field;
        this.content = false;
        this.setup = false;
        this.elid = el;
        this.win = {};
        this.el = {};
        this.field = {};
        this.options = $.append(this.options, opts);

        // If the parent field is inserted via js then we delay the loading until the html is present
        if (!this.ready()) {
            this.timer = setInterval(function () {
                this.testReady.call(this, true);
            }, 500);

        } else {
            this.setUp();
        }
    },

    ready: function () {
        return $('#' + this.elid).length > 0;
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
        this.button = $('#' + this.elid + '_button');
        this.mask = new Mask(document.body, {style: {'background-color': '#000', 'opacity': 0.4, 'z-index': 9998}});
        $(document).on('click', '*[data-modal=' + this.elid + ']', function (e) {
            e.preventDefault();
            var tbl,
            // Correct when in repeating group
                id = $(this).next('input').id,
                c = $(this).closest('li');
            this.field[id] = $(this).next('input');
            if (!c) {
                // Joomla 3
                c = $(this).closest('div.control-group');
            }
            this.origContainer = c;
            tbl = c.find('table');
            if (tbl.length > 0) {
                this.el[id] = tbl;
            }
            this.openWindow(id);
        }.bind(this));
    },

    openWindow: function (target) {
        var makeWin = false;
        if (!this.win[target]) {
            makeWin = true;
            this.makeTarget(target);
        }
        this.el[target].inject(this.win[target], 'top');
        this.el[target].show();

        if (!this.win[target] || makeWin) {
            this.makeWin(target);
        }
        this.win[target].show();
        this.win[target].position();
        this.resizeWin(true, target);
        this.win[target].position();
        this.mask.show();
    },

    makeTarget: function (target) {
        this.win[target] = $(document.createElement('div')).attr({
            'data-modal-content': target
        }).css({
            'padding'         : '5px',
            'background-color': '#fff',
            'display'         : 'none',
            'z-index'         : 9999
        }).inject(document.body);

    },

    makeWin: function (target) {
        var self = this;
        var close = $(document.createElement('button')).addClass('btn button btn-primary').text('close');
        close.on('click', function (e) {
            e.stopPropagation();
            self.store(target);
            self.el[target].hide();
            self.el[target].inject(self.origContainer);
            self.close();
        });
        var controls = $(document.createElement('div')).addClass('controls form-actions').css({
            'text-align'   : 'right',
            'margin-bottom': 0
        }).adopt(close);

        this.win[target].adopt(controls);
        this.win[target].position();
        this.content = this.el[target];
        this.build(target);
        this.watchButtons(this.win[target], target);
    },

    resizeWin: function (setup, target) {
        Object.each(this.win, function (win, key) {
            var size = this.el[key].getDimensions(true),
                wsize = win.getDimensions(true);
            win.css({'width': size.x + 'px'});
        }.bind(this));
    },

    close: function () {
        Object.each(this.win, function (win, key) {
            win.hide();
        });
        this.mask.hide();
    },

    _getRadioValues: function (target) {
        var radiovals = [], sel;
        jQuery.each(this.getTrs(target), function (key, tr) {
            var v = (sel = tr.find('input[type=radio]:checked')) ? sel.get('value') : '';
            radiovals.push(v);
        });
        return radiovals;
    },

    _setRadioValues: function (radiovals, target) {
        // Reapply radio button selections
        var r;
        jQuery.each(this.getTrs(target), function (i, tr) {
            if (r = tr.find('input[type=radio][value=' + radiovals[i] + ']')) {
                r.checked = 'checked';
            }
        });
    },

    /**
     * Add a new row of fields
     * @param target
     * @param srouce
     */
    addRow: function (target, source) {
        // Store radio button selections
        var radiovals = this._getRadioValues(target),
            body = source.closest('table').find('tbody'),
            clone = this.tmpl.clone(true, true);
        clone.inject(body);
        this.stripe(target);

        this.fixUniqueAttributes(source, clone);

        // Reapply values as renaming radio buttons
        this._setRadioValues(radiovals, target);
        this.resizeWin(false, target);
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

        jQuery.each(row.find('*[name]'), function (key, node) {
            node.name += '-' + rowCount;
        });
        jQuery.each(row.find('*[id]'), function (key, node) {
            node.id += '-' + rowCount;
        });
        jQuery.each(row.find('label[for]'), function (key, node) {
            node.label += '-' + rowCount;
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
                tr.dispose();
            }
            self.resizeWin(false, target);
            win.position();
            e.stopPropagation();
        });
    },

    resetChosen: function (clone) {
        if ($('select').chosen) {

            // Chosen reset
            clone.find('select').removeClass('chzn-done').show();

            // Assign random id
            $(clone.find('select'), function (key, c) {
                c.id = c.id + '_' + parseInt(Math.random() * 10000000, 10);
            });
            clone.find('.chzn-container').destroy();

            $(clone).find('select').chosen({
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

        var a = JSON.decode(this.field[target].get('value'));
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
                    if (f.get('type') === 'radio') {
                        if (f.value === a[k][i]) {
                            f.checked = true;
                        }
                    } else {
                        // Works for input,select and textareas
                        f.value = a[k][i];
                        if (f.get('tag') === 'select' && typeof jQuery !== 'undefined') {

                            // Manually fire chosen dropdown update
                            jQuery(f).trigger('liszt:updated');
                        }
                    }
                });
            });
        }
        this.tmpl = tr;
        if (newrow) {
            tr.dispose();
        }

    },

    findTr: function (e) {
        var tr = e.target.getParents().filter(function (p) {
            return p.get('tag') === 'tr';
        });
        return (tr.length === 0) ? false : tr[0];
    },

    store: function (target) {
        var c = this.content;
        c = this.el[target];

        // Get the current values
        var json = {};
        for (var i = 0; i < this.names.length; i++) {
            var n = this.names[i];
            var fields = c.find('*[name*=' + n + ']');
            json[n] = [];
            fields.each(function (field) {
                if (field.get('type') === 'radio') {
                    if (field.get('checked') === true) {
                        json[n].push(field.get('value'));
                    }
                } else {
                    json[n].push(field.get('value'));
                }
            }.bind(this));
        }
        // Store them in the parent field.
        this.field[target].value = JSON.encode(json);
        return true;
    }

});