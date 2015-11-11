/**
 * Notes Element
 *
 * @copyright: Copyright (C) 2005-2013, fabrikar.com - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

/**
 watch another element for changes to its value, and send an ajax call to update
 this elements values
 */

var FbNotes = my.Class(FbElement, {

    options: {
        'rowid': 0,
        'id'   : 0
    },

    constructor: function (element, options) {
        this.plugin = 'notes';
        this.parent(element, options);
        this.setUp();
    },

    setUp: function () {
        var self = this;
        if (this.options.rowid !== 0) {
            this.element.find('.button').on('click', function (e) {
                self.submit(e);
            });
            this.field = this.element.find('.fabrikinput');
            var msg = this.element.find('div');
            msg.makeResizable({
                'modifiers': {x: false, y: 'height'},
                'handle'   : this.element.find('.noteHandle')
            });
            this.element.find('.noteHandle').css('cursor', 'all-scroll');
        }
    },

    submit: function (e) {
        e.stop();
        var label = this.field.get('value');
        if (label !== '') {
            Fabrik.loader.start(this.element);
            var data = {
                'option'    : 'com_fabrik',
                'format'    : 'raw',
                'task'      : 'plugin.pluginAjax',
                'plugin'    : 'notes',
                'method'    : 'ajax_addNote',
                'element_id': this.options.id,
                'v'         : label,
                'rowid'     : this.options.rowid,
                'formid'    : this.form.id
            };
            this.myAjax = new Request.JSON({
                'url'      : '',
                'data'     : data,
                onSuccess  : function (json) {
                    Fabrik.loader.stop(this.element);
                    var rows = this.element.find('div');
                    var row = $(document.createElement('div')).addClass('row-fluid');
                    var inner_row = $(document.createElement('div')).addClass('span12').html(json.label).inject(row);
                    inner_row.inject(rows);
                    this.field.value = '';
                }.bind(this),
                'onError'  : function (text) {
                    Fabrik.loader.stop(this.element);
                    window.alert(text);
                },
                'onFailure': function (xhr) {
                    Fabrik.loader.stop(this.element);
                    window.alert('ajax failed');
                },
                'onCancel' : function () {
                    Fabrik.loader.stop(this.element);
                }
            }).send();

        }
    },

    cloned: function (c) {
        Fabrik.trigger('fabrik.notes.update', this);
        this.parent(c);
    }
});