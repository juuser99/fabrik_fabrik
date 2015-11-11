/**
 * List Inline Edit
 *
 * @copyright: Copyright (C) 2005-2015, fabrikar.com - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

var FbListInlineEdit = my.Class(FbListPlugin, {

    constructor: function (options) {
        var self = this;
        this.parent(options);
        this.defaults = {};
        this.editors = {};
        this.inedit = false;
        this.saving = false;

        // Assigned in list.js fabrik3
        if (typeOf(this.getList().getForm()) === 'null') {
            return false;
        }
        this.listid = this.options.listid;
        this.setUp();

        Fabrik.addEvent('fabrik.list.clearrows', function () {
            this.cancel();
        }.bind(this));

        Fabrik.addEvent('fabrik.list.inlineedit.stopEditing', function () {
            this.stopEditing();
        }.bind(this));

        Fabrik.addEvent('fabrik.list.updaterows', function () {
            this.watchCells();
        }.bind(this));

        Fabrik.addEvent('fabrik.list.ini', function () {
            var table = this.getList();
            var formData = table.form.toQueryString().toObject();
            formData.format = 'raw';
            formData.listref = this.options.ref;
            new Request.JSON({
                'url'        : '',
                data         : formData,
                onComplete   : function () {
                    console.log('complete');
                },
                onSuccess    : function (json) {
                    json = Json.evaluate(json.stripScripts());
                    table.options.data = json.data;
                }.bind(this),
                'onFailure'  : function (xhr) {
                    console.log('ajax inline edit failure', xhr);
                },
                'onException': function (headerName, value) {
                    console.log('ajax inline edit exception', headerName, value);
                }
            }).send();
        }.bind(this));

        // Check for a single element whose click value should trigger the save (ie radio buttons)
        Fabrik.addEvent('fabrik.element.click', function () {
            if (Object.getLength(this.options.elements) === 1 && this.options.showSave === false) {
                this.save(null, this.editing);
            }
        }.bind(this));

        Fabrik.addEvent('fabrik.list.inlineedit.setData', function () {
            if (typeOf(this.editOpts) === 'null') {
                return;
            }
            jQuery.each(this.editOpts.plugins, function (key, fieldid) {
                var e = Fabrik['inlineedit_' + this.editOpts.elid].elements[fieldid];
                delete e.element;
                e.update(this.editData[fieldid]);
                e.select();
            }.bind(this));
            this.watchControls(this.editCell);
            this.setFocus(this.editCell);
        }.bind(this));

        // Click outside list clears down selection
        $(window).on('click', function (e) {
            if (!$(this).hasClass('fabrik_element') && self.td) {
                self.td.removeClass(self.options.focusClass);
                self.td = null;
            }
        });
    },

    setUp: function () {
        var self = this;
        if (typeOf(this.getList().getForm()) === 'null') {
            return;
        }
        this.scrollFx = new Fx.Scroll(window, {
            'wait': false
        });
        this.watchCells();
        $(document).on('keydown', function (e) {
            self.checkKey(e);
        });
    },

    watchCells: function () {
        var firstLoaded = false,
            self = this;
        this.getList().getForm().find('.fabrik_element').each(function (td, x) {

            if (self.canEdit(td)) {
                if (!firstLoaded && self.options.loadFirst) {
                    firstLoaded = self.edit(null, td);
                    if (firstLoaded) {
                        self.select(null, td);
                    }
                }
                if (!self.isEditable(td)) {
                    return;
                }
                self.setCursor(td);
                td.removeEvents();
                td.on(this.options.editEvent, function (e) {
                    self.edit(e, td);
                });
                td.on('click', function (e) {
                    self.select(e, td);
                });

                td.on('mouseenter', function (e) {
                    if (!self.isEditable(td)) {
                        td.css('cursor', 'pointer');
                    }
                });
                td.on('mouseleave', function (e) {
                    td.css('cursor', '');
                });
            }
        });
    },

    checkKey: function (e) {
        var nexttds, row, index;
        if (typeOf(this.td) !== 'element') {
            return;
        }
        switch (e.code) {
            case 39:
                //right
                if (this.inedit) {
                    return;
                }
                if (typeOf(this.td.getNext()) === 'element') {
                    e.stop();
                    this.select(e, this.td.getNext());
                }
                break;
            case 9:
                //tab - don't navigate with tab - moofs form field tab ordering if we do
                if (this.inedit) {
                    if (this.options.tabSave) {
                        if (typeOf(this.editing) === 'element') {
                            this.save(e, this.editing);
                        } else {
                            this.edit(e, this.td);
                        }
                    }
                    return;
                }
                break;
            case 37: //left
                if (this.inedit) {
                    return;
                }
                if (typeOf(this.td.getPrevious()) === 'element') {
                    e.stop();
                    this.select(e, this.td.getPrevious());
                }
                break;
            case 40:
                //down
                if (this.inedit) {
                    return;
                }
                row = this.td.parent();
                if (row.length === 0) {
                    return;
                }
                index = row.find('td').indexOf(this.td);
                if (typeOf(row.getNext()) === 'element') {
                    e.stop();
                    nexttds = row.getNext().find('td');
                    this.select(e, nexttds[index]);
                }
                break;
            case 38:
                //up
                if (this.inedit) {
                    return;
                }
                row = this.td.parent();
                if (row.length === 0) {
                    return;
                }
                index = row.find('td').indexOf(this.td);
                if (typeOf(row.getPrevious()) === 'element') {
                    e.stop();
                    nexttds = row.getPrevious().find('td');
                    this.select(e, nexttds[index]);
                }
                break;
            case 27:
                //escape
                e.stop();
                if (!this.inedit) {
                    this.td.removeClass(this.options.focusClass);
                    this.td = null;
                } else {
                    this.select(e, this.editing);
                    this.cancel(e);
                }

                break;
            case 13:
                //enter

                // Already editing or no cell selected
                if (this.inedit || typeOf(this.td) !== 'element') {
                    return;
                }
                e.stop();
                if (typeOf(this.editing) === 'element') {
                    // stop textarea elements from submitting when you press enter
                    if (this.editors[this.activeElementId].contains('<textarea')) {
                        return;
                    }
                    this.save(e, this.editing);
                } else {
                    this.edit(e, this.td);
                }
                break;
        }
    },

    select: function (e, td) {
        if (!this.isEditable(td)) {
            return;
        }
        var element = this.getElementName(td);
        var opts = this.options.elements[element];
        if (typeOf(opts) === false) {
            return;
        }
        if (typeOf(this.td) === 'element') {
            this.td.removeClass(this.options.focusClass);
        }
        this.td = td;
        if (typeOf(this.td) === 'element') {
            this.td.addClass(this.options.focusClass);
        }
        if (typeOf(this.td) === 'null') {
            return;
        }
        if (e && (e.type !== 'click' && e.type !== 'mouseover')) {
            //if using key nav scroll the cell into view
            var p = this.td.getPosition();
            var x = p.x - (window.getSize().x / 2) - (this.td.getSize().x / 2);
            var y = p.y - (window.getSize().y / 2) + (this.td.getSize().y / 2);
            this.scrollFx.start(x, y);
        }
    },

    /**
     * Parse the td class name to grab the element name
     *
     * @param   DOM node  td  Cell to parse.
     *
     * @return  string  Element name
     */
    getElementName: function (td) {
        var c = td.className.trim().split(' ').filter(function (item, index) {
            return item !== 'fabrik_element' && item !== 'fabrik_row' && !item.contains('hidden');
        });
        var element = c[0].replace('fabrik_row___', '');
        return element;
    },

    setCursor: function (td) {
        var element = this.getElementName(td),
            self = this,
            opts = this.options.elements[element];
        if (typeOf(opts) === 'null') {
            return;
        }
        td.on('mouseover', function (e) {
            if (self.isEditable(e.target)) {
                $(this).css('cursor', 'pointer');
            }
        });
        td.on('mouseleave', function (e) {
            if (self.isEditable(e.target)) {
                $(this).css('cursor', '');
            }
        });
    },

    isEditable: function (cell) {
        if (cell.hasClass('fabrik_uneditable') || cell.hasClass('fabrik_ordercell') || cell.hasClass('fabrik_select') || cell.hasClass('fabrik_actions')) {
            return false;
        }
        var rowid = this.getRowId(cell.closest('.fabrik_row')),
            res = this.getList().firePlugin('onCanEditRow', rowid);
        return res;
    },

    getPreviousEditable: function (active) {
        var found = false;
        var tds = this.getList().getForm().find('.fabrik_element');
        for (var i = tds.length; i >= 0; i--) {
            if (found) {
                if (this.canEdit(tds[i])) {
                    return tds[i];
                }
            }
            if (tds[i] === active) {
                found = true;
            }
        }
        return false;
    },

    getNextEditable: function (active) {
        var found = false;
        var next = this.getList().getForm().find('.fabrik_element').filter(function (td, i) {
            if (found) {
                if (this.canEdit(td)) {
                    found = false;
                    return true;
                }
            }
            if (td === active) {
                found = true;
            }
            return false;
        }.bind(this));
        return next.getLast();
    },

    canEdit: function (td) {
        if (!this.isEditable(td)) {
            return false;
        }
        var element = this.getElementName(td);
        var opts = this.options.elements[element];
        if (typeOf(opts) === 'null') {
            return false;
        }
        return true;
    },

    edit: function (e, td) {
        var self = this;
        if (this.saving) {
            return;
        }
        Fabrik.trigger('fabrik.plugin.inlineedit.editing');

        // Only one field can be edited at a time
        if (this.inedit) {
            // If active event is mouse over - close the current editor
            if (this.options.editEvent === 'mouseover') {
                if (td === this.editing) {
                    return;
                }
                this.select(e, this.editing);
                this.cancel();
            } else {
                return;
            }
        }
        if (!this.canEdit(td)) {
            return false;
        }
        if (typeOf(e) !== 'null') {
            e.stop();
        }
        var element = this.getElementName(td);
        var rowid = this.getRowId(td);
        var opts = this.options.elements[element];
        if (typeOf(opts) === 'null') {
            return;
        }
        this.inedit = true;
        this.editing = td;
        this.activeElementId = opts.elid;
        this.defaults[rowid + '.' + opts.elid] = td.innerHTML;

        var data = this.getDataFromTable(td);

        if (typeOf(this.editors[opts.elid]) === 'null' || typeOf(Fabrik['inlineedit_' + opts.elid]) === 'null') {
            // Need to load on parent otherwise in table td size gets monged
            Fabrik.loader.start(td.parent());
            var inline = this.options.showSave ? 1 : 0;

            $.ajax({
                'evalScripts' : function (script, text) {
                    self.javascript = script;
                },
                'evalResponse': false,
                'url'         : '',
                'data'        : {
                    'element'     : element,
                    'elid'        : opts.elid,
                    'elementid'   : Object.values(opts.plugins),
                    'rowid'       : rowid,
                    'listref'     : this.options.ref,
                    'formid'      : this.options.formid,
                    'listid'      : this.options.listid,
                    'inlinesave'  : inline,
                    'inlinecancel': this.options.showCancel,
                    'option'      : 'com_fabrik',
                    'task'        : 'form.inlineedit',
                    'format'      : 'raw'
                },


            }).fail(function (jqxhr, textStatus, error) {
                self.saving = false;
                self.inedit = false;
                Fabrik.loader.stop(td.parent());
                window.alert(textStatus + ': ' + error);
            }).success(function (r) {
                // Need to load on parent otherwise in table td size gets monged
                Fabrik.loader.stop(td.parent());

                //don't use evalScripts = true as we reuse the js when tabbing to the next element.
                // so instead set evalScripts to a function to store the js in this.javascript.
                //Previously js was wrapped in delay
                //but now we want to use it with and without the delay

                //delay the script to allow time for the dom to be updated
                (function () {
                    Browser.exec(self.javascript);
                    Fabrik.tips.attach('.fabrikTip');
                }).delay(100);
                td.empty().html(r);

                // IE selection wierdness
                self.clearSelection();
                r = r + '<script type="text/javascript">' + self.javascript + '</script>';
                self.editors[opts.elid] = r;
                self.watchControls(td);
                self.setFocus(td);
            });
        } else {

            // Re-use old form
            var html = this.editors[opts.elid].stripScripts(function (script) {
                this.javascript = script;
            }.bind(this));
            td.empty().html(html);

            // Make a new instance of the element js class which will use the new html
            eval(this.javascript);
            this.clearSelection();
            Fabrik.tips.attach('.fabrikTip');

            // Set some options for use in 'fabrik.list.inlineedit.setData'
            this.editOpts = opts;
            this.editData = data;
            this.editCell = td;
        }
        return true;
    },

    clearSelection: function () {
        if (document.selection) {
            document.selection.empty();
        } else {
            window.getSelection().removeAllRanges();
        }
    },

    getDataFromTable: function (td) {
        var groupedData = this.getList().options.data;
        var element = this.getElementName(td);
        var ref = td.closest('.fabrik_row').id;
        var v = {};
        this.vv = [];

        jQuery.each(groupedData, function (key, data) {
            if (typeOf(data) === 'array') {//groued by data in forecasting slotenweb app. Where groupby table plugin applied to data.
                for (var i = 0; i < data.length; i++) {
                    if (data[i].id === ref) {
                        this.vv.push(data[i]);
                    }
                }
            } else {
                var vv = Array.filter(data, function (row) {
                    return row.id === ref;
                });
            }
        }.bind(this));
        var opts = this.options.elements[element];
        if (this.vv.length > 0) {
            jQuery.each(opts.plugins, function (elementName, elid) {
                v[elid] = this.vv[0].data[elementName + '_raw'];
            }.bind(this));
        }
        return v;
    },

    setTableData: function (row, element, val) {
        var ref = row.id;
        var groupedData = this.getList().options.data;

        jQuery.each(groupedData, function (gkey, data) {
            jQuery.each(data, function (dkey, tmpRow) {
                if (tmpRow.id === ref) {
                    tmpRow.data[element + '_raw'] = val;
                    this.currentRow = tmpRow;
                }
            }.bind(this));
        }.bind(this));
    },

    setFocus: function (td) {

        // See http://www.fabrikar.com/forums/index.php?threads/inline-edit-dialog-window-shows-highlight-in-ie.31732/page-2#post-167922
        if (Browser.ie) {
            return;
        }
        var el = td.find('.fabrikinput');
        if (typeOf(el) !== 'null') {
            var fn = function () {
                if (typeOf(el) !== 'null') {
                    el.focus();
                }
            };
            fn.delay(1000);
        }
    },

    watchControls: function (td) {
        var self = this;
        td.find('.inline-save').removeEvents('click').on('click', function (e) {
            self.save(e, td);
        });
        td.find('.inline-cancel').removeEvents('click').on('click', function (e) {
            self.cancel(e, td);
        });
    },

    save: function (e, td) {
        var saveRequest,
            self = this,
            element = this.getElementName(td),
            opts = this.options.elements[element],
            row = this.editing.closest('.fabrik_row'),
            rowid = this.getRowId(row),
            currentRow = {},
            eObj = {},
            data = {};

        if (!this.editing) {
            return;
        }
        this.saving = true;
        this.inedit = false;
        if (e) {
            e.stop();
        }

        eObj = Fabrik['inlineedit_' + opts.elid];
        if (typeOf(eObj) === 'null') {
            fconsole('issue saving from inline edit: eObj not defined');
            this.cancel(e);
            return false;
        }

        // Need to load on parent otherwise in table td size gets monged
        Fabrik.loader.start(td.parent());

        // Set package id to return js string
        data = {
            'option'                 : 'com_fabrik',
            'task'                   : 'form.process',
            'format'                 : 'raw',
            'packageId'              : 1,
            'fabrik_ajax'            : 1,
            'element'                : element,
            'listref'                : this.options.ref,
            'elid'                   : opts.elid,
            'plugin'                 : opts.plugin,
            'rowid'                  : rowid,
            'listid'                 : this.options.listid,
            'formid'                 : this.options.formid,
            'fabrik_ignorevalidation': 1
        };
        data.fabrik_ignorevalidation = 0;
        data.join = {};
        jQuery.each(eObj.elements, function (key, el) {

            el.find();
            var v = el.getValue();
            var jid = el.options.joinId;
            this.setTableData(row, el.options.element, v);
            if (el.options.isJoin) {
                if (typeOf(data.join[jid]) !== 'object') {
                    data.join[jid] = {};
                }
                data.join[jid][el.options.elementName] = v;
            } else {
                data[el.options.element] = v;
            }

        }.bind(this));
        jQuery.each(this.currentRow.data, function (k, v) {
            if (k.substr(k.length - 4, 4) === '_raw') {
                currentRow[k.substr(0, k.length - 4)] = v;
            }
        });
        // Post all the rows data to form.process
        data = Object.append(currentRow, data);
        data[eObj.token] = 1;

        data.toValidate = this.options.elements[data.element].plugins;
        $.ajax({
            url          : '',
            'data'       : data,
            'evalScripts': true
        }).fail(function (xhr, textStatus, error) {
            // Inject error message from header (created by JError::raiseError()...)
            var err = td.find('.inlineedit .fabrikMainError');
            if (err.length === 0) {
                err = $(document.createElement('div')).addClass('fabrikMainError fabrikError alert alert-error');
                err.inject(td.find('form'), 'top');
            }
            self.saving = false;
            Fabrik.loader.stop(td.parent());
            err.html(textStatus);
        }).success(function (r) {
            td.empty();
            td.empty().html(r);

            // Need to load on parent otherwise in table td size gets monged
            Fabrik.loader.stop(td.parent());
            Fabrik.trigger('fabrik.list.updaterows');
            self.stopEditing();
            self.saving = false;
        });
    },

    stopEditing: function (e) {
        var td = this.editing;
        this.editing = null;
        this.inedit = false;
    },

    cancel: function (e) {
        if (e) {
            e.stop();
        }
        var row = this.editing.closest('.fabrik_row'),
            rowId = this.getRowId(row),
            td = this.editing;
        if (td !== false) {
            var element = this.getElementName(td);
            var opts = this.options.elements[element];
            var c = this.defaults[rowId + '.' + opts.elid];
            td.html(c);
        }
        this.stopEditing();
    }
});