/**
 * Form
 *
 * @copyright: Copyright (C) 2005-2013, fabrikar.com - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

/*jshint mootools: true */
/*global Fabrik:true, fconsole:true, Joomla:true, CloneObject:true, $H:true,unescape:true */

var FbForm = my.Class({

    options: {
        'rowid'         : '',
        'admin'         : false,
        'ajax'          : false,
        'primaryKey'    : null,
        'error'         : '',
        'submitOnEnter' : false,
        'updatedMsg'    : 'Form saved',
        'pages'         : [],
        'start_page'    : 0,
        'ajaxValidation': false,
        'showLoader'    : false,
        'customJsAction': '',
        'plugins'       : [],
        'ajaxmethod'    : 'post',
        'inlineMessage' : true,
        'print'         : false,
        'images'        : {
            'alert'       : '',
            'action_check': '',
            'ajax_loader' : ''
        }
    },

    constructor: function (id, options) {
        // $$$ hugh - seems options.rowid can be null in certain corner cases, so defend against that
        if (typeof(options.rowid) === 'undefined') {
            options.rowid = '';
        }
        this.id = id;
        this.result = true; //set this to false in window.fireEvents to stop current action (e.g. stop form submission)
        this.options = $.extend(this.options, options);
        this.plugins = this.options.plugins;
        this.subGroups = {};
        this.currentPage = this.options.start_page;
        this.formElements = {};
        this.elements = this.formElements;
        this.duplicatedGroups = {};

        this.fx = {};
        this.fx.elements = [];
        this.fx.validations = {};
        this.setUpAll();
        (function () {
            this.duplicateGroupsToMin();
        }.bind(this)).delay(1000);

        // Delegated element events
        this.events = {};

        this.submitBroker = new FbFormSubmit();

        Fabrik.trigger('fabrik.form.loaded', [this]);
    },

    setUpAll: function () {
        var self = this;
        this.setUp();
        this.winScroller = new Fx.Scroll(window);
        if (this.form.length > 0) {
            if (this.options.ajax || this.options.submitOnEnter === false) {
                this.stopEnterSubmitting();
            }
            this.watchAddOptions();
        }

        jQuery.each(this.options.hiddenGroup, function (k, v) {
            if (v === true && $('#group' + k).length > 0) {
                var subGroup = $('#group' + k).find('.fabrikSubGroup');
                self.subGroups[k] = subGroup.cloneWithIds();
                self.hideLastGroup(k, subGroup);
            }
        });

        // get an int from which to start incrementing for each repeated group id
        // don't ever decrease this value when deleting a group as it will cause all sorts of
        // reference chaos with cascading dropdowns etc.
        this.repeatGroupMarkers = {};
        if (this.form) {
            this.form.find('.fabrikGroup').each(function () {
                var id = this.id.replace('group', '');
                var c = $(this).find('.fabrikSubGroup').length;
                //if no joined repeating data then c should be 0 and not 1
                if (c === 1) {
                    if ($(this).find('.fabrikSubGroupElements').css('display') === 'none') {
                        c = 0;
                    }
                }
                self.repeatGroupMarkers[id] = c;
            });
            this.watchGoBackButton();
        }

        this.watchPrintButton();
        this.watchPdfButton();
    },

    /**
     * Print button action - either open up the print preview window - or print if already opened
     */
    watchPrintButton: function () {
        var self = this;
        $('a[data-fabrik-print]').on('click', function (e) {
            e.stopPropagation();
            if (self.options.print) {
                window.print();
            } else {
                // Build URL as we could have changed the rowid via ajax pagination
                var url = 'index.php?option=com_' + Fabrik.package + '&view=details&tmpl=component&formid=' +
                        self.id + '&listid=' + self.options.listid + '&rowid=' + self.options.rowid + '&iframe=1&print=1',
                    opts = 'status=no,toolbar=no,scrollbars=yes,titlebar=no,menubar=no,resizable=yes,' +
                        'width=400,height=350,directories=no,location=no;';
                window.open(url, 'win2', opts);
            }
        });
    },

    /**
     * PDF button action.
     */
    watchPdfButton: function () {
        var self = this;
        $('*[data-role="open-form-pdf"]').on('click', function (e) {
            e.stopPropagation();
            // Build URL as we could have changed the rowid via ajax pagination
            window.location = 'index.php?option=com_' + Fabrik.package + '&view=details&formid=' +
                self.id + '&rowid=' + self.options.rowid + '&format=pdf';
        });
    },

    /**
     * Go back button in ajax pop up window should close the window
     */
    watchGoBackButton: function () {
        var winId = this.options.fabrik_window_id;
        if (this.options.ajax) {
            var goback = this._getButton('Goback');
            if (goback.length === 0) {
                return;
            }
            goback.on('click', function (e) {
                e.stopPropagation();
                if (Fabrik.Windows[winId]) {
                    Fabrik.Windows[winId].close();
                }
                else {
                    // $$$ hugh - http://fabrikar.com/forums/showthread.php?p=166140#post166140
                    window.history.back();
                }
            });
        }
    },

    watchAddOptions: function () {
        this.fx.addOptions = [];
        this.getForm().find('.addoption').each(function () {
            var d = $(this),
                a = d.closest('.fabrikElementContainer').find('.toggle-addoption');

            d.slideUp(500);
            a.on('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                d.slideToggle();
            });
        });
    },

    setUp: function () {
        this.form = this.getForm();
        this.watchGroupButtons();
        // Submit can appear in confirmation plugin even when readonly
        this.watchSubmit();
        this.createPages();
        this.watchClearSession();
    },

    getForm: function () {
        if (this.form === undefined) {
            this.form = $('#' + this.getBlock());
        }

        return this.form;
    },

    getBlock: function () {
        if (this.block === undefined) {
            this.block = this.options.editable === true ? 'form_' + this.id : 'details_' + this.id;
            if (this.options.rowid !== '') {
                this.block += '_' + this.options.rowid;
            }
        }

        return this.block;
    },

    /**
     * Attach an effect to an elements
     *
     * @param   {string}  id      Element or group to apply the fx TO, triggered from another element
     * @param   {string}  method  JS event which triggers the effect (click,change etc.)
     *
     * @return {bool} false if no element found or element fx
     */
    addElementFX: function (id, method) {
        var c, k, fxdiv;
        id = id.replace('fabrik_trigger_', '');
        // Paul - add sanity checking and error reporting
        if (id.slice(0, 6) === 'group_') {
            id = id.slice(6, id.length);
            k = id;
            c = $('#' + id);

            if (c.length === 0) {
                fconsole('Fabrik form::addElementFX: Group "' + id + '" does not exist.');
                return false;
            }
        } else if (id.slice(0, 8) === 'element_') {
            id = id.slice(8, id.length);
            k = 'element' + id;
            c = $('#' + id);
            if (!c) {
                fconsole('Fabrik form::addElementFX: Element "' + id + '" does not exist.');
                return false;
            }
            c = c.closest('.fabrikElementContainer');
            if (!c) {
                fconsole('Fabrik form::addElementFX: Element "' + id + '.fabrikElementContainer" does not exist.');
                return false;
            }
        } else {
            fconsole('Fabrik form::addElementFX: Not an element or group: ' + id);
            return false;
        }
        if (c.length > 0) {
            // c will be the <li> element - you can't apply fx's to this as it makes the
            // DOM squiffy with multi column rows, so get the li's content and put it
            // inside a div which is injected into c
            // apply fx to div rather than li - damn I'm good
            var tag = (c).prop('tagName');
            if (tag === 'LI' || tag === 'TD') {
                fxdiv = $('<div />').css({'width': '100%'}).append(c.getChildren());
                c.empty();
                fxdiv.inject(c);
            } else {
                fxdiv = c;
            }

            var opts = {
                duration  : 800,
                transition: Fx.Transitions.Sine.easeInOut
            };
            if (typeOf(this.fx.elements[k]) === 'null') {
                this.fx.elements[k] = {};
            }

            this.fx.elements[k].css = new Fx.Morph(fxdiv, opts);

            if (typeOf(fxdiv) !== 'null' && (method === 'slide in' || method === 'slide out' || method === 'slide toggle')) {
                this.fx.elements[k].slide = new Fx.Slide(fxdiv, opts);
            }

            return this.fx.elements[k];
        }
        return false;
    },

    /**
     * Create the fx key
     * @param {string} id Element id
     * @returns {string}
     * @private
     */
    _createFxKey: function (id) {
        var k;
        id = id.replace('fabrik_trigger_', '');
        if (id.slice(0, 6) === 'group_') {
            id = id.slice(6, id.length);
            // weird fix?
            if (id.slice(0, 6) === 'group_') {
                id = id.slice(6, id.length);
            }
            k = id;
        } else {
            id = id.slice(8, id.length);
            k = 'element' + id;
        }
        return k;
    },

    /**
     *
     * @param {string} id Element id
     * @returns {bool}
     * @private
     */
    _groupFx: function (id) {
        return id.replace('fabrik_trigger_', '').slice(0, 6) === 'group_';
    },

    /**
     * An element state has changed, so lets run any associated effects
     *
     * @param   string  id            Element id to run the effect on
     * @param   string  method        Method to run
     * @param   object  elementModel  The element JS object which is calling the fx, this is used to work ok which repeat group the fx is applied on
     */
    doElementFX: function (id, method, elementModel) {
        var k, groupfx, fx, fxElement;

        // Could be the source element is in a repeat group but the target is not.
        var target = this.formElements[id.replace('fabrik_trigger_element_', '')],
            targetInRepeat = true;
        if (target) {
            targetInRepeat = target.options.inRepeatGroup;
        }

        // Update the element id that we will apply the fx to to be that of the calling elementModels group
        // (if in a repeat group)
        if (elementModel && targetInRepeat) {
            if (elementModel.options.inRepeatGroup) {
                var bits = id.split('_');
                bits[bits.length - 1] = elementModel.options.repeatCounter;
                id = bits.join('_');
            }
        }
        k = this._createFxKey(id);
        groupfx = this._groupFx(id);

        // Get the stored fx
        fx = this.fx.elements[k];
        if (!fx) {
            // A group was duplicated but no element FX added, lets try to add it now
            fx = this.addElementFX('element_' + id, method);

            // If it wasn't added then lets get out of here
            if (!fx) {
                return;
            }
        }
        // Seems dropdown element fx.css.element is already the container
        if (groupfx || fx.css.element.hasClass('fabrikElementContainer')) {
            fxElement = fx.css.element;
        } else {
            fxElement = fx.css.element.closest('.fabrikElementContainer');
        }

        // For repeat groups rendered as tables we cant apply fx on td so get child
        if (fxElement.prop('tagName') === 'TD') {
            fxElement = fxElement.getChildren()[0];
        }
        switch (method) {
            case 'show':
                fxElement.fade('show').removeClass('fabrikHide');
                if (groupfx) {
                    $('#' + id).find('.fabrikinput').css('opacity', '1');
                }
                break;
            case 'hide':
                fxElement.fade('hide').addClass('fabrikHide');
                break;
            case 'fadein':
                fxElement.removeClass('fabrikHide');
                if (fx.css.lastMethod !== 'fadein') {
                    fx.css.element.show();
                    fx.css.start({'opacity': [0, 1]});
                }
                break;
            case 'fadeout':
                if (fx.css.lastMethod !== 'fadeout') {
                    fx.css.start({'opacity': [1, 0]}).chain(function () {
                        fx.css.element.hide();
                        fxElement.addClass('fabrikHide');
                    });
                }
                break;
            case 'slide in':
                fx.slide.slideIn();
                break;
            case 'slide out':
                fx.slide.slideOut();
                fxElement.removeClass('fabrikHide');
                break;
            case 'slide toggle':
                fx.slide.toggle();
                break;
            case 'clear':
                this.formElements[id].clear();
                break;
        }
        fx.lastMethod = method;
        Fabrik.trigger('fabrik.form.doelementfx', [this]);
    },

	/**
	 * Get a group's tab, if it exists
	 *
	 * These tab funcions are currently just helpers for user scripts
	 *
	 * @param groupId
	 *
	 * @return tab | false
	 */
	getGroupTab: function(groupid) {
		if (document.id('group' + groupid).getParent().hasClass('tab-pane')) {
			var tabid = document.id('group' + groupid).getParent().id;
			var tab_anchor = this.form.getElement('a[href=#' + tabid + ']');
			return tab_anchor.getParent();
		}
		return false;
	},

	/**
	 * Get a group's tab, if it exists
	 *
	 * These tab funcions are currently just helpers for user scripts
	 *
	 * @param groupId
	 *
	 * @return tab | false
	 */
	getGroupTab: function(groupid) {
		if (document.id('group' + groupid).getParent().hasClass('tab-pane')) {
			var tabid = document.id('group' + groupid).getParent().id;
			var tab_anchor = this.form.getElement('a[href=#' + tabid + ']');
			return tab_anchor.getParent();
		}
		return false;
	},

	/**
	 * Hide a group's tab, if it exists
	 *
	 * @param groupId
	 */
	hideGroupTab: function(groupid) {
		var tab = this.getGroupTab(groupid);
		if (tab !== false) {
			tab.hide();
			if (tab.hasClass('active')) {
				if (tab.getPrevious()) {
					jQuery(tab.getPrevious().getFirst()).tab('show');
				}
				else if (tab.getNext()) {
					jQuery(tab.getNext().getFirst()).tab('show');
				}
			}
		}
	},

	/**
	 * Hide a group's tab, if it exists
	 *
	 * @param groupId
	 */
	selectGroupTab: function(groupid) {
		var tab = this.getGroupTab(groupid);
		if (tab !== false) {
			if (!tab.hasClass('active')) {
				jQuery(tab.getFirst()).tab('show');
			}
		}
	},

	/**
	 * Hide a group's tab, if it exists
	 *
	 * @param groupId
	 */
	showGroupTab: function(groupid) {
		var tab = this.getGroupTab(groupid);
		if (tab !== false) {
			tab.show();
		}
	},

	watchClearSession: function () {
		var self = this;
		this.form.find('.clearSession').on('click', function (e) {
            e.stopPropagation();
			self.form.find('input[name=task]').val('removeSession');
			self.clearForm();
			self.form.submit();
		});
	},

    createPages: function () {
        var submit, p, firstGroup, tabDiv, self = this;
        if (this.isMultiPage()) {

            // Wrap each page in its own div
            jQuery.each(this.options.pages, function (i, page) {
                p = $(document.createElement('div'));
                p.attr({
                    'class': 'page',
                    'id'   : 'page_' + i
                });
                firstGroup = $('#group' + page[0]);
                if (firstGroup.length > 0) {

                    // Paul - Don't use pages if this is a bootstrap_tab form
                    tabDiv = firstGroup.closest('div');
                    if (tabDiv.length === 0 || tabDiv.hasClass('tab-pane')) {
                        return;
                    }
                    p.inject(firstGroup, 'before');
                    page.each(function (group) {
                        p.append($('#group' + group));
                    });
                }
            });
            submit = this._getButton('Submit');
            if (submit && this.options.rowid === '') {
                submit.prop('disabled', 'disabled');
                submit.css('opacity', 0.5);
            }
            if (this.form.find('.fabrikPagePrevious').length > 0) {
                this.form.find('.fabrikPagePrevious').prop('disabled', 'disabled');
                this.form.find('.fabrikPagePrevious').on('click', function (e) {
                    self._doPageNav(e, -1);
                });
            }
            if (typeOf(document.find('.fabrikPagePrevious')) !== 'null') {
                this.form.find('.fabrikPageNext').on('click', function (e) {
                    self._doPageNav(e, 1);
                });
            }
            this.setPageButtons();
            this.hideOtherPages();
        }
    },

    isMultiPage: function () {
        return Object.keys(this.options.pages).length > 1;
    },

    /**
     * Move forward/backwards in multipage form
     *
     * @param   event  e
     * @param   int    dir  1/-1
     */
    _doPageNav: function (e, dir) {
        var self = this, url;
        if (this.options.editable) {
            this.form.find('.fabrikMainError').addClass('fabrikHide');

            // If tip shown at bottom of long page and next page shorter we need to move the tip to
            // the top of the page to avoid large space appearing at the bottom of the page.
            $('.tool-tip').css('top', 0);

            // Don't prepend with Fabrik.liveSite,
            // as it can create cross origin browser errors if you are on www and livesite is not on www.
            url = 'index.php?option=com_fabrik&format=raw&task=form.ajax_validate&form_id=' + this.id;

            Fabrik.loader.start(this.getBlock(), Joomla.JText._('COM_FABRIK_VALIDATING'));

            var d = this.getFormData();
            d.task = 'form.ajax_validate';
            d.fabrik_ajax = '1';
            d.format = 'raw';

            d = this._prepareRepeatsForAjax(d);

            var myAjax = $.ajax({
                'url' : url,
                method: this.options.ajaxmethod,
                data  : d,

            }).done(function (r) {
                Fabrik.loader.stop(self.getBlock());
                r = JSON.decode(r);

                // Don't show validation errors if we are going back a page
                if (dir === -1 || self._showGroupError(r, d) === false) {
                    self.changePage(dir);
                    self.saveGroupsToDb();
                }
                new Fx.Scroll(window).toElement(this.form);
            });
        }
        else {
            this.changePage(dir);
        }
        e.stopPropagation();
    },

    saveGroupsToDb: function () {
        var self = this,
            orig = this.form.find('input[name=format]').value,
            origprocess = this.form.find('input[name=task]').value,
            url = 'index.php?option=com_fabrik&format=raw&page=' + this.currentPage,
            data;

        if (this.options.multipage_save === 0) {
            return;
        }
        Fabrik.trigger('fabrik.form.groups.save.start', [this]);
        if (this.result === false) {
            this.result = true;
            return;
        }
        this.form.find('input[name=format]').val('raw');
        this.form.find('input[name=task]').val('form.savepage');

        Fabrik.loader.start(this.getBlock(), 'saving page');
        data = this.getFormData();
        data.fabrik_ajax = 1;
        new $.ajax({
            url   : url,
            method: this.options.ajaxmethod,
            data  : data,
        }).done(function (r) {
                Fabrik.trigger('fabrik.form.groups.save.completed', [this]);
                if (self.result === false) {
                    self.result = true;
                    return;
                }
                self.form.find('input[name=format]').val(orig);
                self.form.find('input[name=task]').val(origprocess);
                if (self.options.ajax) {
                    Fabrik.trigger('fabrik.form.groups.save.end', [this, r]);
                }
                Fabrik.loader.stop(self.getBlock());
            });
    },

    changePage: function (dir) {
        this.changePageDir = dir;
        Fabrik.trigger('fabrik.form.page.change', [this, dir]);
        if (this.result === false) {
            this.result = true;
            return;
        }
        this.currentPage = parseInt(this.currentPage, 10);
        if (this.currentPage + dir >= 0 && this.currentPage + dir < Object.keys(this.options.pages).length) {
            this.currentPage = this.currentPage + dir;
            if (!this.pageGroupsVisible()) {
                this.changePage(dir);
            }
        }

        this.setPageButtons();
        $('#page_' + this.currentPage).css('display', '');
        this.hideOtherPages();
        Fabrik.trigger('fabrik.form.page.chage.end', [this, dir]);
        Fabrik.trigger('fabrik.form.page.change.end', [this, dir]);
        if (this.result === false) {
            this.result = true;
            return;
        }
    },

    pageGroupsVisible: function () {
        var visible = false;
        this.options.pages.get(this.currentPage).each(function (gid) {
            var group = $('#group' + gid);
            if (group.length > 0) {
                if (group.css('display') !== 'none') {
                    visible = true;
                }
            }
        });
        return visible;
    },

    /**
     * Hide all groups except those in the active page
     */
    hideOtherPages: function () {
        var page, currentPage = parseInt(this.currentPage, 10);
        this.options.pages.each(function (gids, i) {
            if (parseInt(i, 10) !== currentPage) {
                page = $('#page_' + i);
                if (page.length > 0) {
                    page.hide();
                }
            }
        });
    },

    setPageButtons: function () {
        var submit = this._getButton('Submit');
        var prev = this.form.find('.fabrikPagePrevious');
        var next = this.form.find('.fabrikPageNext');
        if (next.length > 0) {
            if (this.currentPage === Object.keys(this.options.pages).length - 1) {
                if (typeOf(submit) !== 'null') {
                    submit.prop('disabled', '');
                    submit.css('opacity', 1);
                }
                next.prop('disabled', 'disabled');
                next.css('opacity', 0.5);
            } else {
                if (submit.length > 0 && (this.options.rowid === '' || this.options.rowid.toString() === '0')) {
                    submit.prop('disabled', 'disabled');
                    submit.css('opacity', 0.5);
                }
                next.prop('disabled', '');
                next.css('opacity', 1);
            }
        }
        if (prev.length > 0) {
            if (this.currentPage === 0) {
                prev.prop('disabled', 'disabled');
                prev.css('opacity', 0.5);
            } else {
                prev.prop('disabled', '');
                prev.css('opacity', 1);
            }
        }
    },

    destroyElements: function () {
        jQuery.each(this.formElements, function () {
            $(this).destroy();
        });
    },

    /**
     * Add elements into the form
     *
     * @param  Hash  a  Elements to add.
     */
    addElements: function (a) {
        /*
         * Store the newly added elements so we can call attachedToForm only on new elements. Avoids issue with cdd in repeat groups
         * resetting themselves when you add a new group
         */
        var added = [], i, self = this;
        $.each(a, function (gid, elements) {
            elements.each(function (el) {
                if ($.isArray(el)) {
                    // Paul - check that element exists before adding it http://fabrikar.com/forums/index.php?threads/ajax-validation-never-ending-in-forms.36907
                    if ($('#' + el[1]).length === 0) {
                        fconsole('Fabrik form::addElements: Cannot add element "' + el[1] + '" because it does not exist in HTML.');
                        return;
                    }
                    var oEl = new window[el[0]](el[1], el[2]);
                    added.push(self.addElement(oEl, el[1], gid));
                }
                else if (typeof(el) === 'object') {
                    // Paul - check that element exists before adding it
                    // http://fabrikar.com/forums/index.php?threads/ajax-validation-never-ending-in-forms.36907
                    if ($('#' + el.options.element).length === 0) {
                        fconsole('Fabrik form::addElements: Cannot add element "' + el.options.element +
                            '" because it does not exist in HTML.');
                        return;
                    }
                    added.push(self.addElement(el, el.options.element, gid));
                }
                else if (el === undefined) {
                    fconsole('Fabrik form::addElements: Cannot add unknown element: ' + el);
                }
                else {
                    fconsole('Fabrik form::addElements: Cannot add null element.');
                }
            });
        });
        // $$$ hugh - moved attachedToForm calls out of addElement to separate loop, to fix forward reference issue,
        // i.e. calc element adding events to other elements which come after itself, which won't be in formElements
        // yet if we do it in the previous loop ('cos the previous loop is where elements get added to formElements)
        for (i = 0; i < added.length; i++) {
            if (typeOf(added[i]) !== 'null') {
                try {
                    added[i].attachedToForm();
                } catch (err) {
                    fconsole(added[i].options.element + ' attach to form:' + err);
                }
            }
        }
        Fabrik.trigger('fabrik.form.elements.added', [this]);
    },

    addElement: function (oEl, elId, gid) {
        //var oEl = new window[element[0]](element[1], element[2]);
        //elId = element[1];
        elId = oEl.getFormElementsKey(elId);
        elId = elId.replace('[]', '');

        var ro = elId.substring(elId.length - 3, elId.length) === '_ro';
        oEl.form = this;
        oEl.groupid = gid;
        this.formElements[elId] = oEl;
        Fabrik.trigger('fabrik.form.element.added', [this, elId, oEl]);
        if (ro) {
            elId = elId.substr(0, elId.length - 3);
            this.formElements[elId] = oEl;
        }
        this.submitBroker.addElement(elId, oEl);
        return oEl;
    },

    /**
     * Dispatch an event to an element
     *
     * @param   string  elementType  Deprecated
     * @param   string  elementId    Element key to look up in this.formElements
     * @param   string  action       Event change/click etc.
     * @param   mixed   js           String or function
     */

    dispatchEvent: function (elementType, elementId, action, js) {
        if (typeof(js) === 'string') {
            js = Encoder.htmlDecode(js);
        }
        var el = this.formElements[elementId];
        if (!el) {
            // E.g. db join rendered as chx
            var els = $.each(this.formElements, function (e) {
                if (elementId === e.baseElementId) {
                    el = e;
                }
            });
        }
        if (!el) {
            fconsole('Fabrik form::dispatchEvent: Cannot find element to add ' + action + ' event to: ' + elementId);
        }
        else if (js !== '') {
            el.addNewEvent(action, js);
        }
        else if (Fabrik.debug) {
            fconsole('Fabrik form::dispatchEvent: Javascript empty for ' + action + ' event on: ' + elementId);
        }
    },

    action: function (task, el) {
        var oEl = this.formElements[el];
        Browser.exec('oEl.' + task + '()');
    },

    triggerEvents: function (el) {
        this.formElements[el].fireEvents(arguments[1]);
    },

    /**
     * @param   string  id            Element id to observe
     * @param   string  triggerEvent  Event type to add
     */

    watchValidation: function (id, triggerEvent) {
        if (this.options.ajaxValidation === false) {
            return;
        }
        var el = $('#' + id),
            self = this;
        if (el.length === 0) {
            fconsole('Fabrik form::watchValidation: Could not add ' + triggerEvent + ' event because element "' + id + '" does not exist.');
            return;
        }
        if (el.hasClass('fabrikSubElementContainer')) {
            // check for things like radio buttons & checkboxes
            el.find('.fabrikinput').each(function () {
                $(this).on(triggerEvent, function (e) {
                    self.doElementValidation(e, true);
                });
            });
            return;
        }
        el.on(triggerEvent, function (e) {
            self.doElementValidation(e, false);
        });
    },

    // as well as being called from watchValidation can be called from other
    // element js actions, e.g. date picker closing
    doElementValidation: function (e, subEl, replacetxt) {
        var id, self = this;
        if (this.options.ajaxValidation === false) {
            return;
        }
        replacetxt = typeOf(replacetxt) === 'null' ? '_time' : replacetxt;
        if (typeOf(e) === 'event' || typeOf(e) === 'object' || typeOf(e) === 'domevent') { // type object in
            id = e.target.id;
            // for elements with subelements e.g. checkboxes radiobuttons
            if (subEl === true) {
                id = $(e.target).closest('.fabrikSubElementContainer').id;
            }
        } else {
            // hack for closing date picker where it seems the event object isn't
            // available
            id = e;
        }

        if ($('#' + id).length === 0) {
            return;
        }
        if ($('#' + id).prop('readonly') === true || $('#' + id).prop('readonly') === 'readonly') {
            // stops date element being validated
            // return;
        }
        var el = this.formElements[id];
        if (!el) {
            //silly catch for date elements you cant do the usual method of setting the id in the
            //fabrikSubElementContainer as its required to be on the date element for the calendar to work
            id = id.replace(replacetxt, '');
            el = this.formElements[id];
            if (!el) {
                return;
            }
        }
        Fabrik.trigger('fabrik.form.element.validation.start', [this, el, e]);
        if (this.result === false) {
            this.result = true;
            return;
        }
        el.setErrorMessage(Joomla.JText._('COM_FABRIK_VALIDATING'), 'fabrikValidating');

        var d = this.getFormData();
        d.task = 'form.ajax_validate';
        d.fabrik_ajax = '1';
        d.format = 'raw';

        d = this._prepareRepeatsForAjax(d);

        // $$$ hugh - nasty hack, because validate() in form model will always use _0 for
        // repeated id's
        var origid = id;
        if (el.origId) {
            origid = el.origId + '_0';
        }
        //var origid = el.origId ? el.origId : id;
        el.options.repeatCounter = el.options.repeatCounter ? el.options.repeatCounter : 0;
        var url = 'index.php?option=com_fabrik&form_id=' + this.id;
        $.ajax({
            url   : url,
            method: this.options.ajaxmethod,
            data  : d,
        }).done(function (e) {
            self._completeValidaton(e, id, origid);
        });
    },

    _completeValidaton: function (r, id, origid) {
        r = JSON.decode(r);
        if (r === null) {
            this._showElementError(['Oups'], id);
            this.result = true;
            return;
        }
        $.each(this.formElements, function (key, el) {
            el.afterAjaxValidation();
        });
        Fabrik.trigger('fabrik.form.element.validation.complete', [this, r, id, origid]);
        if (this.result === false) {
            this.result = true;
            return;
        }
        var el = this.formElements[id];
        if ((typeOf(r.modified[origid]) !== 'null')) {
            el.update(r.modified[origid]);
        }
        if (typeOf(r.errors[origid]) !== 'null') {
            this._showElementError(r.errors[origid][el.options.repeatCounter], id);
        } else {
            this._showElementError([], id);
        }
    },

    /**
     *
     * @param {object} d
     * @returns {object}
     * @private
     */
    _prepareRepeatsForAjax: function (d) {
        this.getForm();

        //data should be keyed on the data stored in the elements name between []'s which is the group id
        $.each(this.form.find('input[name^=fabrik_repeat_group]'),
            function () {
                // $$$ hugh - had a client with a table called fabrik_repeat_group, which was hosing up here,
                // so added a test to narrow the element name down a bit!
                if (this.id.test(/fabrik_repeat_group_\d+_counter/)) {
                    var c = this.name.match(/\[(.*)\]/)[1];
                    d['fabrik_repeat_group[' + c + ']'] = $(this).val();
                }
            }
        );
        return d;
    },

    _showGroupError: function (r, d) {
        var tmperr, self = this;
        var gids = Array.from(this.options.pages[parseInt(this.currentPage, 10)]);
        var err = false;
        jQuery.each(d, function (k, v) {
            k = k.replace(/\[(.*)\]/, '').replace(/%5B(.*)%5D/, '');// for dropdown validations
            if (self.formElements[k] !== undefined) {
                var el = self.formElements[k];
                if (gids.contains(parseInt(el.groupid, 10))) {
                    if (r.errors[k]) {
                        // prepare error so that it only triggers for real errors and not success
                        // msgs

                        var msg = '';
                        if (typeOf(r.errors[k]) !== 'null') {
                            msg = r.errors[k].flatten().join('<br />');
                        }
                        if (msg !== '') {
                            tmperr = self._showElementError(r.errors[k], k);
                            if (err === false) {
                                err = tmperr;
                            }
                        } else {
                            el.setErrorMessage('', '');
                        }
                    }
                    if (r.modified[k]) {
                        if (el) {
                            el.update(r.modified[k]);
                        }
                    }
                }
            }
        });

        return err;
    },

    _showElementError: function (r, id) {
        // r should be the errors for the specific element, down to its repeat group
        // id.
        var msg = '';
        if (typeOf(r) !== 'null') {
            msg = r.flatten().join('<br />');
        }
        var classname = (msg === '') ? 'fabrikSuccess' : 'fabrikError';
        if (msg === '') {
            msg = Joomla.JText._('COM_FABRIK_SUCCESS');
        }
        msg = '<span> ' + msg + '</span>';
        this.formElements[id].setErrorMessage(msg, classname);
        return (classname === 'fabrikSuccess') ? false : true;
    },

    updateMainError: function () {
        var myfx, activeValidations;
        var mainEr = this.form.find('.fabrikMainError');
        mainEr.html(this.options.error);
        activeValidations = this.form.find('.fabrikError').filter(
            function (e, index) {
                return !e.hasClass('fabrikMainError');
            });
        if (activeValidations.length > 0 && mainEr.hasClass('fabrikHide')) {
            this.showMainError(this.options.error);
        }
        if (activeValidations.length === 0) {
            this.hideMainError();
        }
    },

    hideMainError: function () {
        var mainEr = this.form.find('.fabrikMainError');
        new Fx.Tween(mainEr, {
            property  : 'opacity',
            duration  : 500,
            onComplete: function () {
                mainEr.addClass('fabrikHide');
            }
        }).start(1, 0);
    },

    showMainError: function (msg) {
        // If we  ajax validations are on - don't show main error as it makes the form 'jumpy'
        if (this.options.ajaxValidation) {
            return;
        }
        var mainEr = this.form.find('.fabrikMainError');
        mainEr.html(msg);
        mainEr.removeClass('fabrikHide');
        myfx = new Fx.Tween(mainEr, {
            property: 'opacity',
            duration: 500
        }).start(0, 1);
    },

    /** @since 3.0 get a form button name */
    _getButton: function (name) {
        if (!this.getForm()) {
            return;
        }
        var b = this.form.find('input[type=button][name=' + name + ']');
        if (!b) {
            b = this.form.find('input[type=submit][name=' + name + ']');
        }
        if (!b) {
            b = this.form.find('button[type=button][name=' + name + ']');
        }
        if (!b) {
            b = this.form.find('button[type=submit][name=' + name + ']');
        }
        return b;
    },

    watchSubmit: function () {
        var submit = this._getButton('Submit'),
            self = this;
        if (submit.length === 0) {
            return;
        }
        var apply = this._getButton('apply'),
            del = this._getButton('delete'),
            copy = this._getButton('Copy');
        if (del) {
            del.on('click', function (e) {
                if (window.confirm(Joomla.JText._('COM_FABRIK_CONFIRM_DELETE_1'))) {
                    var res = Fabrik.trigger('fabrik.form.delete', [self, self.options.rowid]).eventResults;
                    if (typeOf(res) === 'null' || res.length === 0 || !res.contains(false)) {
                        // Task value is the same for front and admin
                        this.form.find('input[name=task]').val('form.delete');
                        this.doSubmit(e, del);
                    } else {
                        e.stopPropagation();
                        return false;
                    }

                } else {
                    return false;
                }
            });
        }
        var submits = this.form.find('button[type=submit]').combine([apply, submit, copy]);
        submits.each(function () {
            var btn = $(this);
            btn.on('click', function (e) {
                self.doSubmit(e, btn);
            });
        });

        this.form.on('submit', function (e) {
            self.doSubmit(e);
        });
    },

    doSubmit: function (e, btn) {
        var self = this;
        if (this.submitBroker.enabled()) {
            e.stopPropagation();
            return false;
        }
        this.submitBroker.submit(function () {
            if (self.options.showLoader) {
                Fabrik.loader.start(self.getBlock(), Joomla.JText._('COM_FABRIK_LOADING'));
            }
            Fabrik.trigger('fabrik.form.submit.start', [self, e, btn]);
            if (self.result === false) {
                self.result = true;
                e.stopPropagation();
                Fabrik.loader.stop(self.getBlock());
                // Update global status error
                self.updateMainError();

                // Return otherwise ajax upload may still occur.
                return;
            }
            // Insert a hidden element so we can reload the last page if validation fails
            if (Object.keys(self.options.pages).length > 1) {
                var i = $(document.createElement('input')).attr({
                    'name' : 'currentPage',
                    'value': parseInt(self.currentPage, 10),
                    'type' : 'hidden'
                });
                self.form.append(i);
            }
            if (self.options.ajax) {
                // Do ajax val only if onSubmit val ok
                if (self.form) {
                    // if showLoader is enabled (for non AJAX submits) the loader will already have been shown up there ^^
                    if (!self.options.showLoader) {
                        Fabrik.loader.start(self.getBlock(), Joomla.JText._('COM_FABRIK_LOADING'));
                    }

                    // Get all values from the form
                    var data = self.getFormData();
                    data = self._prepareRepeatsForAjax(data);
                    data[btn.name] = btn.value;
                    if (btn.name === 'Copy') {
                        data.Copy = 1;
                        e.stopPropagation();
                    }
                    data.fabrik_ajax = '1';
                    data.format = 'raw';
                    $.getJSON({
                        'url'   : this.form.action,
                        'data'  : data,
                        'method': this.options.ajaxmethod,
                    })
                        .fail(function () {
                            Fabrik.loader.stop(this.getBlock(), 'Ajax failure');
                        })
                        .done(function (json, txt) {

                            // Process errors if there are some
                            var errfound = false;
                            if (json.errors !== undefined) {

                                // For every element of the form update error message
                                jQuery.each(json.errors, function (key, errors) {
                                    if (typeof(self.formElements[key]) !== 'undefined' && errors.flatten().length > 0) {
                                        errfound = true;
                                        if (self.formElements[key].options.inRepeatGroup) {
                                            for (e = 0; e < errors.length; e++) {
                                                if (errors[e].flatten().length > 0) {
                                                    var this_key = key.replace(/(_\d+)$/, '_' + e);
                                                    self._showElementError(errors[e], this_key);
                                                }
                                            }
                                        }
                                        else {
                                            self._showElementError(errors, key);
                                        }
                                    }
                                });
                            }
                            // Update global status error
                            self.updateMainError();

                            if (errfound === false) {
                                var clear_form = false;
                                if (self.options.rowid === '' && btn.name !== 'apply') {
                                    // We're submitting a new form - so always clear
                                    clear_form = true;
                                }
                                Fabrik.loader.stop(self.getBlock());
                                var savedMsg = (typeOf(json.msg) !== 'null' && json.msg !== undefined && json.msg !== '') ? json.msg : Joomla.JText._('COM_FABRIK_FORM_SAVED');
                                if (json.baseRedirect !== true) {
                                    clear_form = json.reset_form;
                                    if (json.url !== undefined) {
                                        if (json.redirect_how === 'popup') {
                                            var width = json.width ? json.width : 400,
                                                height = json.height ? json.height : 400,
                                                x_offset = json.x_offset ? json.x_offset : 0,
                                                y_offset = json.y_offset ? json.y_offset : 0,
                                                title = json.title ? json.title : '';
                                            Fabrik.getWindow({
                                                'id'      : 'redirect',
                                                'type'    : 'redirect',
                                                contentURL: json.url,
                                                caller    : self.getBlock(),
                                                'height'  : height,
                                                'width'   : width,
                                                'offset_x': x_offset,
                                                'offset_y': y_offset,
                                                'title'   : title
                                            });
                                        }
                                        else {
                                            if (json.redirect_how === 'samepage') {
                                                window.open(json.url, '_self');
                                            }
                                            else if (json.redirect_how === 'newpage') {
                                                window.open(json.url, '_blank');
                                            }
                                        }
                                    } else {
                                        if (!json.suppressMsg) {
                                            window.alert(savedMsg);
                                        }
                                    }
                                } else {
                                    clear_form = json.reset_form !== undefined ? json.reset_form : clear_form;
                                    if (!json.suppressMsg) {
                                        window.alert(savedMsg);
                                    }
                                }
                                // Query the list to get the updated data
                                Fabrik.trigger('fabrik.form.submitted', [this, json]);
                                if (btn.name !== 'apply') {
                                    if (clear_form) {
                                        self.clearForm();
                                    }
                                    // If the form was loaded in a Fabrik.Window close the window.
                                    if (Fabrik.Windows[self.options.fabrik_window_id]) {
                                        Fabrik.Windows[self.options.fabrik_window_id].close();
                                    }
                                }
                            } else {
                                Fabrik.trigger('fabrik.form.submit.failed', [self, json]);
                                // Stop spinner
                                Fabrik.loader.stop(self.getBlock(), Joomla.JText._('COM_FABRIK_VALIDATION_ERROR'));
                            }
                        });
                }
            }
            Fabrik.trigger('fabrik.form.submit.end', [self]);
            if (self.result === false) {
                self.result = true;
                e.stopPropagation();
                // Update global status error
                self.updateMainError();
            } else {
                // Enables the list to clean up the form and custom events
                if (self.options.ajax) {
                    e.stopPropagation();
                    Fabrik.trigger('fabrik.form.ajax.submit.end', [self]);
                } else {
                    // Inject submit button name/value.
                    if (btn.length > 0) {
                        $(document.createElement('input')).attr({
                            type : 'hidden',
                            name : btn.name,
                            value: btn.value
                        }).inject(self.form);
                        self.form.submit();
                    } else {
                        // Regular button pressed which seems to be triggering form.submit() method.
                        e.stopPropagation();
                    }
                }
            }
        });
        e.stopPropagation();
    },

    /**
     * Used to get the querystring data and
     * for any element overwrite with its own data definition
     * required for empty select lists which return undefined as their value if no
     * items available
     *
     * @param  bool  submit  Should we run the element onsubmit() methods - set to false in calc element
     */

    getFormData: function (submit) {
        submit = typeOf(submit) !== 'null' ? submit : true;
        if (submit) {
            jQuery(this.formElements, function (key, el) {
                el.onsubmit();
            });
        }
        this.getForm();
        var s = this.form.toQueryString();
        var h = {};
        s = s.split('&');
        var arrayCounters = {};
        s.each(function (p) {
            p = p.split('=');
            var k = p[0];
            // $$$ rob deal with checkboxes
            // Ensure [] is not encoded
            k = decodeURI(k);
            if (k.substring(k.length - 2) === '[]') {
                k = k.substring(0, k.length - 2);
                if (!arrayCounters.hasOwnProperty(k)) {
                    // rob for ajax validation on repeat element this is required to be set to 0
                    arrayCounters[k] = 0;
                } else {
                    arrayCounters[k] = arrayCounters[k] + 1;
                }
                k = k + '[' + arrayCounters[k] + ']';
            }
            h[k] = p[1];
        });

        // toQueryString() doesn't add in empty data - we need to know that for the
        // validation on multipages
        var elKeys = Object.keys(this.formElements);
        jQuery.each(this.formElements, function (key, el) {
            //fileupload data not included in querystring
            if (el.plugin === 'fabrikfileupload') {
                h[key] = el.val();
            }
            if (typeOf(h[key]) === 'null') {
                // search for elementname[*] in existing data (search for * as datetime
                // elements aren't keyed numerically)
                var found = false;
                jQuery.each(h, function (dataKey, val) {
                    dataKey = unescape(dataKey); // 3.0 ajax submission [] are escaped
                    dataKey = dataKey.replace(/\[(.*)\]/, '');
                    if (dataKey === key) {
                        found = true;
                    }
                }.bind(this));
                if (!found) {
                    h[key] = '';
                }
            }
        }.bind(this));
        return h;
    },

    // $$$ hugh - added this, so far only used by cascading dropdown JS
    // to populate 'data' for the AJAX update, so custom cascade 'where' clauses
    // can use {placeholders}. Initially tried to use getFormData for this, but because
    // it adds ALL the query string args from the page, the AJAX call from cascade ended
    // up trying to submit the form. So, this func does what the commented out code in
    // getFormData used to do, and only fetches actual form element data.

    getFormElementData: function () {
        var h = {};
        jQuery.each(this.formElements, function (key, el) {
            if (el.element) {
                h[key] = el.getValue();
                h[key + '_raw'] = h[key];
            }
        });
        return h;
    },

    watchGroupButtons: function () {
        var self = this;
        this.form.on('click', '.deleteGroup', function (e) {
            e.preventDefault();
            var group = $(this).closest('.fabrikGroup'),
                subGroup = $(this).closest('.fabrikSubGroup');
            self.deleteGroup(e, group, subGroup);
        });

        this.form.on('click:relay(.addGroup)', function (e) {
            e.preventDefault();
            self.duplicateGroup(e);
        });

        this.form.on('click', '.fabrikSubGroup', function (e) {
            var r = $(this).find('.fabrikGroupRepeater');
            if (r.length > 0) {
                $(this).on('mouseenter', function (e) {
                    r.fade(1);
                });
                $(this).on('mouseleave', function (e) {
                    r.fade(0.2);
                });
            }
        });
    },

    /**
     * When editing a new form and when min groups set we need to duplicate each group
     * by the min repeat value.
     */
    duplicateGroupsToMin: function () {
        var self = this;
        if (!this.form) {
            return;
        }

        Fabrik.trigger('fabrik.form.group.duplicate.min', [this]);

        $.each(this.options.group_repeats, function (canRepeat, groupId) {

            if (self.options.minRepeat[groupId] === undefined) {
                return;
            }

            if (parseInt(canRepeat, 10) !== 1) {
                return;
            }

            var repeatCounter = self.form.find('#fabrik_repeat_group_' + groupId + '_counter'),
                repeatRows, repeatReal, add_btn, deleteButton, i, repeat_id_0, deleteEvent;

            if (repeatCounter.length === 0) {
                return;
            }

            repeatRows = repeatReal = parseInt(repeatCounter.val(), 10);

            if (repeatRows === 1) {
                repeat_id_0 = self.form.find('#' + self.options.group_pk_ids[groupId] + '_0');

                if (repeat_id_0.val() === '') {
                    repeatReal = 0;
                }
            }

            var min = parseInt(self.options.minRepeat[groupId], 10);

            /**
             * $$$ hugh - added ability to override min count
             * http://fabrikar.com/forums/index.php?threads/how-to-initially-show-repeat-group.32911/#post-170147
             * $$$ hugh - trying out min of 0 for Troester
             * http://fabrikar.com/forums/index.php?threads/how-to-start-a-new-record-with-empty-repeat-group.34666/#post-175408
             * $$$ paul - fixing min of 0 for Jaanus
             * http://fabrikar.com/forums/index.php?threads/couple-issues-with-protostar-template.35917/
             **/
            if (min === 0 && repeatReal === 0) {

                // Create mock event
                deleteButton = self.form.find('#group' + groupId + ' .deleteGroup');
                deleteEvent = deleteButton.length !== 0 ? jQuery.Event('click') : false;
                var group = self.form.find('#group' + groupId),
                    subGroup = group.find('.fabrikSubGroup');
                // Remove only group
                self.deleteGroup(deleteEvent, group, subGroup);

            }
            else if (repeatRows < min) {
                // Create mock event
                add_btn = self.form.find('#group' + groupId + ' .addGroup');
                if (add_btn.length > 0) {
                    var add_e = jQuery.Event('click');

                    // Duplicate group
                    for (i = repeatRows; i < min; i++) {
                        self.duplicateGroup(add_e);
                    }
                }
            }
        });
    },

    /**
     * Delete an repeating group
     *
     * @param e
     * @param group
     */
    deleteGroup: function (e, group, subGroup) {
        var self = this;
        Fabrik.trigger('fabrik.form.group.delete', [this, e, group]);
        if (this.result === false) {
            this.result = true;
            return;
        }
        if (e) {
            e.stopPropagation();
        }

        // Find which repeat group was deleted
        var delIndex = 0;
        group.find('.deleteGroup').each(function (b, x) {
            if ($(this).find('img') === $(e.target) || $(this).find('i') === $(e.target) || $(this) === $(e.target)) {
                delIndex = x;
            }
        });
        var i = group.id.replace('group', '');

        var repeats = parseInt($('#fabrik_repeat_group_' + i + '_counter').val(), 10);
        if (repeats <= this.options.minRepeat[i] && this.options.minRepeat[i] !== 0) {
            if (this.options.minMaxErrMsg[i] !== '') {
                var errorMessage = this.options.minMaxErrMsg[i];
                errorMessage = errorMessage.replace(/\{min\}/, this.options.minRepeat[i]);
                errorMessage = errorMessage.replace(/\{max\}/, this.options.maxRepeat[i]);
                window.alert(errorMessage);
            }
            return;
        }

        delete this.duplicatedGroups.i;
        if ($('#fabrik_repeat_group_' + i + '_counter').val() === '0') {
            return;
        }
        var subgroups = group.find('.fabrikSubGroup');

        this.subGroups[i] = subGroup.clone();
        if (subgroups.length <= 1) {
            this.hideLastGroup(i, subGroup);
            Fabrik.trigger('fabrik.form.group.delete.end', [this, e, i, delIndex]);
        } else {
            var toel = subGroup.getPrevious();
            new Fx.Tween(subGroup, {
                'property': 'opacity',
                duration  : 300,
                onComplete: function () {
                    if (subgroups.length > 1) {
                        subGroup.remove();
                    }

                    $.each(self.formElements, function (k, e) {
                        if (typeOf(e.element) !== 'null') {
                            if ($('#' + e.element.id).length === 0) {
                                e.decloned(i);
                                delete self.formElements[k];
                            }
                        }
                    });

                    // Minus the removed group
                    subgroups = group.find('.fabrikSubGroup');
                    var nameMap = {};
                    $.each(self.formElements, function (k, e) {
                        if (e.groupid === i) {
                            nameMap[k] = e.decreaseName(delIndex);
                        }
                    });
                    // ensure that formElements' keys are the same as their object's ids
                    // otherwise delete first group, add 2 groups - ids/names in last
                    // added group are not updated
                    $.each(nameMap, function (oldKey, newKey) {
                        if (oldKey !== newKey) {
                            self.formElements[newKey] = self.formElements[oldKey];
                            delete self.formElements[oldKey];
                        }
                    });
                    Fabrik.trigger('fabrik.form.group.delete.end', [self, e, i, delIndex]);
                }
            }).start(1, 0);
            if (toel) {
                // Only scroll the window if the previous element is not visible
                var win_scroll = $('#' + window).getScroll().y;
                var obj = toel.getCoordinates();
                // If the top of the previous repeat goes above the top of the visible
                // window,
                // scroll down just enough to show it.
                if (obj.top < win_scroll) {
                    var new_win_scroll = obj.top;
                    this.winScroller.start(0, new_win_scroll);
                }
            }
        }
        // Update the hidden field containing number of repeat groups
        var newCounter = parseInt($('#fabrik_repeat_group_' + i + '_counter').val(), 10) - 1;
        $('#fabrik_repeat_group_' + i + '_counter').val(newCounter);
        // $$$ hugh - no, mustn't decrement this!  See comment in setupAll
        this.repeatGroupMarkers[i] = this.repeatGroupMarkers[i] - 1;
        this.setRepeatGroupIntro(group, i);
    },

    hideLastGroup: function (groupid, subGroup) {
        var sge = subGroup.find('.fabrikSubGroupElements'),
            div = $(document.createElement('div')).attr({'class': 'fabrikNotice alert'}),
            notice = div.text(Joomla.JText._('COM_FABRIK_NO_REPEAT_GROUP_DATA'));
        if (sge.length === 0) {
            sge = subGroup;
            var add = sge.find('.addGroup');
            var lastth = sge.closest('table').find('thead th').find();
            if (add.length > 0) {
                add.inject(lastth);
            }
        }
        sge.css('display', 'none');
        notice.inject(sge, 'after');
    },

    isFirstRepeatSubGroup: function (group) {
        var subgroups = group.find('.fabrikSubGroup');
        return subgroups.length === 1 && group.find('.fabrikNotice');
    },

    getSubGroupToClone: function (groupid) {
        var group = $('#group' + groupid);
        var subgroup = group.find('.fabrikSubGroup');
        if (!subgroup) {
            subgroup = this.subGroups[groupid];
        }

        var clone = null;
        var found = false;
        if (this.duplicatedGroups.hasOwnProperty(groupid)) {
            found = true;
        }
        if (!found) {
            clone = subgroup.cloneNode(true);
            this.duplicatedGroups[groupid] = clone;
        } else {
            if (!subgroup) {
                clone = this.duplicatedGroups[groupid];
            } else {
                clone = subgroup.cloneNode(true);
            }
        }
        return clone;
    },

    repeatGetChecked: function (group) {
        // /stupid fix for radio buttons loosing their checked value
        var tocheck = [];
        group.find('.fabrikinput').each(function (i) {
            if (this.type === 'radio' && $(this).prop('checked')) {
                tocheck.push(i);
            }
        });
        return tocheck;
    },

    /**
     * Duplicates the groups sub group and places it at the end of the group
     *
     * @param {event} e Click event
     */
    duplicateGroup: function (e) {
        var subElementContainer, container, self = this;
        Fabrik.trigger('fabrik.form.group.duplicate', [this, e]);
        if (this.result === false) {
            this.result = true;
            return;
        }
        if (e) {
            e.stopPropagation();
        }
        var i = $(e.target).closest('.fabrikGroup').id.replace('group', '');
        var group_id = parseInt(i, 10);
        var group = $('#group' + i);
        var c = this.repeatGroupMarkers[i];
        var repeats = parseInt($('#fabrik_repeat_group_' + i + '_counter').val(), 10);
        if (repeats >= this.options.maxRepeat[i] && this.options.maxRepeat[i] !== 0) {
            if (this.options.minMaxErrMsg[i] !== '') {
                var errorMessage = this.options.minMaxErrMsg[i];
                errorMessage = errorMessage.replace(/\{min\}/, this.options.minRepeat[i]);
                errorMessage = errorMessage.replace(/\{max\}/, this.options.maxRepeat[i]);
                window.alert(errorMessage);
            }
            return;
        }
        $('#fabrik_repeat_group_' + i + '_counter').val(repeats + 1);

        if (this.isFirstRepeatSubGroup(group)) {
            var subgroups = group.find('.fabrikSubGroup');
            // user has removed all repeat groups and now wants to add it back in
            // remove the 'no groups' notice

            var sub = subgroups[0].find('.fabrikSubGroupElements');
            if (sub.length === 0) {
                group.find('.fabrikNotice').remove();
                sub = subgroups[0];

                // Table group
                var add = group.find('.addGroup');
                add.inject(sub.find('td.fabrikGroupRepeater'));
                sub.css('display', '');
            } else {
                subgroups[0].find('.fabrikNotice').remove();
                subgroups[0].find('.fabrikSubGroupElements').show();
            }

            this.repeatGroupMarkers[i] = this.repeatGroupMarkers[i] + 1;
            return;
        }

        var clone = this.getSubGroupToClone(i);
        var tocheck = this.repeatGetChecked(group);


        if (group.find('table.repeatGroupTable')) {
	        if (group.find('table.repeatGroupTable tbody')) {
		        group = group.find('table.repeatGroupTable tbody');
	        }
            group.appendChild(clone);
        } else {
            group.appendChild(clone);
        }

        tocheck.each(function (i) {
            $(this).prop('checked', true);
        });

        this.subelementCounter = 0;
        // Remove values and increment ids
        var newElementControllers = [],
            hasSubElements = false,
            inputs = clone.find('.fabrikinput'),
            lastinput = null;
        $.each(this.formElements, function (index, el) {
            var formElementFound = false;
            subElementContainer = null;
            var subElementCounter = -1;
            $.each(function (input) {

                hasSubElements = el.hasSubElements();

                container = $(this).closest('.fabrikSubElementContainer');
                var testid = (hasSubElements && container) ? container.id : this.id;
                var cloneName = el.getCloneName();

				// Test ===, plus special case for join rendered as auto-complete
				if (testid === cloneName || testid === cloneName + '-auto-complete') {
                    lastinput = $(this);
					formElementFound = true;

                    if (hasSubElements) {
                        subElementCounter++;
                        subElementContainer = $(this).closest('.fabrikSubElementContainer');

                        // Clone the first inputs event to all subelements
                        // $$$ hugh - sanity check in case we have an element which has no input
                        if ($('#' + testid).find('input')) {
                            $(this).cloneEvents($('#' + testid).find('input'));
                        }
                        // Note: Radio's etc. now have their events delegated from the form - so no need to duplicate them

                    } else {
                        $(this).cloneEvents(el.element);

                        // Update the element id use el.element.id rather than input.id as
                        // that may contain _1 at end of id
                        var bits = Array.from(el.element.id.split('_'));
                        bits.splice(bits.length - 1, 1, c);
                        this.id = bits.join('_');

                        // Update labels for non sub elements
                        var l = $(this).closest('.fabrikElementContainer').find('label');
                        l.prop('for', input.id);
                    }
                    if (this.name !== undefined) {
                        this.name = this.name.replace('[0]', '[' + c + ']');
                    }
                }
            });

            if (formElementFound) {
                if (hasSubElements && subElementContainer.length > 0) {
                    // if we are checking subelements set the container id after they have all
                    // been processed
                    // otherwise if check only works for first subelement and no further
                    // events are cloned

                    // $$$ rob fix for date element
                    var bits = el.options.element.split('_');
                    bits.splice(bits.length - 1, 1, c);
                    subElementContainer.id = bits.join('_');
                }
                // clone js element controller, set form to be passed by reference and
                // not cloned
                var ignore = el.unclonableProperties();
                var newEl = new CloneObject(el, true, ignore);

                newEl.container = null;
                newEl.options.repeatCounter = c;

                // This seems to be wrong, as it'll set origId to the repeat ID with the _X appended.
                //newEl.origId = origelid;

                if (hasSubElements && typeOf(subElementContainer) !== 'null') {
                    newEl.element = $('#' + subElementContainer);
                    newEl.cloneUpdateIds(subElementContainer.id);
                    newEl.options.element = subElementContainer.id;
                    newEl._getSubElements();
                } else {
                    newEl.cloneUpdateIds(lastinput.id);
                }
                //newEl.reset();
                newElementControllers.push(newEl);
            }
        });

        newElementControllers.each(function (newEl) {
            newEl.cloned(c);
            // $$$ hugh - moved reset() from end of loop above, otherwise elements with un-cloneable object
            // like maps end up resetting the wrong map to default values.  Needs to run after element has done
            // whatever it needs to do with un-cloneable object before resetting.
            // $$$ hugh - adding new option to allow copying of the existing element values when copying
            // a group, instead of resetting to default value.  This means knowing what the group PK element
            // is, do we don't copy that value.  hence new group_pk_ids[] array, which gives us the PK element
            // name in regular full format, which we need to test against the join string name.
            //var pk_re = new RegExp('\\[' + this.options.group_pk_ids[group_id] + '\\]');
            var pk_re = new RegExp(self.options.group_pk_ids[group_id]);
            if (!self.options.group_copy_element_values[group_id] || (self.options.group_copy_element_values[group_id] && newEl.element.name && newEl.element.name.test(pk_re))) {
                // Call reset method that resets both events and value back to default.
                newEl.reset();
            }
            else {
                // Call reset method that only resets the events, not the value
                newEl.resetEvents();
            }
        });
        var o = {};
        o[i] = newElementControllers;
        this.addElements(o);

        // Only scroll the window if the new element is not visible
        var win_size = window.getHeight(),
            win_scroll = $('#' + window).getScroll().y,
            obj = clone.getCoordinates();
        // If the bottom of the new repeat goes below the bottom of the visible
        // window,
        // scroll up just enough to show it.
        if (obj.bottom > (win_scroll + win_size)) {
            var new_win_scroll = obj.bottom - win_size;
            this.winScroller.start(0, new_win_scroll);
        }

        var myFx = new Fx.Tween(clone, {
            'property': 'opacity',
            duration  : 500
        }).set(0);

        clone.fade(1);
        // $$$ hugh - added groupid (i) and repeatCounter (c) as args
        // note I commented out the increment of c a few lines above//duplicate
        Fabrik.trigger('fabrik.form.group.duplicate.end', [this, e, i, c]);

        this.setRepeatGroupIntro(group, i);
        this.repeatGroupMarkers[i] = this.repeatGroupMarkers[i] + 1;
    },

    /**
     * Set the repeat group intro text
     * @param group
     * @param groupId
     */
    setRepeatGroupIntro: function (group, groupId) {
        var intro = this.options.group_repeat_intro[groupId],
            tmpIntro = '',
            targets = group.find('*[data-role="group-repeat-intro"]');

		targets.each(function (target, i) {
			tmpIntro = intro.replace('{i}', i + 1);
			// poor man's parseMsgForPlaceholder ... ignore elements in joined groups.
			this.formElements.each(function (el) {
				if (!el.options.inRepeatGroup) {
					var re = new RegExp('\{' + el.element.id + '\}');
					// might should do a match first, to avoid always calling getValue(), just not sure which is more overhead!
					tmpIntro = tmpIntro.replace(re, el.getValue());
				}
			});
            $(this).html(tmpIntro);
		});
	},

    update: function (o) {
        var self = this;
        Fabrik.trigger('fabrik.form.update', [this, o.data]);
        if (this.result === false) {
            this.result = true;
            return;
        }
        var leaveEmpties = arguments[1] || false;
        var data = o.data;
        this.getForm();
        if (this.form) { // test for detailed view in module???
            var rowidel = this.form.find('input[name=rowid]');
            if (data.rowid) {
                rowidel.val(data.rowid);
            }
        }
        jQuery.each(this.formElements, function (key, el) {
            // if updating from a detailed view with prev/next then data's key is in
            // _ro format
            if (data[key] === undefined) {
                if (key.substring(key.length - 3, key.length) === '_ro') {
                    key = key.substring(0, key.length - 3);
                }
            }
            // this if stopped the form updating empty fields. Element update()
            // methods
            // should test for null
            // variables and convert to their correct values
            // if (data[key]) {
            if (data[key] === undefined) {
                // only update blanks if the form is updating itself
                // leaveEmpties set to true when this form is called from updateRows
                if (o.id === self.id && !leaveEmpties) {
                    el.update('');
                }
            } else {
                el.update(data[key]);
            }
        });
    },

    reset: function () {
        this.addedGroups.each(function (subgroup) {
            var group = $('#' + subgroup).closest('fabrikGroup'),
                i = group.prop('id').replace('group', ''),
                newCounter = parseInt($('#fabrik_repeat_group_' + i + '_counter').val(), 10) - 1;
            $('#fabrik_repeat_group_' + i + '_counter').val(newCounter);
            subgroup.remove();
        });
        this.addedGroups = [];
        Fabrik.trigger('fabrik.form.reset', [this]);
        if (this.result === false) {
            this.result = true;
            return;
        }
        $.each(this.formElements, function (key, el) {
            el.reset();
        });
    },

    showErrors: function (data) {
        var d = null, e, x, y,
            mainError = this.form.find('.fabrikMainError'),
            errors = data.errors;
        if (data.id === this.id) {
            // Show errors
            if (Object.keys(errors).length > 0) {
                mainError.html(this.options.error);
                mainError.removeClass('fabrikHide');
                errors.each(function (a, key) {
                    e = $('#' + key + '_error');
                    if (e.length > 0) {
                        for (x = 0; x < a.length; x++) {
                            for (y = 0; y < a[x].length; y++) {
                                d = $(document.createElement('div')).text(a[x][y]).inject(e);
                            }
                        }
                    } else {
                        fconsole(key + '_error' + ' not found (form show errors)');
                    }
                });
            }
        }
    },

    /** add additional data to an element - e.g database join elements */
    appendInfo: function (data) {
        $.each(this.formElements, function (key, el) {
            if (el.appendInfo) {
                el.appendInfo(data, key);
            }
        });
    },

    clearForm: function () {
        var self = this;
        this.getForm();
        if (!this.form) {
            return;
        }
        $.each(this.formElements, function (key, el) {
            if (key === this.options.primaryKey) {
                self.form.find('input[name=rowid]').value = '';
            }
            el.update('');
        });
        // reset errors
        this.form.find('.fabrikError').empty();
        this.form.find('.fabrikError').addClass('fabrikHide');
    },

    stopEnterSubmitting: function () {
        var self = this;
        var inputs = this.form.find('input.fabrikinput');
        inputs.each(function (i) {
            $(this).on('keypress', function (e) {
                if (e.key === 'enter') {
                    e.stopPropagation();
                    if (inputs[i + 1]) {
                        inputs[i + 1].focus();
                    }
                    //last one?
                    if (i === inputs.length - 1) {
                        self._getButton('Submit').focus();
                    }
                }
            });
        });
    },

    getSubGroupCounter: function (group_id) {

    }
});
