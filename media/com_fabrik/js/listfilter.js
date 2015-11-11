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
        var advancedSearchButton;
        this.filters = {};
        this.options = $.extend(this.options, options);
        this.advancedSearch = false;
        this.container = document.id(this.options.container);
        this.filterContainer = this.container.find('.fabrikFilterContainer');
        this.filtersInHeadings = this.container.find('.listfilter');
        var b = this.container.find('.toggleFilters');
        b.on('click', function (e) {
            var dims = b.getPosition();
            e.stopPropagation();
            var x = dims.x - this.filterContainer.getWidth();
            var y = dims.y + b.getHeight();
            this.filterContainer.toggle();
            this.filtersInHeadings.toggle();
        }.bind(this));

        this.filterContainer.hide();
        this.filtersInHeadings.toggle();

        if (typeOf(this.container) === 'null') {
            return;
        }
        this.getList();
        var c = this.container.find('.clearFilters');
        c.removeEvents();
        c.addEvent('click', function (e) {
            var plugins;
            e.stopPropagation();

            // Reset the filter fields that contain previously selected values
            this.container.find('.fabrik_filter').each(function (f) {
                if (f.name.contains('[value]') || f.name.contains('fabrik_list_filter_all') || f.hasClass('autocomplete-trigger')) {
                    if (f.get('tag') === 'select') {
                        f.selectedIndex = f.get('multiple') ? -1 : 0;
                    } else {
                        if (f.get('type') === 'checkbox') {
                            f.checked = false;
                        } else {
                            f.value = '';
                        }
                    }
                }
            });
            plugins = this.getList().plugins;
            if (typeOf(plugins) !== 'null') {
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
                url += '&listref=' + this.options.ref;
                this.windowopts = {
                    'id'           : 'advanced-search-win' + this.options.ref,
                    title          : Joomla.JText._('COM_FABRIK_ADVANCED_SEARCH'),
                    loadMethod     : 'xhr',
                    evalScripts    : true,
                    contentURL     : url,
                    width          : 710,
                    height         : 340,
                    y              : this.options.popwiny,
                    onContentLoaded: function (win) {
                        var list = Fabrik.blocks['list_' + this.options.ref];
                        if (typeOf(list) === 'null') {
                            list = Fabrik.blocks[this.options.container];
                            this.options.advancedSearch.parentView = this.options.container;
                        }
                        list.advancedSearch = new AdvancedSearch(this.options.advancedSearch);
                        mywin.fitToContent(false);
                    }.bind(this)
                };
                var mywin = Fabrik.getWindow(this.windowopts);
            }.bind(this));
        }
    },

    getList: function () {
        this.list = Fabrik.blocks[this.options.type + '_' + this.options.ref];
        if (typeOf(this.list) === 'null') {
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
            if (f.id.test(/value$/)) {
                var key = f.id.match(/(\S+)value$/)[1];
                // $$$ rob added check that something is select - possibly causes js
                // error in ie
                if (f.get('tag') === 'select' && f.selectedIndex !== -1) {
                    h[key] = document.id(f.options[f.selectedIndex]).get('text');
                } else {
                    h[key] = f.get('value');
                }
                h[key + '_raw'] = f.get('value');
            }
        }.bind(this));
        return h;
    },

    update: function () {
        jQuery.each(this.filters, function (plugin, fs) {
            fs.each(function (f) {
                f.update();
            }.bind(this));
        }.bind(this));
    }
});