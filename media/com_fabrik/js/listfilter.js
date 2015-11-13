/**
 * List Filter
 *
 * @copyright: Copyright (C) 2005-2015, fabrikar.com - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

var FbListFilter = my.Class({

    options: {
        'container'     : '',
        'type'          : 'list',
        'id'            : '',
        'advancedSearch': {
            'controller': 'list'
        }
    },

    constructor: function (options) {
        var advancedSearchButton, self = this;
        this.filters = {};
        this.options = $.extend(this.options, options);
        this.advancedSearch = false;
        this.container = document.id(this.options.container);
        this.filterContainer = this.container.find('.fabrikFilterContainer');
        this.filtersInHeadings = this.container.find('.listfilter');
        var b = this.container.find('.toggleFilters');
        b.on('click', function (e) {
            e.stopPropagation();
            this.filterContainer.toggle();
            this.filtersInHeadings.toggle();
        }.bind(this));

        this.filterContainer.hide();
        this.filtersInHeadings.toggle();

        if (this.container.length === 0) {
            return;
        }
        this.getList();
        var c = this.container.find('.clearFilters');
        c.off();
        c.on('click', function (e) {
            var plugins;
            e.stopPropagation();

            // Reset the filter fields that contain previously selected values
            this.container.find('.fabrik_filter').each(function (f) {
                if (f.name.contains('[value]') || f.name.contains('fabrik_list_filter_all') || f.hasClass('autocomplete-trigger')) {
                    if ($(this).prop('tagName') === 'SELECT') {
                        this.selectedIndex = $(this).prop('multiple') ? -1 : 0;
                    } else {
                        if ($(this).prop('type') === 'checkbox') {
                            this.checked = false;
                        } else {
                            this.value = '';
                        }
                    }
                }
            });
            plugins = this.getList().plugins;
            if (plugins !== null) {
                plugins.each(function (p) {
                    p.clearFilter();
                });
            }
            var injectForm = this.container.prop('tagName') === 'FORM' ? this.container : this.container.find('form');
            $('<input />').attr({
                'name' : 'resetfilters',
                'value': 1,
                'type' : 'hidden'
            }).inject(injectForm);
            if (this.options.type === 'list') {
                this.list.submit('list.clearfilter');
            } else {
                this.container.find('form[name=filter]').submit();
            }
        }.bind(this));
        if (advancedSearchButton = this.container.find('.advanced-search-link')) {
            advancedSearchButton.on('click', function (e) {
                e.stopPropagation();
                var a = e.target;
                if (a.prop('tagName') !== 'A') {
                    a = a.closest('a');
                }
                var url = a.href;
                url += '&listref=' + self.options.ref;
                this.windowopts = {
                    'id'           : 'advanced-search-win' + self.options.ref,
                    title          : Joomla.JText._('COM_FABRIK_ADVANCED_SEARCH'),
                    loadMethod     : 'xhr',
                    evalScripts    : true,
                    contentURL     : url,
                    width          : 710,
                    height         : 340,
                    y              : self.options.popwiny,
                    onContentLoaded: function (win) {
                        var list = Fabrik.blocks['list_' + self.options.ref];
                        if (list.length === 0) {
                            list = Fabrik.blocks[self.options.container];
                            self.options.advancedSearch.parentView = self.options.container;
                        }
                        list.advancedSearch = new AdvancedSearch(self.options.advancedSearch);
                        mywin.fitToContent(false);
                    }
                };
                var mywin = Fabrik.getWindow(self.windowopts);
            });
        }
    },

    getList: function () {
        this.list = Fabrik.blocks[this.options.type + '_' + this.options.ref];
        if (this.list.length === 0) {
            this.list = Fabrik.blocks[this.options.container];
        }
        return this.list;
    },

    addFilter: function (plugin, f) {
        if (this.filters.hasOwnProperty(plugin) === false) {
            this.filters[plugin] = [];
        }
        this.filters[plugin].push(f);
    },

    onSubmit: function () {
        if (this.filters.date) {
            jQuery.each(this.filters.date, function (key, f) {
                f.onSubmit();
            });
        }
    },

    onUpdateData: function () {
        if (this.filters.date) {
            jQuery.each(this.filters.date, function (key, f) {
                f.onUpdateData();
            });
        }
    },

    // $$$ hugh - added this primarily for CDD element, so it can get an array to
    // emulate submitted form data
    // for use with placeholders in filter queries. Mostly of use if you have
    // daisy chained CDD's.
    getFilterData: function () {
        var h = {};
        this.container.find('.fabrik_filter').each(function (f) {
            if ($(this).prop('id').test(/value$/)) {
                var key = $(this).prop('id').match(/(\S+)value$/)[1];
                // $$$ rob added check that something is select - possibly causes js
                // error in ie
                if ($(this).prop('tagName') === 'SELECT' && this.selectedIndex !== -1) {
                    h[key] = $('#' + this.options[this.selectedIndex]).text();
                } else {
                    h[key] = $(this).val();
                }
                h[key + '_raw'] = $(this).val();
            }
        });
        return h;
    },

    update: function () {
        $.each(this.filters, function (plugin, fs) {
            fs.each(function (f) {
                f.update();
            });
        });
    }
});