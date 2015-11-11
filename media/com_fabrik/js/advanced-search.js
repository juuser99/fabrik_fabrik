/**
 * Advanced Search
 *
 * @copyright: Copyright (C) 2005-2013, fabrikar.com - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

/*jshint mootools: true */
/*global Fabrik:true, fconsole:true, Joomla:true, CloneObject:true, $H:true,unescape:true */

AdvancedSearch = my.Class({

    options: {
        'ajax'            : false,
        'controller'      : 'list',
        'parentView'      : '',
        'defaultStatement': '='
    },

    constructor: function (options) {
        var add = this.form.find('.advanced-search-add'),
            clearAll = this.form.find('.advanced-search-clearall'),
            self = this;
        this.options = $.extend(this.options, options);
        this.form = $('#advanced-search-win' + this.options.listref).find('form');
        this.trs = Array.from([]);
        if (add.length > 0) {
            add.removeEvents('click');
            add.on('click', function (e) {
                self.addRow(e);
            });
            clearAll.removeEvents('click');
            clearAll.on('click', function (e) {
                self.resetForm(e);
            });
            this.trs.each(function (tr) {
                tr.inject(self.form.find('.advanced-search-list').find('tr').getLast(), 'after');
            });
        }

        this.form.on('click', 'tr', function (e, target) {
            self.form.find('tr').removeClass('fabrikRowClick');
            $(this).addClass('fabrikRowClick');
        });
        this.watchDelete();
        this.watchApply();
        this.watchElementList();
        Fabrik.trigger('fabrik.advancedSearch.ready', this);
    },

    watchApply: function () {
        var self = this;
        this.form.find('.advanced-search-apply').on('click', function (e) {
            Fabrik.trigger('fabrik.advancedSearch.submit', this);
            var filterManager = Fabrik['filter_' + this.options.parentView];

            // Format date advanced search fields to db format before posting
            if (typeOf(filterManager) !== 'null') {
                filterManager.onSubmit();
            }
            /* Ensure that we clear down other advanced searches from the session.
             * Otherwise, filter on one element and submit works, but changing the filter element and value
             * will result in 2 filters applied (not one)
             * @see http://fabrikar.com/forums/index.php?threads/advanced-search-remembers-value-of-last-dropdown-after-element-change.34734/#post-175693
             */
            var list = self.getList();
            $(document.createElement('input')).attr({
                'name' : 'resetfilters',
                'value': 1,
                'type' : 'hidden'
            }).inject(self.form);

            if (!self.options.ajax) {
                return;
            }
            e.stop();

            list.submit(self.options.controller + '.filter');
        });
    },

    getList: function () {
        var list = Fabrik.blocks['list_' + this.options.listref];
        if (typeOf(list) === 'null') {
            list = Fabrik.blocks[this.options.parentView];
        }
        return list;
    },

    watchDelete: function () {
        var remove = this.form.find('.advanced-search-remove-row'),
            self = this;
        //should really just delegate these events from the adv search table
        remove.removeEvents();
        remove.on('click', function (e) {
            self.removeRow(e);
        });
    },

    watchElementList: function () {
        var select = this.form.find('select.key'),
            self = this;
        select.removeEvents();
        select.on('change', function (e) {
            self.updateValueInput(e);
        });
    },

    /**
     * called when you choose an element from the filter dropdown list
     * should run ajax query that updates value field to correspond with selected
     * element
     * @param {Object} e event
     */

    updateValueInput: function (e) {
        var row = $(e.target).closest('tr'),
            url = 'index.php?option=com_fabrik&task=list.elementFilter&format=raw',
            eldata;
        Fabrik.loader.start(row);
        var v = $(e.target).val();
        var update = $(e.target).parent().parent().find('td')[3];
        if (v === '') {
            update.html('');
            return;
        }
        eldata = this.options.elementMap[v];
        $.ajax({
            'url'   : url,
            'update': update,
            'data'  : {
                'element'   : v,
                'id'        : this.options.listid,
                'elid'      : eldata.id,
                'plugin'    : eldata.plugin,
                'counter'   : this.options.counter,
                'listref'   : this.options.listref,
                'context'   : this.options.controller,
                'parentView': this.options.parentView
            }
        }).done(function () {
            Fabrik.loader.stop(row);
        });
    },

    addRow: function (e) {
        this.options.counter++;
        e.stop();
        var tr = this.form.find('.advanced-search-list').find('tbody').find('tr').getLast();
        var clone = tr.clone();
        clone.removeClass('oddRow1').removeClass('oddRow0').addClass('oddRow' + this.options.counter % 2);
        clone.inject(tr, 'after');
        clone.find('td').empty().html(this.options.conditionList);
        var tds = clone.find('td');
        tds[1].empty().html(this.options.elementList);
        tds[1].adopt([
            $(document.createElement('input')).attr({
                'type' : 'hidden',
                'name' : 'fabrik___filter[list_' + this.options.listref + '][search_type][]',
                'value': 'advanced'
            }),
            $(document.createElement('input')).attr({
                'type' : 'hidden',
                'name' : 'fabrik___filter[list_' + this.options.listref + '][grouped_to_previous][]',
                'value': '0'
            })
        ]);
        tds[2].empty().html(this.options.statementList);
        tds[3].empty();
        this.watchDelete();
        this.watchElementList();
        Fabrik.trigger('fabrik.advancedSearch.row.added', this);
    },

    removeRow: function (e) {
        e.stop();
        if (this.form.find('.advanced-search-remove-row').length > 1) {
            this.options.counter--;
            var tr = e.target.closest('tr');
            var fx = new Fx.Morph(tr, {
                duration  : 800,
                transition: Fx.Transitions.Quart.easeOut,
                onComplete: function () {
                    tr.dispose();
                }
            });
            fx.start({
                'height' : 0,
                'opacity': 0
            });
        }
        Fabrik.trigger('fabrik.advancedSearch.row.removed', this);
    },

    /**
     * removes all rows except for the first one, whose values are reset to empty
     */
    resetForm: function () {
        var table = this.form.find('.advanced-search-list'),
            self = this;
        if (!table) {
            return;
        }
        table.find('tbody tr').each(function (i) {
            if (i >= 1) {
                $(this).dispose();
            }
            if (i === 0) {
                $(this).find('.inputbox').each(function () {
                    if ($(this).id.test(/condition$/)) {
                        $(this).value = self.options.defaultStatement;
                    }
                    else {
                        $(this).selectedIndex = 0;
                    }
                });
                $(this).find('input').each(function () {
                    $(this).value = '';
                });
            }
        });
        this.watchDelete();
        this.watchElementList();
        Fabrik.trigger('fabrik.advancedSearch.reset', this);
    },

    deleteFilterOption: function (event) {
        var self = this;
        $(event.target).removeEvent('click', function (e) {
            self.deleteFilterOption(e);
        });
        var tr = $(event.target).parent().parent();
        var table = tr.parent();
        table.removeChild(tr);
        event.stop();
    }

});