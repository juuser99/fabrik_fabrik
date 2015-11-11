/**
 * Admin Fabrik Tables Editor
 *
 * @copyright: Copyright (C) 2005-2015, fabrikar.com - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

var fabriktablesElement = my.Class({

    options: {
        conn        : null,
        connInRepeat: true,
        container   : ''
    },

    constructor: function (el, options) {
        this.el = el;
        this.options = $.extend(this.options, options);
        this.elements = [];
        this.elementLists = {}; // keyed on specific element options
        this.waitingElements = {}; // keyed on specific element options
        // if loading in a form plugin then the connect is not yet available in the dom
        if ($('#' + this.options.conn).length > 0) {
            this.periodical = setInterval(function () {
                this.getCnn.call(this, true);
            }, 500);
        } else {
            this.setUp();
        }
    },

    getCnn: function () {
        if ($('#' + this.options.conn.length) === 0) {
            return;
        }
        this.setUp();
        clearInterval(this.periodical);
    },

    registerElement: function (el) {
        this.elements.push(el);
        this.updateElements();
    },

    setUp: function () {
        this.el = $('#' + this.el);
        this.cnn = $('#' + this.options.conn);
        if (this.cnn === 'null') {
            return;
        }
        this.loader = $('#' + this.el.id + '_loader');

        var self = this;

        if (this.cnn.hasClass('chzn-done')) {
            jQuery('#' + this.cnn.id).on('change', function () {
                $('#' + self.cnn).trigger('change');
            });
        }

        this.cnn.on('change', function (e) {
            self.updateMe(e);
        });

        if (this.el.hasClass('chzn-done')) {
            jQuery('#' + this.el.id).on('change', function () {
                $('#' + self.el.id).trigger('change');
            });
        }

        this.el.on('change', function (e) {
            self.updateElements(e);
        });

        // see if there is a connection selected
        var v = this.cnn.val();
        if (v !== '' && v !== -1) {
            this.updateMe();
        }
    },

    updateMe: function (e) {
        var self = this;
        if (e) {
            e.stopPropagation();
        }
        var cid = this.cnn.get('value');
        // keep repeating the periodical until the cnn drop down is completed
        if (!cid) {
            return;
        }
        if (this.loader) {
            this.loader.show();
        }

        var myAjax = $.getJSON({
            url : 'index.php',
            data: {
                'option': 'com_fabrik',
                'format': 'raw',
                'task'  : 'plugin.pluginAjax',
                'g'     : 'element',
                'plugin': 'field',
                'method': 'ajax_tables',
                'showf' : '1',
                'cid'   : parseInt(cid, 10)
            }

        }).fail(function (jqxhr, textStatus, error) {
            console.log('fabriktables request exception', textStatus, error);
        }).done(function (opts) {
            if (opts.err) {
                window.alert(opts.err);
            } else {
                self.el.empty();
                opts.each(function (opt) {
                    var o = {
                        'value': opt.id
                    };
                    if (opt.id === self.options.value) {
                        o.selected = 'selected';
                    }
                    $(document.createElement('option')).attr(o).text(opt.label).inject(this.el);
                });
                if (self.loader) {
                    self.loader.hide();
                }
                if (self.el.hasClass('chzn-done')) {
                    $('#' + this.el.id).trigger('liszt:updated');
                }
                this.updateElements();
            }
        });

        /*
         * Use Fabrik.requestQueue rather than myAjax.send()
         * as it is polled on form save to ensure that elements are not in a loading state
         */
        Fabrik.requestQueue.add(myAjax);
    },

    updateElements: function () {
        var self = this;
        this.elements.each(function (element) {
            var opts = element.getOpts();
            var table = this.el.get('value');
            if (table === '') {
                // $$$ rob don't empty as this messes up parameter saving in paypal
                // plugin
                // element.el.empty();
                return;
            }
            if (this.loader) {
                this.loader.show();
            }
            var key = opts.getValues().toString() + ',' + table;
            if (!this.waitingElements.hasOwnProperty(key)) {
                this.waitingElements[key] = {};
            }
            if (this.elementLists[key] !== undefined) {
                if (this.elementLists[key] === '') {
                    // delay update
                    this.waitingElements[key][element.el.id] = element;
                } else {
                    // keyed on specific element options
                    this.updateElementOptions(this.elementLists[key], element);
                }
            } else {

                var cid = this.cnn.get('value');
                this.elementLists[key] = '';

                var ajaxopts = {
                    'option': 'com_fabrik',
                    'format': 'raw',
                    'task'  : 'plugin.pluginAjax',
                    'g'     : 'element',
                    'plugin': 'field',
                    'method': 'ajax_fields',
                    'cid'   : parseInt(cid, 10),
                    'showf' : '1',
                    'k'     : '2',
                    't'     : table
                };
                opts.each(function (v, k) {
                    ajaxopts[k] = v;
                });
                new $.ajax({
                    'url'     : 'index.php',
                    'data'    : ajaxopts,
                })
                    .done(function (r) {
                        self.elementLists[key] = r;
                        self.updateElementOptions(r, element);
                        $.each(self.waitingElements[key], function (i, el) {
                            self.updateElementOptions(r, el);
                            delete self.waitingElements[key][i];
                        });
                    })
                    .fail(function (jqxhr, textStatus, error) {
                        $.each(this.waitingElements[key], function (i, el) {
                            self.updateElementOptions('[]', el);
                            delete self.waitingElements[key][i];
                        });
                        if (self.loader) {
                            self.loader.hide();
                        }
                        window.alert(textStatus + ' ' + error);
                    });
            }
        }.bind(this));
    },

    updateElementOptions: function (r, element) {
        var target, dotValue;
        if (r === '') {
            return;
        }

        var table = $('#' + this.el).val();
        var key = element.getOpts().getValues().toString() + ',' + table;
        var opts = eval(r);

        if (element.el.prop('tagName') === 'TEXTAREA') {
            target = element.el.parent().find('select');
        } else {
            target = element.el;
        }
        target.empty();
        var o = {
            'value': ''
        };
        if (element.options.value === '') {
            o.selected = 'selected';
        }
        $(document.createElement('option')).attr(o).text('-').inject(target);
        dotValue = element.options.value.replace('.', '___');
        opts.each(function (opt) {
            var v = opt.value.replace('[]', '');
            var o = {
                'value': v
            };

            if (v === element.options.value || v === dotValue) {
                o.selected = 'selected';
            }

            $(document.createElement('option')).attr(o).text(opt.label).inject(target);
        }.bind(this));
        if (this.loader) {
            this.loader.hide();
        }

    },

    // only called from repeat viz admin interface i think
    cloned              : function (newid, counter) {
        if (this.options.connInRepeat === true) {
            // table needs to update watch connection id
            var cid = this.options.conn.split('-');
            cid.pop();
            this.options.conn = cid.join('-') + '-' + counter;
        }
        this.el = newid;
        this.elements = [];
        this.elementLists = {};
        this.waitingElements = {};
        this.setUp();
        FabrikAdmin.model.fields.fabriktable[this.el.id] = this;
    }

});