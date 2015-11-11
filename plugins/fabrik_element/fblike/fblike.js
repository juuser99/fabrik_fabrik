/**
 * Facebook Like Element
 *
 * @copyright: Copyright (C) 2005-2015, fabrikar.com - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

var FbLike = my.Class(FbElement, {

    constructor: function (element, options) {
        var self = this;
        this.plugin = 'fblike';
        FbLike.Super.call(this, element, options);

        FB.Event.subscribe('edge.create', function (response) {
            self.like('+');
        });

        FB.Event.subscribe('edge.remove', function (response) {
            self.like('-');
        });
    },

    like: function (dir) {
        var data = {
            'option'     : 'com_fabrik',
            'format'     : 'raw',
            'task'       : 'plugin.pluginAjax',
            'plugin'     : 'fblike',
            'method'     : 'ajax_rate',
            'g'          : 'element',
            'element_id' : this.options.elid,
            'row_id'     : this.options.row_id,
            'elementname': this.options.elid,
            'listid'     : this.options.listid,
            'direction'  : dir
        };

        $.getJSON({
            url   : '',
            'data': data,
        }).done(function (r) {
            if (r.error) {
                console.log(r.error);
            }
        });
    }
});