/**
 * Admin Table Editor
 *
 * @copyright: Copyright (C) 2005-2015, fabrikar.com - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

var tablesElement = my.Class({

    options: {
        conn: null
    },

    constructor: function (el, options) {
        this.el = el;
        this.options = $.extend(this.options, options);
        // If loading in a form plugin then the connect is not yet available in the dom
        if ($('#' + this.options.conn).length === 0) {
            this.periodical = setInterval(function () {
                this.getCnn.call(this, true);
            }, 500);
        } else {
            this.setUp();
        }
    },

    cloned: function () {

    },

    getCnn: function () {
        if ($('#' + this.options.conn).length === 0) {
            return;
        }
        this.setUp();
        clearInterval(this.periodical);
    },

    setUp: function () {
        var self = this;
        this.el = $('#' + this.el);
        this.cnn = $('#' + this.options.conn);
        this.loader = $('#' + this.el.id + '_loader');
        this.cnn.on('change', function () {
            self.updateMe();
        });
        // See if there is a connection selected
        var v = this.cnn.get('value');
        if (v !== '' && v !== -1) {
            this.updateMe();
        }
    },

    updateMe: function (e) {
        var self = this;
        if (e) {
            e.stopPropagation();
        }
        if (this.loader) {
            this.loader.show();
        }
        var cid = this.cnn.val();

        var myAjax = $.getJSON({
            url : 'index.php',
            data: {
                'option': 'com_fabrik',
                'format': 'raw',
                'task'  : 'plugin.pluginAjax',
                'g'     : 'element',
                'plugin': 'field',
                'method': 'ajax_tables',
                'cid'   : parseInt(cid, 10)
            },
        }).done(function (opts) {
            if (opts.err) {
                window.alert(opts.err);
            } else {
                self.el.empty();
                opts.each(function (opt) {
                    //var o = {'value':opt.value};//wrong for calendar
                    var o = {'value': opt};
                    if (opt === self.options.value) {
                        o.selected = 'selected';
                    }
                    if (self.loader) {
                        self.loader.hide();
                    }
                    $(document.createElement('option')).attr(o).text(opt).inject(this.el);
                });
            }
        })
            .fail(function (jqxhr, textStatus, error) {
                self.el.empty();
                if (self.loader) {
                    self.loader.hide();
                }
                window.alert(textStatus + ': ' + error);
            });
        Fabrik.requestQueue.add(myAjax);
    }
});