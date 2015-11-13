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

    /**
     * Constructor
     * @param {string} element
     * @param {object} options
     * @returns {*}
     */
    constructor: function (element, options) {
        this.plugin = 'notes';
        FbNotes.Super.call(this, element, options);
        this.setUp();
    },

    /**
     * Set up the element
     */
    setUp: function () {
        var self = this,
            msg = this.element.find('div');
        if (this.options.rowid !== 0) {
            this.element.find('.button').on('click', function (e) {
                self.submit(e);
            });
            this.field = this.element.find('.fabrikinput');
            msg.makeResizable({
                'modifiers': {x: false, y: 'height'},
                'handle'   : this.element.find('.noteHandle')
            });
            this.element.find('.noteHandle').css('cursor', 'all-scroll');
        }
    },

    /**
     * Submit the note via ajax.
     * @param {object} e Event
     */
    submit: function (e) {
        e.stopPropagation();
        var label = this.field.val(),
            self = this;
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
            this.myAjax = $.getJSON({
                'url' : '',
                'data': data
            }).done(function (json) {
                Fabrik.loader.stop(this.element);
                var rows = self.element.find('div');
                var row = $(document.createElement('div')).addClass('row-fluid');
                var inner_row = $(document.createElement('div')).addClass('span12').html(json.label).inject(row);
                inner_row.inject(rows);
                self.field.val('');
            }).fail(function () {
                Fabrik.loader.stop(this.element);
            });

        }
    },

    /**
     * Run when the element is cloned in a repeat group
     * @param {number} c
     */
    cloned: function (c) {
        Fabrik.trigger('fabrik.notes.update', this);
        this.parent(c);
    }
});