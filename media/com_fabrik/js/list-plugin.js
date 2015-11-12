/**
 * List Plugin
 *
 * @copyright: Copyright (C) 2005-2015, fabrikar.com - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

/* jshint mootools: true */
/*
 * global Fabrik:true, fconsole:true, Joomla:true, CloneObject:true,
 * $H:true,unescape:true,head:true,FbListActions:true,FbGroupedToggler:true,f:true
 */

var FbListPlugin = my.Class({
    options: {
        requireChecked: true,
        canAJAX       : true,
        ref           : ''
    },

    constructor: function (options) {
        this.options = $.extend(this.options, options);
        this.result = true; // set this to false in window.fireEvents to stop
        // current action (e.g. stop ordering when
        // fabrik.list.order run)
        if (typeOf(this.getList()) === 'null') {
            return;
        } else {
            // Viz doesn't have getForm method;
            if (typeof this.getList().getForm === 'function') {
                this.listform = this.getList().getForm();
                var l = this.listform.find('input[name=listid]');
                // in case its in a viz
                if (typeOf(l) === 'null') {
                    return;
                }
                this.listid = l.value;
            } else {
                this.listform = this.getList().container.find('form');
            }
        }

        this.watchButton();
    },

    /**
     * Fet the list object that the plugin is assigned to
     */
    getList: function () {
        var b = Fabrik.blocks['list_' + this.options.ref];
        if (typeOf(b) === 'null') {
            b = Fabrik.blocks['visualization_' + this.options.ref];
        }
        return b;
    },

    /**
     * Get a html nodes row id - so you can pass in td or tr for example
     * presumes each row has a fabrik_row class and its id is in a string 'list_listref_rowid'
     */
    getRowId: function (node) {
        if (!node.hasClass('fabrik_row')) {
            node = node.closest('.fabrik_row');
        }
        return node.id.split('_').getLast();
    },

    clearFilter: Function.from(),

    watchButton: function () {
        // Do relay for floating menus
        if (typeOf(this.options.name) === 'null') {
            return;
        }
        // Might need to be this.listform and not document
        $(document).on('click', '.' + this.options.name, function (e) {
            if (e.rightClick) {
                return;
            }
            e.stopPropagation();
            e.preventDefault();

            // Check that the button clicked belongs to this this.list
            if ($(this).data('list') !== this.list.options.listRef) {
                return;
            }

            var row, chx;
            // if the row button is clicked check its associated checkbox
            if ($(this).closest('.fabrik_row')) {
                row = $(this).closest('.fabrik_row');
                if (row.find('input[name^=ids]')) {
                    chx = row.find('input[name^=ids]');
                    this.listform.find('input[name^=ids]').prop('checked', false);
                    chx.set('checked', true);
                }
            }

            // check that at least one checkbox is checked
            var ok = false;
            this.listform.find('input[name^=ids]').each(function () {
                if (this.checked) {
                    ok = true;
                }
            });
            if (!ok && this.options.requireChecked) {
                window.alert(Joomla.JText._('COM_FABRIK_PLEASE_SELECT_A_ROW'));
                return;
            }
            var n = this.options.name.split('-');
            this.listform.find('input[name=fabrik_listplugin_name]').val(n[0]);
            this.listform.find('input[name=fabrik_listplugin_renderOrder]').val(n.getLast());
            this.buttonAction();
        }.bind(this));
    },

    buttonAction: function () {
        var task = this.options.canAJAX ? 'list.doPlugin' : 'list.doPlugin.noAJAX';
        this.list.submit(task);
    }
});