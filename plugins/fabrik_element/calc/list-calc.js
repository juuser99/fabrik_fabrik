/**
 * Calc Element - List
 *
 * @copyright: Copyright (C) 2005-2015, fabrikar.com - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

var FbCalcList = my.Class({

    options: {},

    constructor: function (id, options) {
        options.element = id;
        var self = this;
        this.options = $.extend(this.options, options);
        this.col = $('.' + id);
        this.list = Fabrik.blocks[this.options.listRef];
        if (this.options.doListUpdate) {
            Fabrik.addEvent('fabrik.list.updaterows', function () {
                self.update();
            });
        }
    },

    update: function () {
        var self = this,
            data = {
                'option'     : 'com_fabrik',
                'format'     : 'raw',
                'task'       : 'plugin.pluginAjax',
                'plugin'     : 'calc',
                'g'          : 'element',
                'listid'     : this.options.listid,
                'formid'     : this.options.formid,
                'method'     : 'ajax_listUpdate',
                'element_id' : this.options.elid,
                'rows'       : this.list.getRowIds(),
                'elementname': this.options.elid
            };

        new $.getJSON({
            url : '',
            data: data
        }).fail(function (jqxhr, textStatus, error) {
                fconsole('Fabrik:list-calc:update error:' + textStatus, error);
            }).success(function (json) {
                var owns = Object.prototype.hasOwnProperty;
                for (var key in json) {
                    if (owns.call(json, key) && typeof json[key] === 'string') {
                        json[key] = Encoder.htmlDecode(json[key]);
                    }
                }
                $.each(json, function (id, html) {
                    var cell = this.list.list.find('#' + id + ' .' + this.options.element);
                    if (html !== false) {
                        cell.html(html);
                    }
                });
            });
    }

});