/**
 * List Order
 *
 * @copyright: Copyright (C) 2005-2015, fabrikar.com - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

var FbListOrder = my.Class(FbListPlugin, {

    constructor: function (options) {
        FbListOrder.Super.call(this, options);

        //for iE?
        document.ondragstart = function () {
            return false;
        };

        this.sortables = {};
        this.origorder = {};
        this.neworder = {};

        var isGrouped = Fabrik.blocks['list_' + this.options.ref].options.isGrouped;
        var container = this.getList().list;

        if (!isGrouped) {
            container.css('position', 'relative');
            if (typeOf(container.find('tbody')) !== 'null') {
                container = container.find('tbody');
            }
            this.makeSortable(container);
        } else {
            var containers = container.find('tbody.fabrik_groupdata');
            containers.each(function (container, x) {
                container.setProperty('data-order', x);
                console.log(container, x);
                this.makeSortable(container);
            }.bind(this));

        }

        if (this.options.handle !== false && container.getElements(this.options.handle).length === 0) {
            fconsole('order: handle selected (' + this.options.handle + ') but not found in container');
            return;
        }

        if (options.enabled === false) {
            fconsole('drag n drop reordering not enabled - need to order by ordering element');
            this.sortable.detach();
        } else {
            if (this.options.handle) {
                container.getElements(this.options.handle).css('cursor', 'move');
            } else {
                container.getChildren().css('cursor', 'move');
            }
        }
    },

    makeSortable: function (container) {
        var self = this,
            sortable = new Sortables(container, {
                clone     : true,
                constrain : false,
                revert    : true,
                opacity   : 0.7,
                transition: 'elastic:out',

                'handle'  : this.options.handle,
                onComplete: function (element, clone) {
                    clone ? clone.removeClass('fabrikDragSelected') : element.removeClass('fabrikDragSelected');

                    var c = element.closest('tbody');
                    var sort = this.sortables[c.getProperty('data-order')];
                    this.neworder[c] = this.getOrder(sort);

                    Fabrik.loader.start('list_' + this.options.ref, 'sorting', true);
                    $.ajax({
                        url   : 'index.php',
                        'data': {
                            'option'   : 'com_fabrik',
                            'format'   : 'raw',
                            'task'     : 'plugin.pluginAjax',
                            'plugin'   : 'order',
                            'g'        : 'list',
                            'listref'  : this.options.ref,
                            'method'   : 'ajaxReorder',
                            'order'    : this.neworder[c],
                            'origorder': this.origorder[c],
                            'dragged'  : this.getRowId(element),
                            'listid'   : this.options.listid,
                            'orderelid': this.options.orderElementId,
                            'direction': this.options.direction
                        }
                    }).complete(function (r) {
                        Fabrik.loader.stop('list_' + self.options.ref, null, true);
                        self.origorder[c] = self.neworder[c];
                    });

                }.bind(this),
                onStart   : function (element, clone) {
                    var c = element.closest('tbody');
                    var sort = self.sortables[c.getProperty('data-order')];
                    this.origorder[c] = self.getOrder(sort);
                    clone ? clone.addClass('fabrikDragSelected') : element.addClass('fabrikDragSelected');
                }
            });

        this.sortables[container.getProperty('data-order')] = sortable;
    },

    // Get the id from the fabrik row's html id

    getRowId: function (element) {
        return typeOf(element.getProperty('id')) === 'null' ? null : element.getProperty('id').replace('list_' + this.options.ref + '_row_', '');
    },

    // Get the order of the sortable

    getOrder: function (sortable) {
        var self = this;
        return (sortable.serialize(0, function (element) {
            return self.getRowId(element);
        })).clean();
    }
});