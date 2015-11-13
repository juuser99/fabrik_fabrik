/**
 * List Plugin
 *
 * @copyright: Copyright (C) 2005-2015, fabrikar.com - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
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
        if (this.getList() === undefined) {
            return;
        } else {
            // Viz doesn't have getForm method;
            if (typeof this.getList().getForm === 'function') {
                this.listform = this.getList().getForm();
                var l = this.listform.find('input[name=listid]');
                // in case its in a viz
                if (l.length === 0) {
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
     *
     * @return {object|undefined} List object
     */
    getList: function () {
        if (Fabrik.blocks['list_' + this.options.ref] === undefined) {
            return undefined;
        }
        return Fabrik.blocks['list_' + this.options.ref];
    },

    /**
     * Get a html nodes row id - so you can pass in td or tr for example
     * presumes each row has a fabrik_row class and its id is in a string 'list_listref_rowid'
     * @param {jQuery} node
     */
    getRowId: function (node) {
        if (!node.hasClass('fabrik_row')) {
            node = node.closest('.fabrik_row');
        }
        return node[0].id.split('_').pop();
    },

    clearFilter: Function.from(),

    watchButton: function () {
        // Do relay for floating menus
        if (this.options.name === undefined) {
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
                    chx.prop('checked', true);
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