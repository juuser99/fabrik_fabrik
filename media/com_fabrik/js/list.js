/**
 * List
 *
 * @copyright: Copyright (C) 2005-2013, fabrikar.com - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

/* jshint mootools: true */
/*
 * global Fabrik:true, fconsole:true, Joomla:true, CloneObject:true,
 * $H:true,unescape:true,head:true,FbListActions:true,FbGroupedToggler:true,FbListKeys:true
 */

var FbList = my.Class({

    options: {
        'admin'              : false,
        'filterMethod'       : 'onchange',
        'ajax'               : false,
        'ajax_links'         : false,
        'links'              : {'edit': '', 'detail': '', 'add': ''},
        'form'               : 'listform_' + this.id,
        'hightLight'         : '#ccffff',
        'primaryKey'         : '',
        'headings'           : [],
        'labels'             : {},
        'Itemid'             : 0,
        'formid'             : 0,
        'canEdit'            : true,
        'canView'            : true,
        'page'               : 'index.php',
        'actionMethod'       : 'floating', // deprecated in 3.1
        'formels'            : [], // elements that only appear in the form
        'data'               : [], // [{col:val, col:val},...] (depreciated)
        'rowtemplate'        : '',
        'floatPos'           : 'left', // deprecated in 3.1
        'csvChoose'          : false,
        'csvOpts'            : {},
        'popup_width'        : 300,
        'popup_height'       : 300,
        'popup_offset_x'     : null,
        'popup_offset_y'     : null,
        'groupByOpts'        : {},
        'listRef'            : '', // e.g. '1_com_fabrik_1'
        'fabrik_show_in_list': [],
        'singleOrdering'     : false,
        'tmpl'               : '',
        'groupedBy'          : '',
        'toggleCols'         : false
    },

    constructor: function (id, options) {
        this.id = id;
        this.options = $.extend(this.options, options);
        this.getForm();
        this.result = true; //used with plugins to determine if list actions should be performed
        this.plugins = [];
        this.list = $('#list_' + this.options.listRef);

        if (this.options.toggleCols) {
            this.toggleCols = new FbListToggle(this.form);
        }

        this.groupToggle = new FbGroupedToggler(this.form, this.options.groupByOpts);
        new FbListKeys(this);
        if (this.list) {
            if (this.list.get('tag') === 'table') {
                this.tbody = this.list.find('tbody');
            }
            if (typeOf(this.tbody) === 'null') {
                this.tbody = this.list;
            }
            // $$$ rob mootools 1.2 has bug where we cant set('html') on table
            // means that there is an issue if table contains no data
            if (window.ie) {
                this.options.rowtemplate = this.list.find('.fabrik_row');
            }
        }
        this.watchAll(false);
        Fabrik.on('fabrik.form.submitted', function () {
            this.updateRows();
        }.bind(this));

        /**
         * once an ajax form has been submitted lets clear out any loose events and the form object itself
         *
         * Commenting out as this causes issues for cdd after ajax form post
         * http://www.fabrikar.com/forums/index.php?threads/cdd-only-triggers-js-change-code-on-first-change.32793/
         */
        /*Fabrik.on('fabrik.form.ajax.submit.end', function (form) {
         form.formElements.each(function (el) {
         el.removeCustomEvents();
         });
         delete Fabrik.blocks['form_' + form.id];
         });*/

        // Reload state
        if (!!(window.history && history.pushState) && history.state && this.options.ajax) {
            this._updateRows(history.state);
        }
    },

    setRowTemplate: function () {
        // $$$ rob mootools 1.2 has bug where we cant setHTML on table
        // means that there is an issue if table contains no data
        if (typeOf(this.options.rowtemplate) === 'string') {
            var r = this.list.find('.fabrik_row');
            if (window.ie && typeOf(r) !== 'null') {
                this.options.rowtemplate = r;
            }
        }
    },

    /**
     * Used for db join select states.
     */
    rowClicks: function () {
        this.list.on('click:relay(.fabrik_row)', function (e, r) {
            var d = Array.from(r.id.split('_')),
                data = {};
            data.rowid = d.getLast();
            var json = {
                'errors': {},
                'data'  : data,
                'rowid' : d.getLast(),
                listid  : this.id
            };
            Fabrik.trigger('fabrik.list.row.selected', json);
        }.bind(this));
    },

    watchAll: function (ajaxUpdate) {
        ajaxUpdate = ajaxUpdate ? ajaxUpdate : false;
        this.watchNav();
        this.storeCurrentValue();
        if (!ajaxUpdate) {
            this.watchRows();
            this.watchFilters();
        }
        this.watchOrder();
        this.watchEmpty();
        if (!ajaxUpdate) {
            this.watchGroupByMenu();
            this.watchButtons();
        }
    },

    watchGroupByMenu: function () {
        if (this.options.ajax) {
            this.form.on('click:relay(*[data-groupBy])', function (e, target) {
                this.options.groupedBy = target.get('data-groupBy');
                if (e.rightClick) {
                    return;
                }
                e.preventDefault();
                this.updateRows();
            }.bind(this));
        }
    },

    watchButtons: function () {
        var self = this;
        this.exportWindowOpts = {
            id         : 'exportcsv',
            title      : 'Export CSV',
            loadMethod : 'html',
            minimizable: false,
            width      : 360,
            height     : 120,
            content    : '',
            bootstrap  : true
        };
        if (this.options.view === 'csv') {

            // For csv links e.g. index.php?option=com_fabrik&view=csv&listid=10
            this.openCSVWindow();
        } else {
            if (this.form.find('.csvExportButton')) {
                this.form.find('.csvExportButton').each(function (b) {
                    if (b.hasClass('custom') === false) {
                        b.on('click', function (e) {
                            self.openCSVWindow();
                            e.stopPropagation();
                        });
                    }
                });
            }
        }
    },

    openCSVWindow: function () {
        var thisc = this.makeCSVExportForm();
        this.exportWindowOpts.content = thisc;
        this.exportWindowOpts.onContentLoaded = function () {
            this.fitToContent(false);
        };
        this.csvWindow = Fabrik.getWindow(this.exportWindowOpts);
    },

    makeCSVExportForm: function () {
        if (this.options.csvChoose) {
            return this._csvExportForm();
        } else {
            return this._csvAutoStart();
        }
    },

    _csvAutoStart: function () {
        var c = $('<div />').attr({
            'id': 'csvmsg'
        }).html(Joomla.JText._('COM_FABRIK_LOADING') +
            ' <br /><span id="csvcount">0</span> / <span id="csvtotal"></span> ' +
            Joomla.JText._('COM_FABRIK_RECORDS') + '.<br/>' + Joomla.JText._('COM_FABRIK_SAVING_TO') +
            '<span id="csvfile"></span>');

        this.csvopts = this.options.csvOpts;
        this.csvfields = this.options.csvFields;

        this.triggerCSVExport(-1);
        return c;
    },

    _csvExportForm: function () {
        var yes = Joomla.JText._('JYES');
        // Can't build via dom as ie7 doesn't accept checked status
        var rad = '<input type="radio" value="1" name="incfilters" checked="checked" />' + yes;
        var rad2 = '<input type="radio" value="1" name="incraw" checked="checked" />' + yes;
        var rad3 = '<input type="radio" value="1" name="inccalcs" checked="checked" />' + yes;
        var rad4 = '<input type="radio" value="1" name="inctabledata" checked="checked" />' + yes;
        var rad5 = '<input type="radio" value="1" name="excel" checked="checked" />Excel CSV';
        var url = 'index.php?option=com_fabrik&view=list&listid=' +
            this.id + '&format=csv&Itemid=' + this.options.Itemid;

        var divopts = {
            'styles': {
                'width': '200px',
                'float': 'left'
            }
        }, no = Joomla.JText._('JNO');
        var c = $('<form />').attr({
            'action': url,
            'method': 'post'
        }).adopt([$('<div />').attr(divopts).text(Joomla.JText._('COM_FABRIK_FILE_TYPE')), $('<label />').html(rad5),
            $('<label />').adopt(
                [$('<input />').attr({
                    'type' : 'radio',
                    'name' : 'excel',
                    'value': '0'
                }),
                    $('<span />').text('CSV')
                ]),
            $('<br />'), $('<br />'),
            $('<div />').attr(divopts).text(Joomla.JText._('COM_FABRIK_INCLUDE_FILTERS')),
            $('<label />').html(rad),
            $('<label />').adopt([$('<input />').attr({
                'type' : 'radio',
                'name' : 'incfilters',
                'value': '0'
            }), $('<span />').text(no)]), $('<br />'),
            $('<div />').attr(divopts).text(Joomla.JText._('COM_FABRIK_INCLUDE_DATA')),
            $('<label />').html(rad4),
            $('<label />').adopt([$('<input />').attr({
                'type' : 'radio',
                'name' : 'inctabledata',
                'value': '0'
            }), $('<span />').text(no)]), $('<br />'),
            $('<div />').attr(divopts).text(Joomla.JText._('COM_FABRIK_INCLUDE_RAW_DATA')),
            $('<label />').html(rad2), $('<label />').adopt([$('<input />').attr({
                'type' : 'radio',
                'name' : 'incraw',
                'value': '0'
            }), $('<span />').text(no)]), $('<br />'),
            $('<div />').attr(divopts).text(Joomla.JText._('COM_FABRIK_INCLUDE_CALCULATIONS')),
            $('<label />').html(rad3), $('<label />').adopt([$('<input />').attr({
                'type' : 'radio',
                'name' : 'inccalcs',
                'value': '0'
            }), $('<span />').text(no)])]);
        $('<h4 />').text(Joomla.JText._('COM_FABRIK_SELECT_COLUMNS_TO_EXPORT')).inject(c);
        var g = '';
        var i = 0;
        jQuery.each(this.options.labels, function (k, label) {
            if (k.substr(0, 7) !== 'fabrik_' && k !== '____form_heading') {
                var newg = k.split('___')[0];
                if (newg !== g) {
                    g = newg;
                    $('<h5 />').text(g).inject(c);
                }
                var rad = '<input type="radio" value="1" name="fields[' + k + ']" checked="checked" />' +
                    yes;
                label = label.replace(/<\/?[^>]+(>|$)/g, '');
                var r = $('<div />').attr(divopts).text(label);
                r.inject(c);
                $('<label />').html(rad).inject(c);
                $('<label />').adopt([$('<input />').attr({
                    'type' : 'radio',
                    'name' : 'fields[' + k + ']',
                    'value': '0'
                }), $('<span />').text(Joomla.JText._('JNO'))]).inject(c);
                $('<br />').inject(c);
            }
            i++;
        }.bind(this));

        // elements not shown in table
        if (this.options.formels.length > 0) {
            $('<h5 />').text(Joomla.JText._('COM_FABRIK_FORM_FIELDS')).inject(c);
            this.options.formels.each(function (el) {
                var rad = '<input type="radio" value="1" name="fields[' + el.name + ']" checked="checked" />' +
                    yes;
                var r = $('<div />').attr(divopts).text(el.label);
                r.inject(c);
                $('<label />').html(rad).inject(c);
                $('<label />').adopt([$('<input />').attr({
                    'type' : 'radio',
                    'name' : 'fields[' + el.name + ']',
                    'value': '0'
                }), $('<span />').text(Joomla.JText._('JNO'))]).inject(c);
                $('<br />').inject(c);
            }.bind(this));
        }

        $('<div />').css({
            'text-align': 'right'
        }).adopt($('<input />').attr({
            'type' : 'button',
            'name' : 'submit',
            'value': Joomla.JText._('COM_FABRIK_EXPORT'),
            'class': 'button exportCSVButton',
            events : {
                'click': function (e) {
                    e.stopPropagation();
                    e.target.disabled = true;
                    var csvMsg = $('#csvmsg');
                    if (csvMsg.length === 0) {
                        csvMsg = $('<div />').attr({
                            'id': 'csvmsg'
                        }).inject(e.target, 'before');
                    }
                    csvMsg.html(Joomla.JText._('COM_FABRIK_LOADING') +
                        ' <br /><span id="csvcount">0</span> / <span id="csvtotal"></span> ' +
                        Joomla.JText._('COM_FABRIK_RECORDS') + '.<br/>' + Joomla.JText._('COM_FABRIK_SAVING_TO') +
                        '<span id="csvfile"></span>');
                    this.triggerCSVExport(0);
                }.bind(this)

            }
        })).inject(c);
        $('<input />').attr({
            'type' : 'hidden',
            'name' : 'view',
            'value': 'table'
        }).inject(c);
        $('<input />').attr({
            'type' : 'hidden',
            'name' : 'option',
            'value': 'com_fabrik'
        }).inject(c);
        $('<input />').attr({
            'type' : 'hidden',
            'name' : 'listid',
            'value': this.id
        }).inject(c);
        $('<input />').attr({
            'type' : 'hidden',
            'name' : 'format',
            'value': 'csv'
        }).inject(c);
        $('<input />').attr({
            'type' : 'hidden',
            'name' : 'c',
            'value': 'table'
        }).inject(c);
        return c;
    },

    triggerCSVExport: function (start, opts, fields) {
        var self = this;
        if (start !== 0) {
            if (start === -1) {
                // not triggered from front end selections
                start = 0;
                opts = this.csvopts;
                opts.fields = this.csvfields;
            } else {
                opts = this.csvopts;
                fields = this.csvfields;
            }
        } else {
            if (!opts) {
                opts = {};
                ['incfilters', 'inctabledata', 'incraw', 'inccalcs', 'excel'].each(function (v) {
                    var inputs = $('#exportcsv').find('input[name=' + v + ']');
                    if (inputs.length > 0) {
                        opts[v] = inputs.filter(function (i) {
                            return i.checked;
                        })[0].value;
                    }
                });
            }
            // selected fields
            if (!fields) {
                fields = {};
                $('#exportcsv').find('input[name^=field]').each(function (i) {
                    if (i.checked) {
                        var k = i.name.replace('fields[', '').replace(']', '');
                        fields[k] = i.val();
                    }
                });
            }
            opts.fields = fields;
            this.csvopts = opts;
            this.csvfields = fields;
        }

        opts = this.csvExportFilterOpts(opts);

        opts.start = start;
        opts.option = 'com_fabrik';
        opts.view = 'list';
        opts.format = 'csv';
        opts.Itemid = this.options.Itemid;
        opts.listid = this.id;
        opts.listref = this.options.listRef;
        opts.download = 0;
        opts.setListRefFromRequest = 1;

        this.options.csvOpts.custom_qs.split('&').each(function (qs) {
            var key = qs.split('=');
            opts[key[0]] = key[1];
        });

        // Append the custom_qs to the URL to enable querystring filtering of the list data
        var myAjax = new Request.JSON({
            url       : '?' + this.options.csvOpts.custom_qs,
            method    : 'post',
            data      : opts,
            onError   : function (text, error) {
                fconsole(text, error);
            },
            onComplete: function (res) {
                if (res.err) {
                    window.alert(res.err);
                    Fabrik.Windows.exportcsv.close();
                } else {
                    $('#csvcount').text(res.count);
                    $('#csvtotal').text(res.total);
                    $('#csvfile').text(res.file);
                    if (res.count < res.total) {
                        this.triggerCSVExport(res.count);
                    } else {
                        var finalurl = 'index.php?option=com_fabrik&view=list&format=csv&listid=' + self.id + '&start=' + res.count + '&Itemid=' + self.options.Itemid;
                        var msg = '<div class="alert alert-success"><h3>' + Joomla.JText._('COM_FABRIK_CSV_COMPLETE');
                        msg += '</h3><p><a class="btn btn-success" href="' + finalurl + '"><i class="icon-download"></i> ' + Joomla.JText._('COM_FABRIK_CSV_DOWNLOAD_HERE') + '</a></p></div>';
                        $('#csvmsg').html(msg);
                        self.csvWindow.fitToContent(false);
                        $('input.exportCSVButton').removeProperty('disabled');
                    }
                }
            }
        });
        myAjax.send();
    },

    /**
     * Add filter options to CSV export info
     *
     * @param   objet  opts
     *
     * @return  opts
     */
    csvExportFilterOpts: function (opts) {
        var ii = 0,
            aa, bits,
            advancedPointer = 0,
            testii,
            usedAdvancedKeys = ['value', 'condition', 'join', 'key', 'search_type', 'match', 'full_words_only', 'eval', 'grouped_to_previous', 'hidden', 'elementid'];

        this.getFilters().each(function (f) {
            bits = f.name.split('[');
            if (bits.length > 3) {
                testii = parseInt(bits[3].replace(']', ''), 10);
                ii = testii > ii ? testii : ii;

                if (f.get('type') === 'checkbox' || f.get('type') === 'radio') {
                    if (f.checked) {
                        opts[f.name] = f.val();
                    }
                } else {
                    opts[f.name] = f.val();
                }
            }
        }.bind(this));

        ii++;

        Object.each(this.options.advancedFilters, function (values, key) {
            if (usedAdvancedKeys.contains(key)) {
                advancedPointer = 0;
                for (aa = 0; aa < values.length; aa++) {
                    advancedPointer = aa + ii;
                    aName = 'fabrik___filter[list_' + this.options.listRef + '][' + key + '][' + advancedPointer + ']';
                    opts[aName] = values[aa];
                }
            }
        }.bind(this));

        return opts;
    },

    addPlugins: function (a) {
        a.each(function (p) {
            p.list = this;
        }.bind(this));
        this.plugins = a;
    },

    firePlugin: function (method) {
        var args = Array.prototype.slice.call(arguments),
            self = this;
        args = args.slice(1, args.length);
        this.plugins.each(function (plugin) {
            Fabrik.trigger(method, [self, args]);
        });
        return this.result === false ? false : true;
    },

    watchEmpty: function (e) {
        var b = $('#' + this.options.form).find('.doempty'),
            self = this;
        b.on('click', function (e) {
            e.stopPropagation();
            if (window.confirm(Joomla.JText._('COM_FABRIK_CONFIRM_DROP'))) {
                self.submit('list.doempty');
            }
        });
    },

    watchOrder: function () {
        var elementId = false,
            hs = $('#' + this.options.form).find('.fabrikorder, .fabrikorder-asc, .fabrikorder-desc');
        hs.removeEvents('click');
        hs.each(function (h) {
            h.on('click', function (e) {
                var img = 'ordernone.png',
                    orderdir = '',
                    newOrderClass = '',
                    bsClassAdd = '',
                    bsClassRemove = '';
                // $$$ rob in pageadaycalendar.com h was null so reset to e.target
                h = $(e.target);
                var td = $(this).closest('.fabrik_ordercell');
                if ($(this).prop('tagName') !== 'A') {
                    h = td.closest('a');
                }

                /**
                 * Figure out what we need to change the icon from / to.  We don't know in advance for
                 * bootstrapped templates what icons will be used, so the fabrik-order-header layout
                 * will have set data-sort-foo properties of each of the three states.  Another wrinkle
                 * is that we can't just set the new icon class blindly, because there may be other classes
                 * on the icon.  For instancee BS3 using Font Awesome will have "fa fa-sort-foo".  So we have
                 * to specifically remove the current class and add the new one.
                 */

                switch (h.className) {
                    case 'fabrikorder-asc':
                        newOrderClass = 'fabrikorder-desc';
                        bsClassAdd = h.get('data-sort-desc-icon');
                        bsClassRemove = h.get('data-sort-asc-icon');
                        orderdir = 'desc';
                        img = 'orderdesc.png';
                        break;
                    case 'fabrikorder-desc':
                        newOrderClass = 'fabrikorder';
                        bsClassAdd = h.get('data-sort-icon');
                        bsClassRemove = h.get('data-sort-desc-icon');
                        orderdir = '-';
                        img = 'ordernone.png';
                        break;
                    case 'fabrikorder':
                        newOrderClass = 'fabrikorder-asc';
                        bsClassAdd = h.get('data-sort-asc-icon');
                        bsClassRemove = h.get('data-sort-icon');
                        orderdir = 'asc';
                        img = 'orderasc.png';
                        break;
                }
                td.className.split(' ').each(function (c) {
                    if (c.contains('_order')) {
                        elementId = c.replace('_order', '').replace(/^\s+/g, '').replace(/\s+$/g, '');
                    }
                });
                if (!elementId) {
                    fconsole('woops didnt find the element id, cant order');
                    return;
                }
                h.className = newOrderClass;
                var i = h.find('img');
                var icon = h.firstElementChild;

                // Swap images - if list doing ajax nav then we need to do this
                if (this.options.singleOrdering) {
                    $('#' + this.options.form).find('.fabrikorder, .fabrikorder-asc, .fabrikorder-desc').each(function (otherH) {
                        var otherIcon = otherH.firstElementChild;
                        switch (otherH.className) {
                            case 'fabrikorder-asc':
                                otherIcon.removeClass(otherH.get('data-sort-asc-icon'));
                                otherIcon.addClass(otherH.get('data-sort-icon'));
                                break;
                            case 'fabrikorder-desc':
                                otherIcon.removeClass(otherH.get('data-sort-desc-icon'));
                                otherIcon.addClass(otherH.get('data-sort-icon'));
                                break;
                            case 'fabrikorder':
                                break;
                        }

                    });
                }

                icon.removeClass(bsClassRemove);
                icon.addClass(bsClassAdd);
                this.fabrikNavOrder(elementId, orderdir);
                e.stopPropagation();
            }.bind(this));
        }.bind(this));

    },

    getFilters: function () {
        return $('#' + this.options.form).find('.fabrik_filter');
    },

    storeCurrentValue: function () {
        this.getFilters().each(function (f) {
            if (this.options.filterMethod !== 'submitform') {
                f.store('initialvalue', f.val());
            }
        }.bind(this));
    },

    watchFilters: function () {
        var e = '',
            self = this,
            submit = $('#' + this.options.form).find('.fabrik_filter_submit');
        this.getFilters().each(function (f) {
            e = f.get('tag') === 'select' ? 'change' : 'blur';
            if (self.options.filterMethod !== 'submitform') {
                f.removeEvent(e);
                f.on(e, function (e) {
                    e.stopPropagation();
                    if ($(e.target).data('initialvalue') !== $(e.target).val()) {
                        self.doFilter();
                    }
                });
            } else {
                f.on(e, function (e) {
                    submit.highlight('#ffaa00');
                });
            }
        });

        // Watch submit if present regardless of this.options.filterMethod
        if (submit) {
            submit.removeEvents();
            submit.on('click', function (e) {
                e.stopPropagation();
                this.doFilter();
            }.bind(this));
        }
        this.getFilters().on('keydown', function (e) {
            if (e.code === 13) {
                e.stopPropagation();
                this.doFilter();
            }
        }.bind(this));
    },

    doFilter: function () {
        var res = Fabrik.trigger('list.filter', [this]).eventResults;
        if (typeOf(res) === 'null') {
            this.submit('list.filter');
        }
        if (res.length === 0 || !res.contains(false)) {
            this.submit('list.filter');
        }
    },

    // highlight active row, deselect others
    setActive: function (activeTr) {
        this.list.find('.fabrik_row').each(function (tr) {
            tr.removeClass('activeRow');
        });
        activeTr.addClass('activeRow');
    },

    getActiveRow: function (e) {
        var row = $(e.target).closest('.fabrik_row');
        if (!row) {
            row = Fabrik.activeRow;
        }
        return row;
    },

    watchRows: function () {
        if (!this.list) {
            return;
        }
        this.rowClicks();
    },

    getForm: function () {
        if (!this.form) {
            this.form = $('#' + this.options.form);
        }
        return this.form;
    },

    uncheckAll: function () {
        this.form.find('input[name^=ids]').each(function (c) {
            c.checked = '';
        });
    },

    submit: function (task) {
        this.getForm();
        var doAJAX = this.options.ajax, self = this;
        if (task === 'list.doPlugin.noAJAX') {
            task = 'list.doPlugin';
            doAJAX = false;
        }
        if (task === 'list.delete') {
            var ok = false;
            var delCount = 0;
            this.form.find('input[name^=ids]').each(function (c) {
                if (c.checked) {
                    delCount++;
                    ok = true;
                }
            });
            if (!ok) {
                alert(Joomla.JText._('COM_FABRIK_SELECT_ROWS_FOR_DELETION'));
                Fabrik.loader.stop('listform_' + this.options.listRef);
                return false;
            }
            var delMsg = delCount === 1 ? Joomla.JText._('COM_FABRIK_CONFIRM_DELETE_1') : Joomla.JText._('COM_FABRIK_CONFIRM_DELETE').replace('%s', delCount);
            if (!confirm(delMsg)) {
                Fabrik.loader.stop('listform_' + this.options.listRef);
                this.uncheckAll();
                return false;
            }
        }
        // We may want to set this as an option - if long page loads feedback that list is doing something might be useful
        // Fabrik.loader.start('listform_' + this.options.listRef);
        if (task === 'list.filter') {
            Fabrik['filter_listform_' + this.options.listRef].onSubmit();
            this.form.task.value = task;
            if (this.form['limitstart' + this.id]) {
                this.form.find('#limitstart' + this.id).value = 0;
            }
        } else {
            if (task !== '') {
                this.form.task.value = task;
            }
        }
        if (doAJAX) {
            Fabrik.loader.start('listform_' + this.options.listRef);
            // For module & mambot
            // $$$ rob with modules only set view/option if ajax on
            this.form.find('input[name=option]').value = 'com_fabrik';
            this.form.find('input[name=view]').value = 'list';
            this.form.find('input[name=format]').value = 'raw';

            var data = this.form.toQueryString();
            if (task === 'list.filter' && this.advancedSearch !== false) {
                var advSearchForm = document.find('form.advancedSeach_' + this.options.listRef);
                if (typeOf(advSearchForm) !== 'null') {
                    data += '&' + advSearchForm.toQueryString();
                    data += '&replacefilters=1';
                }
            }
            // Pass the elements that are shown in the list - to ensure they are formatted
            for (var i = 0; i < this.options.fabrik_show_in_list.length; i++) {
                data += '&fabrik_show_in_list[]=' + this.options.fabrik_show_in_list[i];
            }

            // Add in tmpl for custom nav in admin
            data += '&tmpl=' + this.options.tmpl;
            this.request = $.getJSON({
                'url' : this.form.get('action'),
                'data': data,
            }).done(function (json) {
                self._updateRows(json);
                Fabrik.loader.stop('listform_' + self.options.listRef);
                Fabrik['filter_listform_' + self.options.listRef].onUpdateData();
                Fabrik.trigger('fabrik.list.submit.ajax.complete', [self, json]);
                if (json.msg) {
                    window.alert(json.msg);
                }
            });

            if (window.history && window.history.pushState) {
                history.pushState(data, 'fabrik.list.submit');
            }
            Fabrik.trigger('fabrik.list.submit', [task, this.form.toQueryString().toObject()]);
        } else {
            this.form.submit();
        }
        //Fabrik['filter_listform_' + this.options.listRef].onUpdateData();
        return false;
    },

    fabrikNav: function (limitStart) {
        this.options.limitStart = limitStart;
        this.form.find('#limitstart' + this.id).value = limitStart;
        // cant do filter as that resets limitstart to 0
        Fabrik.trigger('fabrik.list.navigate', [this, limitStart]);
        if (!this.result) {
            this.result = true;
            return false;
        }
        this.submit('list.view');
        return false;
    },

    /**
     * Get the primary keys for the visible rows
     *
     * @since   3.0.7
     *
     * @return  array
     */
    getRowIds: function () {
        var keys = [];
        jQuery.each(this.options.data, function (k, group) {
            group.each(function (row) {
                keys.push(row.data.__pk_val);
            });
        });
        return keys;
    },

    /**
     * Get a single row's data
     *
     * @param   string  id  ID
     *
     * @since  3.0.8
     *
     * @return object
     */
    getRow: function (id) {
        var found = {};
        jQuery.each(this.options.data, function (key, group) {
            for (var i = 0; i < group.length; i++) {
                var row = group[i];
                if (row && row.data.__pk_val === id) {
                    found = row.data;
                }
            }
        });
        return found;
    },

    fabrikNavOrder: function (orderby, orderdir) {
        this.form.orderby.value = orderby;
        this.form.orderdir.value = orderdir;
        Fabrik.trigger('fabrik.list.order', [this, orderby, orderdir]);
        if (!this.result) {
            this.result = true;
            return false;
        }
        this.submit('list.order');
    },

    removeRows: function (rowids) {
        var i,
            self = this;
        // @TODO: try to do this with FX.Elements
        for (i = 0; i < rowids.length; i++) {
            var row = $('#list_' + this.id + '_row_' + rowids[i]);
            var highlight = new Fx.Morph(row, {
                duration: 1000
            });
            highlight.start({
                'backgroundColor': this.options.hightLight
            }).chain(function () {
                this.start({
                    'opacity': 0
                });
            }).chain(function () {
                row.dispose();
                self.checkEmpty();
            });
        }
    },

    editRow: function () {
    },

    clearRows: function () {
        this.list.find('.fabrik_row').each(function (tr) {
            tr.dispose();
        });
    },

    updateRows: function () {
        var data = {
            'option'  : 'com_fabrik',
            'view'    : 'list',
            'task'    : 'list.view',
            'format'  : 'raw',
            'listid'  : this.id,
            'group_by': this.options.groupedBy,
            'listref' : this.options.listRef
        };
        var url = '';
        data['limit' + this.id] = this.options.limitLength;
        new Request({
            'url'        : url,
            'data'       : data,
            'evalScripts': false,
            onSuccess    : function (json) {
                json = json.stripScripts();
                json = JSON.decode(json);
                this._updateRows(json);
                // Fabrik.trigger('fabrik.list.update', [this, json]);
            }.bind(this),
            onError      : function (text, error) {
                fconsole(text, error);
            },
            onFailure    : function (xhr) {
                fconsole(xhr);
            }
        }).send();
    },

    _updateRows: function (data) {
        var tbody, trs, groupHeading, i, j;
        if (typeOf(data) !== 'object') {
            return;
        }
        if (window.history && window.history.pushState) {
            history.pushState(data, 'fabrik.list.rows');
        }
        if (data.id === this.id && data.model === 'list') {
            var header = $('#' + this.options.form).find('.fabrik___heading').getLast();
            var headings = new Hash(data.headings);
            headings.each(function (data, key) {
                key = '.' + key;
                try {
                    if (typeOf(header.find(key)) !== 'null') {
                        // $$$ rob 28/10/2011 just alter span to allow for maintaining filter toggle links
                        header.find(key).find('span').html(data);
                    }
                } catch (err) {
                    fconsole(err);
                }
            });
            this.setRowTemplate();
            this.clearRows();
            var counter = 0;
            var rowcounter = 0;
            trs = [];
            this.options.data = data.data;

            if (data.calculations) {
                this.updateCals(data.calculations);
            }
            this.form.find('.fabrikNav').html(data.htmlnav);
            // $$$ rob was $H(data.data) but that wasnt working ????
            // testing with $H back in again for grouped by data? Yeah works for
            // grouped data!!
            var gdata = data.data;
            var gcounter = 0;
            jQuery.each(gdata, function (groupKey, groupData) {
                var container, thisrowtemplate;
                tbody = this.options.isGrouped ? this.list.find('.fabrik_groupdata')[gcounter] : this.tbody;

                // Set the group by heading
                if (this.options.isGrouped && tbody) {
                    groupHeading = tbody.getPrevious();
                    groupHeading.find('.groupTitle').html(groupData[0].groupHeading);
                }
                if (typeOf(tbody) !== 'null') {
                    gcounter++;
                    for (i = 0; i < groupData.length; i++) {

                        if (typeof(this.options.rowtemplate) === 'string') {
                            container = (this.options.rowtemplate.trim().slice(0, 3) === '<tr') ? '<table />' : '<div />';
                            thisrowtemplate = $(container);
                            thisrowtemplate.html(this.options.rowtemplate);
                        } else {
                            container = this.options.rowtemplate.get('tag') === 'tr' ? '<table />' : '<div />';
                            thisrowtemplate = $(container);
                            // ie tmp fix for mt 1.2 setHTML on table issue
                            thisrowtemplate.adopt(this.options.rowtemplate.clone());
                        }
                        var row = groupData[i];
                        jQuery.each(row.data, function (key, val) {
                            var rowk = '.' + key;
                            var cell = thisrowtemplate.find(rowk);
                            if (cell.prop('tagName') !== 'A') {
                                cell.html(val);
                            }
                            rowcounter++;
                        });

                        thisrowtemplate.find('.fabrik_row').id = row.id;
                        if (typeof(this.options.rowtemplate) === 'string') {
                            var c = thisrowtemplate.find('.fabrik_row').clone();
                            c.prop('id', row.id);
                            var newClass = row['class'].split(' ');
                            for (j = 0; j < newClass.length; j++) {
                                c.addClass(newClass[j]);
                            }
                            c.inject(tbody);
                        } else {
                            var r = thisrowtemplate.find('.fabrik_row');
                            r.inject(tbody);
                            thisrowtemplate.empty();
                        }
                        counter++;
                    }
                }
            }.bind(this));

            // Grouped data - show all tbodys, then hide empty tbodys (not going to work for none <table> tpls)
            var tbodys = this.list.find('tbody');
            tbodys.css('display', '');
            tbodys.each(function (tbody) {
                if (!tbody.hasClass('fabrik_groupdata')) {
                    var groupTbody = tbody.getNext();
                    if (groupTbody.find('.fabrik_row').length === 0) {
                        tbody.hide();
                        groupTbody.hide();
                    }
                }
            });

            var fabrikDataContainer = this.list.closest('.fabrikDataContainer');
            var emptyDataMessage = this.list.closest('.fabrikForm').find('.emptyDataMessage');
            if (rowcounter === 0) {
                emptyDataMessage.css('display', '');
                /*
                 * $$$ hugh - when doing JSON updates, the emptyDataMessage can be in a td (with no class or id)
                 * which itself is hidden, and also have a child div with the .emptyDataMessage
                 * class which is also hidden.  Should probably move all this logic into a function
                 * but for now just doing it here.
                 */
                if (emptyDataMessage.parent().css('display') === 'none') {
                    emptyDataMessage.parent().css('display', '');
                }
                emptyDataMessage.find('.emptyDataMessage').css('display', '');
            } else {
                fabrikDataContainer.css('display', '');
                emptyDataMessage.css('display', 'none');
            }

            this.form.find('.fabrikNav').html(data.htmlnav);
            this.watchAll(true);
            Fabrik.trigger('fabrik.list.updaterows');
            Fabrik.trigger('fabrik.list.update', [this, data]);
        }
        this.stripe();
        this.mediaScan();
        Fabrik.loader.stop('listform_' + this.options.listRef);
    },

    mediaScan: function () {
        if (typeof(Slimbox) !== 'undefined') {
            Slimbox.scanPage();
        }
        if (typeof(Lightbox) !== 'undefined') {
            Lightbox.init();
        }
        if (typeof(Mediabox) !== 'undefined') {
            Mediabox.scanPage();
        }
    },

    addRow: function (obj) {
        var r = $('<tr />').addClass('oddRow1');

        for (var i in obj) {
            if (this.options.headings.indexOf(i) !== -1) {
                var td = $('<td />').text(obj[i]);
                r.appendChild(td);
            }
        }
        r.inject(this.tbody);
    },

    addRows: function (aData) {
        var i, j;
        for (i = 0; i < aData.length; i++) {
            for (j = 0; j < aData[i].length; j++) {
                this.addRow(aData[i][j]);
            }
        }
        this.stripe();
    },

    stripe: function () {
        var trs = this.list.find('.fabrik_row'), i;
        for (i = 0; i < trs.length; i++) {
            if (!trs[i].hasClass('fabrik___header')) { // ignore heading
                var row = 'oddRow' + (i % 2);
                trs[i].addClass(row);
            }
        }
    },

    checkEmpty: function () {
        var trs = this.list.find('tr');
        if (trs.length === 2) {
            this.addRow({
                'label': Joomla.JText._('COM_FABRIK_NO_RECORDS')
            });
        }
    },

    watchCheckAll: function (e) {
        var checkAll = this.form.find('input[name=checkAll]'), c,
            self = this;
        if (typeOf(checkAll) !== 'null') {
            // IE wont fire an event on change until the checkbxo is blurred!
            checkAll.on('click', function (e) {
                var p = this.list.closest('.fabrikList').length > 0 ? this.list.closest('.fabrikList') : this.list;
                var chkBoxes = p.find('input[name^=ids]');
                c = !e.target.checked ? '' : 'checked';
                for (var i = 0; i < chkBoxes.length; i++) {
                    chkBoxes[i].checked = c;
                    this.toggleJoinKeysChx(chkBoxes[i]);
                }
                // event.stopPropagation(); dont event stop as this stops the checkbox being
                // selected
            }.bind(this));
        }
        this.form.find('input[name^=ids]').each(function (i) {
            var input = $(this);
            input.on('change', function (e) {
                self.toggleJoinKeysChx(input);
            });
        });
    },

    /**
     *
     * @param {jQuery} i
     */
    toggleJoinKeysChx: function (i) {
        i.parent().find('input[class=fabrik_joinedkey]').each(function () {
            this.checked = i.checked;
        });
    },

    watchNav: function (e) {
        var limitBox = null, addRecord = null,
            self = this;
        if (this.form !== null) {
            limitBox = this.form.find('select[name*=limit]');
            addRecord = this.form.find('.addRecord');
        }
        if (limitBox !== null) {
            limitBox.on('change', function (e) {
                var res = Fabrik.trigger('fabrik.list.limit', [self]);
                if (self.result === false) {
                    self.result = true;
                    return false;
                }
                self.doFilter();
            });
        }
        if (addRecord !== null && (this.options.ajax_links)) {
            addRecord.removeEvents();
            var loadMethod = (this.options.links.add === '' || addRecord.href.contains(Fabrik.liveSite)) ? 'xhr' : 'iframe';
            var url = addRecord.href;
            url += url.contains('?') ? '&' : '?';
            url += 'tmpl=component&ajax=1';
            addRecord.on('click', function (e) {
                e.stopPropagation();
                // top.Fabrik.trigger('fabrik.list.add', this);//for packages?
                var winOpts = {
                    'id'        : 'add.' + this.id,
                    'title'     : this.options.popup_add_label,
                    'loadMethod': loadMethod,
                    'contentURL': url,
                    'width'     : this.options.popup_width,
                    'height'    : this.options.popup_height
                };
                if (typeOf(this.options.popup_offset_x) !== 'null') {
                    winOpts.offset_x = this.options.popup_offset_x;
                }
                if (typeOf(this.options.popup_offset_y) !== 'null') {
                    winOpts.offset_y = this.options.popup_offset_y;
                }
                Fabrik.getWindow(winOpts);
            }.bind(this));
        }
        $('#fabrik__swaptable').on('change', function (e) {
            window.location = 'index.php?option=com_fabrik&task=list.view&cid=' + $(this).val();
        });
        // All nav links should submit the form, if we dont then filters are not taken into account when building the list cache id
        // Can result in 2nd pages of cached data being shown, but without filters applied
        var as = this.form.find('.pagination .pagenav');
        if (as.length === 0) {
            as = this.form.find('.pagination a');
        }
        as.each(function (a) {
            a.on('click', function (e) {
                e.stopPropagation();
                if (a.prop('tagName') === 'A') {
                    var o = a.href.toObject();
                    self.fabrikNav(o['limitstart' + this.id]);
                }
            });
        });

        // Not working in J3.2 see http://fabrikar.com/forums/index.php?threads/bug-pagination-not-working-in-chrome.37277/
        /*	if (this.options.admin) {
         Fabrik.on('fabrik.block.added', function (block) {
         if (block.options.listRef === this.options.listRef) {
         var nav = block.form.find('.fabrikNav');
         if (typeOf(nav) !== 'null') {
         nav.find('a').on('click', function (e) {
         e.stopPropagation();
         block.fabrikNav(e.target.get('href'));
         });
         }
         }
         }.bind(this));
         }*/
        this.watchCheckAll();
    },

    /**
     * currently only called from element raw view when using inline edit plugin
     * might need to use for ajax nav as well?
     */

    updateCals: function (json) {
        var types = ['sums', 'avgs', 'count', 'medians'];
        this.form.find('.fabrik_calculations').each(function (c) {
            types.each(function (type) {
                jQuery.each(json[type], function (key, val) {
                    var target = c.find('.' + key);
                    if (typeOf(target) !== 'null') {
                        target.html(val);
                    }
                });
            });
        });
    }
});

/**
 * observe keyboard short cuts
 */

var FbListKeys = my.Class({
    constructor: function (list) {
        window.on('keyup', function (e) {
            if (e.alt) {
                switch (e.key) {
                    case Joomla.JText._('COM_FABRIK_LIST_SHORTCUTS_ADD'):
                        var a = list.form.find('.addRecord');
                        if (list.options.ajax) {
                            a.trigger('click');
                        }
                        if (a.find('a').length > 0) {
                            list.options.ajax ? a.find('a').trigger('click') : document.location = a.find('a').prop('href');
                        } else {
                            if (!list.options.ajax) {
                                document.location = a.get('href');
                            }
                        }
                        break;

                    case Joomla.JText._('COM_FABRIK_LIST_SHORTCUTS_EDIT'):
                        fconsole('edit');
                        break;
                    case Joomla.JText._('COM_FABRIK_LIST_SHORTCUTS_DELETE'):
                        fconsole('delete');
                        break;
                    case Joomla.JText._('COM_FABRIK_LIST_SHORTCUTS_FILTER'):
                        fconsole('filter');
                        break;
                }
            }
        }.bind(this));
    }
});

/**
 * Toggle grouped data by click on the grouped headings icon
 */

var FbGroupedToggler = my.Class({

    options: {
        collapseOthers: false,
        startCollapsed: false,
        bootstrap     : false
    },

    constructor: function (container, options) {
        var rows, h, img, state;
        if (typeOf(container) === 'null') {
            return;
        }
        this.options = $.extend(this.options, options);
        this.container = container;
        this.toggleState = 'shown';
        if (this.options.startCollapsed && this.options.isGrouped) {
            this.collapse();
        }
        container.on('click:relay(.fabrik_groupheading a.toggle)', function (e) {
            if (e.rightClick) {
                return;
            }
            e.stopPropagation();
            e.preventDefault(); //should work according to http://mootools.net/blog/2011/09/10/mootools-1-4-0/

            if (this.options.collapseOthers) {
                this.collapse();
            }
            h = $(e.target).closest('.fabrik_groupheading');
            img = this.options.bootstrap ? h.find('*[data-role="toggle"]') : h.find('img');
            state = img.retrieve('showgroup', true);

            if (h.getNext() && h.getNext().hasClass('fabrik_groupdata')) {
                // For div tmpl
                rows = h.getNext();
            } else {
                rows = h.parent().getNext();
            }
            state ? rows.hide() : rows.show();
            this.setIcon(img, state);
            state = state ? false : true;
            img.store('showgroup', state);
            return false;
        }.bind(this));
    },

    setIcon: function (img, state) {
        if (this.options.bootstrap) {
            var expandIcon = img.get('data-expand-icon'),
                collapsedIcon = img.get('data-collapse-icon');
            if (state) {
                img.removeClass(expandIcon);
                img.addClass(collapsedIcon);
            } else {
                img.addClass(expandIcon);
                img.removeClass(collapsedIcon);
            }
        } else {
            if (state) {
                img.src = img.src.replace('orderasc', 'orderneutral');
            } else {
                img.src = img.src.replace('orderneutral', 'orderasc');
            }
        }
    },

    collapse: function () {
        this.container.find('.fabrik_groupdata').hide();
        var selector = this.options.bootstrap ? 'i' : 'img';
        var i = this.container.find('.fabrik_groupheading a ' + selector);
        if (i.length === 0) {
            i = this.container.find('.fabrik_groupheading ' + selector);
        }
        i.each(function (img) {
            img.store('showgroup', false);
            this.setIcon(img, true);
        }.bind(this));
    },

    expand: function () {
        this.container.find('.fabrik_groupdata').show();
        var i = this.container.find('.fabrik_groupheading a img');
        if (i.length === 0) {
            i = this.container.find('.fabrik_groupheading img');
        }
        i.each(function (img) {
            img.store('showgroup', true);
            this.setIcon(img, false);
        }.bind(this));
    },

    toggle: function () {
        this.toggleState === 'shown' ? this.collapse() : this.expand();
        this.toggleState = this.toggleState === 'shown' ? 'hidden' : 'shown';
    }
});

/**
 * set up and show/hide list actions for each row
 * Deprecated in 3.1
 */
var FbListActions = my.Class({

    options: {
        'selector': 'ul.fabrik_action, .btn-group.fabrik_action',
        'method'  : 'floating',
        'floatPos': 'bottom'
    },

    constructor: function (list, options) {
        this.options = $.extend(this.options, options);
        this.list = list; // main list js object
        this.actions = [];
        this.setUpSubMenus();
        Fabrik.on('fabrik.list.update', function (list, json) {
            this.observe();
        }.bind(this));
        this.observe();
    },

    observe: function () {
        if (this.options.method === 'floating') {
            this.setUpFloating();
        } else {
            this.setUpDefault();
        }
    },

    setUpSubMenus: function () {
        if (!this.list.form) {
            return;
        }
        this.actions = this.list.form.find(this.options.selector);
        this.actions.each(function (ul) {
            // Sub menus ie group by options
            if (ul.find('ul')) {
                var el = ul.find('ul');
                var c = $('<div />').adopt(el.clone());
                var trigger = el.getPrevious();
                if (trigger.find('.fabrikTip')) {
                    trigger = trigger.find('.fabrikTip');
                }
                var t = Fabrik.tips ? Fabrik.tips.options : {};
                var tipOpts = Object.merge(Object.clone(t), {
                    showOn  : 'click',
                    hideOn  : 'click',
                    position: 'bottom',
                    content : c
                });
                var tip = new FloatingTips(trigger, tipOpts);
                el.dispose();
            }
        });
    },

    setUpDefault: function () {
        this.actions = this.list.form.find(this.options.selector);
        this.actions.each(function (ul) {
            if (ul.parent().hasClass('fabrik_buttons')) {
                return;
            }
            ul.fade(0.6);
            var r = ul.closest('.fabrik_row').length > 0 ? ul.closest('.fabrik_row') : ul.closest('.fabrik___heading');
            if (r) {
                // $$$ hugh - for some strange reason, if we use 1 the object disappears
                // in Chrome and Safari!
                r.ons({
                    'mouseenter': function (e) {
                        ul.fade(0.99);
                    },
                    'mouseleave': function (e) {
                        ul.fade(0.6);
                    }
                });
            }
        });
    },

    setUpFloating: function () {
        var chxFound = false;
        this.list.form.find(this.options.selector).each(function (ul) {
            if (ul.closest('.fabrik_row')) {
                if (i = ul.closest('.fabrik_row').find('input[type=checkbox]')) {
                    chxFound = true;
                    var hideFn = function (e, elem, leaving) {
                        if (!e.target.checked) {
                            this.hide(e, elem);
                        }
                        if (leaving === 'tip') {
                            // elem.checked = false; (cant do this otherwise delete
                            // confirmation wont delete anything
                        }
                    };

                    var c = function (el, o) {
                        var r = ul.parent();
                        r.store('activeRow', ul.closest('.fabrik_row'));
                        return r;
                    }.bind(this.list);

                    var opts = {
                        position : this.options.floatPos,
                        showOn   : 'change',
                        hideOn   : 'click',
                        content  : c,
                        'heading': 'Edit: ',
                        hideFn   : function (e) {
                            return !e.target.checked;
                        },
                        showFn   : function (e, trigger) {
                            Fabrik.activeRow = ul.parent().retrieve('activeRow');
                            trigger.store('list', this.list);
                            return e.target.checked;
                        }.bind(this.list)
                    };

                    var tipOpts = Fabrik.tips ? Object.merge(Object.clone(Fabrik.tips.options), opts) : opts;
                    var tip = new FloatingTips(i, tipOpts);
                }
            }
        }.bind(this));

        this.list.form.find('.fabrik_select input[type=checkbox]').on('click', function (e) {
            Fabrik.activeRow = e.target.closest('.fabrik_row');
        });
        // watch the top/master chxbox
        var chxall = this.list.form.find('input[name=checkAll]');
        if (typeOf(chxall) !== 'null') {
            chxall.store('listid', this.list.id);
        }

        var c = function (el) {
            var p = el.closest('.fabrik___heading');
            return typeOf(p) !== 'null' ? p.find(this.options.selector) : '';
        }.bind(this);

        var t = Fabrik.tips ? Object.clone(Fabrik.tips.options) : {};
        var tipChxAllOpts = Object.merge(t, {
            position : this.options.floatPos,
            html     : true,
            showOn   : 'click',
            hideOn   : 'click',
            content  : c,
            'heading': 'Edit all: ',
            hideFn   : function (e) {
                return !e.target.checked;
            },
            showFn   : function (e, trigger) {
                trigger.retrieve('tip').click.store('list', this.list);
                return e.target.checked;
            }.bind(this.list)
        });
        var tip = new FloatingTips(chxall, tipChxAllOpts);

        // hide markup that contained the actions
        if (this.list.form.find('.fabrik_actions') && chxFound) {
            this.list.form.find('.fabrik_actions').hide();
        }
        if (this.list.form.find('.fabrik_calculation')) {
            var calc = this.list.form.find('.fabrik_calculation').getLast();
            if (typeOf(calc) !== 'null') {
                calc.hide();
            }
        }
    }
});