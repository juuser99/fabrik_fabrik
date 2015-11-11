/**
 * Auto-Complete
 *
 * @copyright: Copyright (C) 2005-2015, fabrikar.com - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

/*jshint mootools: true */
/*global Fabrik:true, fconsole:true, Joomla:true, CloneObject:true, $H:true,unescape:true */

var FbAutocomplete = my.Class({

    options: {
        menuclass              : 'auto-complete-container',
        classes                : {
            'ul': 'results',
            'li': 'result'
        },
        url                    : 'index.php',
        max                    : 10,
        onSelection            : Class.empty,
        autoLoadSingleResult   : true,
        minTriggerChars        : 1,
        storeMatchedResultsOnly: false // Only store a value if selected from picklist
    },

    initialize: function (element, options) {
        var self = this;
        this.matchedResult = false;
        this.options = $.extend(this.options, options);
        element = element.replace('-auto-complete', '');
        var autoComplete = $('#' + element + '-auto-complete');
        this.options.labelelement = autoComplete.length === 0 ? $(element + '-auto-complete') : autoComplete;
        this.cache = {};
        this.selected = -1;
        this.mouseinsde = false;
        $(document).on('keydown', function (e) {
            self.doWatchKeys(e);
        });
        var testElement = $('#' + element);
        this.element = testElement.length === 0 ? $(element) : testElement;
        this.buildMenu();
        if (!this.getInputElement()) {
            fconsole('autocomplete didn\'t find input element');
            return;
        }
        this.getInputElement().prop('autocomplete', 'off');
        this.getInputElement().on('keyup', function (e) {
            self.search(e);
        });

        this.getInputElement().on('blur', function (e) {
            if (self.options.storeMatchedResultsOnly) {
                if (!self.matchedResult) {
                    if (self.data === undefined || !(self.data.length === 1 && self.options.autoLoadSingleResult)) {
                        self.element.value = '';
                    }
                }
            }
        });
    },

    /**
     * Should the auto-complete start its ajax search
     * @param   e  Event
     * @return  bool
     */
    canSearch: function (e) {
        if (!this.isMinTriggerlength()) {
            return false;
        }
        if (e.key === 'tab' || e.key === 'enter') {
            e.stop();
            this.closeMenu();
            return false;
        }
        return true;
    },

    /**
     * Get the input text element's value and if empty set this.element.value to empty
     *
     * @return  string  input element text
     */
    defineSearchValue: function () {
        var v = this.getInputElement().val();
        if (v === '') {
            this.element.value = '';
        }
        return v;
    },

    search: function (e) {
        if (!this.canSearch(e)) {
            return;
        }
        this.matchedResult = false;
        var v = this.getInputElement().val();
        if (v === '') {
            this.element.value = '';
        }
        if (v !== this.searchText && v !== '') {
            if (this.options.storeMatchedResultsOnly === false) {
                this.element.value = v;
            }
            this.positionMenu();
            if (this.cache[v]) {
                this.populateMenu(this.cache[v]);
                this.openMenu();
            } else {
                if (this.ajax) {
                    this.closeMenu();
                    this.ajax.cancel();
                }

                var data = {value: v};
                this.ajax = this.makeAjax(this.options.url, data);
            }
        }
        this.searchText = v;
    },

    /**
     * Build the ajax Request object and send it.
     */
    makeAjax: function (url, data) {
        var self = this;
        return $.ajax({
            url        : url,
            data       : data
        }).beforeSend(function () {
            Fabrik.loader.start(self.getInputElement());
        }).success(function (e) {
            this.completeAjax(e, data.value);
        }).fail(function () {
            Fabrik.loader.stop(this.getInputElement());
        }).done(function () {
            Fabrik.loader.stop(self.getInputElement());
        });
    },

    completeAjax: function (r, v) {
        Fabrik.loader.stop(this.getInputElement());
        r = JSON.decode(r);
        this.cache[v] = r;
        this.populateMenu(r);
        this.openMenu();
    },

    buildMenu: function () {
        var self = this;
        this.menu = $(document.createElement('div')).attr({
            'class' : this.options.menuclass,
            'styles': {'position': 'absolute'}
        }).adopt($(document.createElement('ul')).attr({'class': this.options.classes.ul}));
        this.menu.inject(document.body);
        this.menu.on('mouseenter', function () {
            self.mouseinsde = true;
        });
        this.menu.on('mouseleave', function () {
            self.mouseinsde = false;
        });
    },

    getInputElement: function () {
        return this.options.labelelement ? this.options.labelelement : this.element;
    },

    positionMenu: function () {
        var coords = this.getInputElement().getCoordinates();
        this.menu.setStyles({'left': coords.left, 'top': (coords.top + coords.height) - 1, 'width': coords.width});
    },

    populateMenu: function (data) {
        // $$$ hugh - added decoding of things like &amp; in the text strings
        data.map(function (item, index) {
            item.text = Encoder.htmlDecode(item.text);
            return item;
        });
        this.data = data;
        var max = this.getListMax(),
            self = this,
            ul = this.menu.find('ul');
        ul.empty();
        if (data.length === 1 && this.options.autoLoadSingleResult) {
            this.matchedResult = true;
            this.element.value = data[0].value;
            this.trigger('selection', [this, this.element.value]);
        }
        for (var i = 0; i < max; i++) {
            var pair = data[i];
            var li = new Element('li', {
                'data-value': pair.value,
                'class'     : 'unselected ' + this.options.classes.li
            }).text(pair.text);
            li.inject(ul);
            li.on('click', function (e) {
                e.stop();
                self.makeSelection(e.target);
            });
        }
        if (data.length > this.options.max) {
            $(document.createElement('li')).text('....').inject(ul);
        }
    },

    /**
     * @param {JQuery} li
     */
    makeSelection: function (li) {
        // $$$ tom - make sure an item was selected before operating on it.
        if (li.length !== 0) {
            this.getInputElement().val(li.text());
            this.element.value = li.data('value');

            this.matchedResult = true;
            this.closeMenu();
            this.trigger('selection', [this, this.element.value]);
            // $$$ hugh - need to fire change event, in case it's something like a join element
            // with a CDD that watches it.
            this.element.trigger('change', jQuery.Event('change'), 700);
            // $$$ hugh - fire a Fabrik event, just for good luck.  :)
            Fabrik.trigger('fabrik.autocomplete.selected', [this, this.element.value]);
        } else {
            //  $$$ tom - fire a notselected event to let developer take appropriate actions.
            Fabrik.trigger('fabrik.autocomplete.notselected', [this, this.element.value]);
        }
    },

    closeMenu: function () {
        var self = this;
        if (this.shown) {
            this.shown = false;
            this.menu.fade('out');
            this.selected = -1;
            $(document).removeEvent('click', function (e) {
                self.doTestMenuClose(e);
            });
        }
    },

    openMenu: function () {
        var self = this;
        if (!this.shown) {
            if (this.isMinTriggerlength()) {
                this.shown = true;
                this.menu.css('visibility', 'visible').fade('in');
                $(document).on('click', function (e) {
                    self.doTestMenuClose(e);
                });
                this.selected = 0;
                this.highlight();
            }
        }
    },

    doTestMenuClose: function () {
        if (!this.mouseinsde) {
            this.closeMenu();
        }
    },

    isMinTriggerlength: function () {
        var v = this.getInputElement().val();
        return v.length >= this.options.minTriggerChars;
    },

    getListMax: function () {
        if (this.data === undefined) {
            return 0;
        }
        return this.data.length > this.options.max ? this.options.max : this.data.length;
    },

    /**
     * Observe the keydown event on the input field. Should stop the loader as we have a new search query
     */
    doWatchKeys: function (e) {
        if (document.activeElement !== this.getInputElement()) {
            return;
        }
        Fabrik.loader.stop(this.getInputElement());
        var max = this.getListMax();
        if (!this.shown) {
            if (parseInt(e.code, 10) === 13) {
                e.stop();
            }
            if (parseInt(e.code, 10) === 40) {
                this.openMenu();
            }
        } else {
            if (!this.isMinTriggerlength()) {
                e.stop();
                this.closeMenu();
            }
            else {
                if (e.key === 'enter' || e.key === 'tab') {
                    $(window).trigger('blur');
                }
                switch (e.code) {
                    case 40://down
                        if (!this.shown) {
                            this.openMenu();
                        }
                        if (this.selected + 1 < max) {
                            this.selected++;
                            this.highlight();
                        }
                        e.stop();
                        break;
                    case 38: //up
                        if (this.selected - 1 >= -1) {
                            this.selected--;
                            this.highlight();
                        }
                        e.stop();
                        break;
                    case 13://enter
                    case 9://tab
                        e.stop();
                        var selectEvnt = jQuery.Event('click');
                        this.makeSelection(selectEvnt);
                        break;
                    case 27://escape
                        e.stop();
                        this.matchedResult = false;
                        this.closeMenu();
                        break;
                }
            }
        }
    },

    getSelected: function () {
        var a = this.menu.find('li').filter(function (li, i) {
            return i === this.selected;
        }.bind(this));
        return a[0];
    },

    highlight: function () {
        this.matchedResult = true;
        var self = this;
        this.menu.find('li').each(function (i) {
            if (i === self.selected) {
                $(this).addClass('selected');
            } else {
                $(this).removeClass('selected');
            }
        }.bind(this));
    }

});

var FabCddAutocomplete = my.Class(FbAutocomplete, {

    Extends: FbAutocomplete,

    search: function (e) {
        if (!this.canSearch(e)) {
            return;
        }
        var key,
            v = this.defineSearchValue();
        if (v !== this.searchText && v !== '') {
            var observer = $('#' + this.options.observerid);
            if (observer.length !== 0) {
                key = observer.val() + '.' + v;
            } else {
                this.parent(e);
                return;
            }
            this.positionMenu();
            if (this.cache[key]) {
                this.populateMenu(this.cache[key]);
                this.openMenu();
            } else {
                if (this.ajax) {
                    this.closeMenu();
                    this.ajax.cancel();
                }

                // If you are observing a radio list then you need to get the Element js plugin value
                var obsValue = $('#' + this.options.observerid).val();
                if (obsValue.length === 0) {
                    obsValue = Fabrik.getBlock(this.options.formRef).elements.get(this.options.observerid).val();
                }
                var data = {value: v, fabrik_cascade_ajax_update: 1, v: obsValue};
                this.ajax = this.makeAjax(this.options.url, data);
            }
        }
        this.searchText = v;
    }
});