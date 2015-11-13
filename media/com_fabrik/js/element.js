/**
 * Element
 *
 * @copyright: Copyright (C) 2005-2013, fabrikar.com - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

/*jshint mootools: true */
/*global Fabrik:true, fconsole:true, Joomla:true, CloneObject:true, $H:true,unescape:true,Asset:true */

var FbElement = my.Class({

    options: {
        element      : null,
        defaultVal   : '',
        value        : '',
        label        : '',
        editable     : false,
        isJoin       : false,
        joinId       : 0,
        inRepeatGroup: false
    },

    /**
     * Constructor
     * @param {string} element
     * @param {object} options
     * @returns {*}
     */
    constructor: function (element, options) {
        var self = this;
        this.plugin = '';
        options.element = element;
        this.strElement = element;
        this.loadEvents = []; // need to store these for use if the form is reset
        this.events = {}; // was changeEvents
        this.options = $.extend(this.options, options);
        // If this element is a 'chosen' select, we need to relay the jQuery change event to Moo
        if ($('#' + this.options.element + '_chzn')) {
            var changeEvent = this.getChangeEvent();
            jQuery('#' + this.options.element).on('change', {changeEvent: changeEvent}, function (event) {
                $('#' + self.id).trigger(event.data.changeEvent, new jQuery.Event(event.data.changeEvent));
            });
        }
        return this.setElement();
    },

    /**
     * Called when form closed in ajax window
     * Should remove any events added to Window or Fabrik
     */
    destroy: function () {

    },

    setElement: function () {
        if ($('#' + this.options.element)) {
            this.element = $('#' + this.options.element);
            this.setorigId();
            return true;
        }
        return false;
    },

    get: function (v) {
        if (v === 'value') {
            return this.getValue();
        }
    },

    /**
     * Sets the element key used in Fabrik.blocks.form_X.formElements
     * Overwritten by any element which performs a n-n join (multi ajax fileuploads, dbjoins as checkboxes)
     *
     * @since   3.0.7
     *
     * @return  string
     */
    getFormElementsKey: function (elId) {
        this.baseElementId = elId;
        return elId;
    },

    attachedToForm: function () {
        this.setElement();
        this.alertImage = $(document.createElement('i')).addClass(this.form.options.images.alert);
        this.successImage = $(document.createElement('i')).addClass('icon-checkmark')
            .css({'color': 'green'});

        //put ini code in here that can't be put in initialize()
        // generally any code that needs to refer to  this.form, which
        //is only set when the element is assigned to the form.
    },

    /** allows you to fire an array of events to element /  subelements, used in calendar to trigger js events when the calendar closes **/
    fireEvents: function (evnts) {
        var self = this;
        if (this.hasSubElements()) {
            this._getSubElements().each(function (el) {
                Array.from(evnts).each(function (e) {
                    el.trigger(e);
                });
            });
        } else {
            Array.from(evnts).each(function (e) {
                if (self.element) {
                    self.element.trigger(e);
                }
            });
        }
    },

    find: function () {
        if (this.element.length === 0) {
            this.element = $('#' + this.options.element);
        }
        return this.element;
    },

    //used for elements like checkboxes or radio buttons
    _getSubElements: function () {
        var element = this.find();
        if (element.length === 0) {
            return false;
        }
        this.subElements = element.find('.fabrikinput');
        return this.subElements;
    },

    hasSubElements: function () {
        this._getSubElements();
        if ($.isArray(this.subElements)) {
            return this.subElements.length > 0 ? true : false;
        }
        return false;
    },

    unclonableProperties: function () {
        return ['form'];
    },

    /**
     * Set names/ids/elements etc. when the elements group is cloned
     *
     * @param   int  id  element id
     * @since   3.0.7
     */
    cloneUpdateIds: function (id) {
        this.element = $('#' + id);
        this.options.element = id;
    },

    runLoadEvent: function (js, delay) {
        delay = delay ? delay : 0;
        //should use eval and not Browser.exec to maintain reference to 'this'
        if (typeof(js) === 'function') {
            js.delay(delay);
        } else {
            if (delay === 0) {
                eval(js);
            } else {
                (function () {
                    console.log('delayed calling runLoadEvent for ' + delay);
                    eval(js);
                }.bind(this)).delay(delay);
            }
        }
    },

    /**
     * called from list when ajax form closed
     * fileupload needs to remove its onSubmit event
     * otherwise 2nd form submission will use first forms event
     */
    removeCustomEvents: function () {
    },

    /**
     * Was renewChangeEvents() but don't see why change events should be treated
     * differently to other events?
     *
     * @since 3.0.7
     */
    renewEvents: function () {
        var self = this;
        jQuery.each(this.events, function (type, fns) {
            self.element.off(type);
            fns.each(function (js) {
                self.addNewEventAux(type, js);
            });
        });
    },

    addNewEventAux: function (action, js) {
        this.element.on(action, function (e) {
            // Don't stop event - means fx's onchange events wouldn't fire.
            typeOf(js) === 'function' ? js.delay(0, this, this) : eval(js);
        }.bind(this));
    },

    addNewEvent: function (action, js) {
        if (action === 'load') {
            this.loadEvents.push(js);
            this.runLoadEvent(js);
        } else {
            if (!this.element) {
                this.element = $('#' + this.strElement);
            }
            if (this.element) {
                if (!Object.keys(this.events).contains(action)) {
                    this.events[action] = [];
                }
                this.events[action].push(js);
                this.addNewEventAux(action, js);
            }
        }
    },

    // Alias to addNewEvent.
    addEvent: function (action, js) {
        this.addNewEvent(action, js);
    },

    validate: function () {
    },

    //store new options created by user in hidden field
    addNewOption: function (val, label) {
        var a,
            additions = $('#' + this.options.element + '_additions'),
            added = additions.val(),
            json = {'val': val, 'label': label},
            s = '[', i;
        a = added !== '' ? JSON.decode(added) : [];
        a.push(json);
        for (i = 0; i < a.length; i++) {
            s += JSON.encode(a[i]) + ',';
        }
        s = s.substring(0, s.length - 1) + ']';
        additions.val(s);
    },

    getLabel: function () {
        return this.options.label;
    },

    /**
     * set the label (uses textContent attribute, prolly won't work on IE < 9)
     */
    setLabel: function (label) {
        this.options.label = label;
        var c = this.getLabelElement();
        if (c.length > 0) {
            c[0].textContent = label;
        }
    },

    /**
     * Update the element's value
     * @param {string|object|array} val
     */
    update: function (val) {
        //have to call find() - otherwise inline editor doesn't work when editing 2nd row of data.
        if (this.find()) {
            if (this.options.editable) {
                this.element.val(val);
            } else {
                this.element.html(val);
            }
        }
    },

    /**
     * $$$ hugh - testing something for join elements, where in some corner cases,
     * like reverse Geocoding in the map element, we need to update elements that might be
     * joins, and all we have is the label (like "Austria" for country).  So am overriding this
     * new function in the join element, with code that finds the first occurrence of the label,
     * and sets the value accordingly.  But all we need to do here is make it a wrapper for update().
     */
    updateByLabel: function (label) {
        this.update(label);
    },

    // Alias to update()
    set: function (val) {
        this.update(val);
    },

    getValue: function () {
        if (this.element) {
            if (this.options.editable) {
                return this.element.value;
            } else {
                return this.options.value;
            }
        }
        return false;
    },

    reset: function () {
        this.resetEvents();
        if (this.options.editable === true) {
            this.update(this.options.defaultVal);
        }
    },

    resetEvents: function () {
        this.loadEvents.each(function (js) {
            this.runLoadEvent(js, 100);
        }.bind(this));
    },

    clear: function () {
        this.update('');
    },

    /**
     * Called from FbFormSubmit
     *
     * @params   function  cb  Callback function to run when the element is in an
     *                         acceptable state for the form processing to continue
     *                         Should use cb(true) to allow for the form submission,
     *                         cb(false) stops the form submission.
     *
     * @return  void
     */
    onsubmit: function (cb) {
        if (cb) {
            cb(true);
        }
    },

    /**
     * As ajax validations call onsubmit to get the correct date, we need to
     * reset the date back to the display date when the validation is complete
     */
    afterAjaxValidation: function () {

    },

    /**
     * Run when the element is cloned in a repeat group
     * @param {number} c
     */
    cloned: function (c) {
        var self = this;
        this.renewEvents();
        if (this.element.hasClass('chzn-done')) {
            this.element.removeClass('chzn-done');
            this.element.addClass('chzn-select');
            this.element.parent().find('.chzn-container').destroy();
            $('#' + this.element.id).chosen();
            var changeEvent = this.getChangeEvent();
            $('#' + this.options.element).on('change', {changeEvent: changeEvent}, function (event) {
                $('#' + self.id).trigger(event.data.changeEvent, jQuery.Event(event.data.changeEvent));
            });
        }
    },

    /**
     * Run when the element is decloled from the form as part of a deleted repeat group
     */
    decloned: function (groupid) {
    },

    /**
     * Fet the wrapper dom element that contains all of the elements dom objects
     * @return {jQuery}
     */
    getContainer: function () {
        return this.element.closest('.fabrikElementContainer');
    },

    /**
     * get the dom element which shows the error messages
     * @return {jQuery}
     */
    getErrorElement: function () {
        return this.getContainer().find('.fabrikErrorMessage');
    },

    /**
     * get the dom element which contains the label
     * @return {jQuery}
     */
    getLabelElement: function () {
        return this.getContainer().find('.fabrikLabel');
    },

    /**
     * Get the fx to fade up/down element validation feedback text
     */
    getValidationFx: function () {
        if (!this.validationFX) {
            this.validationFX = new Fx.Morph(this.getErrorElement()[0], {duration: 500, wait: true});
        }
        return this.validationFX;
    },

    /**
     * Get all tips attached to the element
     *
     * @return array of tips
     */
    tips: function () {
        return Fabrik.tips.elements.filter(function (t) {
            if (t === this.getContainer() || t.parent() === this.getContainer()) {
                return true;
            }
        }.bind(this));
    },

    /**
     * In 3.1 show error messages in tips - avoids jumpy pages with ajax validations
     */
    addTipMsg: function (msg, klass) {
        // Append notice to tip
        klass = klass ? klass : 'error';
        var ul, a, t = this.tips();
        if (t.length === 0) {
            return;
        }
        t = jQuery(t[0]);

        if (t.attr(klass) === undefined) {
            t.data('popover').show();
            t.attr(klass, msg);
            a = t.data('popover').tip().find('.popover-content');

            var d = $('<div>');
            d.html(a.html());
            var li = $('<li>').addClass(klass);
            li.html(msg);
            $('<i>').addClass(this.form.options.images.alert).inject(li, 'top');
            d.find('ul').append(li);
            t.data('content', unescape(d.get('html')));
            t.data('popover').setContent();
            t.data('popover').options.content = d.get('html');
            t.data('popover').hide();
        }
    },

    /**
     * In 3.1 show/hide error messages in tips - avoids jumpy pages with ajax validations
     */
    removeTipMsg: function (index) {
        var klass = klass ? klass : 'error',
            a,
            t = this.tips();
        t = jQuery(t[0]);
        if (t.attr(klass) !== undefined) {
            t.data('popover').show();
            a = t.data('popover').tip().find('.popover-content');
            var d = $(document.createElement('div')).html(a.html());
            var li = d.find('li.error');
            if (li) {
                li.destroy();
            }
            t.data('content', d.get('html'));
            t.data('popover').setContent();
            t.data('popover').options.content = d.get('html');
            t.data('popover').hide();
            t.removeAttr(klass);
        }
    },

    setErrorMessage: function (msg, classname) {
        var a, i;
        var classes = ['fabrikValidating', 'fabrikError', 'fabrikSuccess'];
        var container = this.getContainer();
        if (container === false) {
            console.log('Notice: couldn not set error msg for ' + msg + ' no container class found');
            return;
        }
        classes.each(function (c) {
            classname === c ? container.addClass(c) : container.removeClass(c);
        });
        var errorElements = this.getErrorElement();
        errorElements.each(function (e) {
            e.empty();
        });
        switch (classname) {
            case 'fabrikError':
                Fabrik.loader.stop(this.element);
                this.addTipMsg(msg);
                container.removeClass('success').removeClass('info').addClass('error');

                // If tmpl has additional error message divs (e.g labels above) then set html msg there
                if (errorElements.length > 1) {
                    for (i = 1; i < errorElements.length; i++) {
                        errorElements[i].html(msg);
                    }
                }

                break;
            case 'fabrikSuccess':
                container.addClass('success').removeClass('info').removeClass('error');
                Fabrik.loader.stop(this.element);
                this.removeTipMsg();

                break;
            case 'fabrikValidating':
                container.removeClass('success').addClass('info').removeClass('error');
                //errorElements[0].adopt(this.loadingImage);
                Fabrik.loader.start(this.element, msg);
                break;
        }

        this.getErrorElement().removeClass('fabrikHide');
        var parent = this.form;
        if (classname === 'fabrikError' || classname === 'fabrikSuccess') {
            parent.updateMainError();
        }

        var fx = this.getValidationFx();
        switch (classname) {
            case 'fabrikValidating':
            case 'fabrikError':
                fx.start({
                    'opacity': 1
                });
                break;
            case 'fabrikSuccess':
                fx.start({
                    'opacity': 1
                }).chain(function () {
                    // Only fade out if its still the success message
                    if (container.hasClass('fabrikSuccess')) {
                        container.removeClass('fabrikSuccess');
                        this.start.delay(700, this, {
                            'opacity'   : 0,
                            'onComplete': function () {
                                container.addClass('success').removeClass('error');
                                parent.updateMainError();
                                classes.each(function (c) {
                                    container.removeClass(c);
                                });
                            }
                        });
                    }
                });
                break;
        }
    },

    setorigId: function () {
        if (this.options.inRepeatGroup) {
            var e = this.options.element;
            this.origId = e.substring(0, e.length - 1 - this.options.repeatCounter.toString().length);
        }
    },

    /**
     * Decrease name
     * @param {int} delIndex
     * @returns {*}
     */
    decreaseName: function (delIndex) {
        var element = this.find(),
            self = this;
        if (element.length === 0) {
            return false;
        }
        if (this.hasSubElements()) {
            this._getSubElements().each(function (e) {
                this.name = self._decreaseName(e.name, delIndex);
                this.id = self._decreaseId(e.id, delIndex);
            });
        } else {
            if (this.element.name !== undefined) {
                this.element.name = this._decreaseName(this.element.name, delIndex);
            }
        }
        if (this.element.id !== undefined) {
            this.element.id = this._decreaseId(this.element.id, delIndex);
        }
        return this.element.id;
    },

    /**
     * @param {string  n         name to decrease
     * @param {int}    delIndex  delete index
     * @param {string} suffix    name suffix to keep (used for db join auto-complete element)
     */
    _decreaseId: function (n, delIndex, suffix) {
        var suffixFound = false;
        suffix = suffix ? suffix : false;
        if (suffix !== false) {
            if (n.contains(suffix)) {
                n = n.replace(suffix, '');
                suffixFound = true;
            }
        }
        var bits = n.split('_');
        var i = bits.getLast();
        if (typeOf(parseInt(i, 10)) === 'null') {
            return bits.join('_');
        }
        if (i >= 1 && i > delIndex) {
            i--;
        }
        bits.splice(bits.length - 1, 1, i);
        var r = bits.join('_');
        if (suffixFound) {
            r += suffix;
        }
        this.options.element = r;
        return r;
    },

    /**
     * @param {string} n         name to decrease
     * @param {int}    delIndex  delete index
     * @param {string} suffix    name suffix to keep (used for db join auto-complete element)
     */
    _decreaseName: function (n, delIndex, suffix) {
        suffix = suffix ? suffix : false;
        var suffixFound = false;
        if (suffix !== false) {
            if (n.contains(suffix)) {
                n = n.replace(suffix, '');
                suffixFound = true;
            }
        }
        var namebits = n.split('[');
        var i = parseInt(namebits[1].replace(']', ''), 10);
        if (i >= 1 && i > delIndex) {
            i--;
        }
        i = i + ']';

        namebits[1] = i;
        var r = namebits.join('[');
        if (suffixFound) {
            r += suffix;
        }
        return r;
    },

    /**
     * determine which duplicated instance of the repeat group the
     * element belongs to, returns false if not in a repeat group
     * other wise an integer
     */
    getRepeatNum: function () {
        if (this.options.inRepeatGroup === false) {
            return false;
        }
        return this.element.prop('id').split('_').pop();
    },

    getBlurEvent: function () {
        return this.element.prop('tagName') === 'SELECT' ? 'change' : 'blur';
    },

    getChangeEvent: function () {
        return 'change';
    },

    select: function () {
    },
    focus : function () {
    },

    hide: function () {
        var c = this.getContainer();
        if (c) {
            c.hide();
        }
    },

    show: function () {
        var c = this.getContainer();
        if (c) {
            c.show();
        }
    },

    toggle: function () {
        var c = this.getContainer();
        if (c) {
            c.toggle();
        }
    },

    /**
     * Used to find element when form clones a group
     * WYSIWYG text editor needs to return something specific as options.element has to use name
     * and not id.
     */
    getCloneName: function () {
        return this.options.element;
    },

    /**
     * Testing some stuff to try and get maps to display properly when they are in the
     * tab template.  If a map is in a tab which isn't selected on page load, the map
     * will not render properly, and needs to be refreshed when the tab it is in is selected.
     * NOTE that this stuff is very specific to the Fabrik tabs template, using J!'s tabs.
     */

    doTab: function (event) {
        (function () {
            this.redraw();
        }.bind(this)).delay(500);
    },

    /**
     * Tabs mess with element positioning - some element (googlemaps, file upload) need to redraw themselves
     * when the tab is clicked
     */
    watchTab      : function () {
        var a, tab_dl,
            self = this;
        var tab_div = this.element.closest('.tab-pane');
        if (tab_div) {
            a = $('a[href$=#' + tab_div.id + ']');
            tab_dl = a.closest('ul.nav');
            tab_dl.on('click', 'a', function (event) {
                self.doTab(event);
            });
        }
    },
    /**
     * When a form/details view is updating its own data, then should we use the raw data or the html?
     * Raw is used for cdd/db join elements
     *
     * @returns {boolean}
     */
    updateUsingRaw: function () {
        return false;
    }
});

/**
 * @author Rob
 * contains methods that are used by any element which manipulates files/folders
 */


var FbFileElement = my.Class(FbElement, {

    ajaxFolder: function () {
        var self = this;
        this.folderlist = [];
        if (this.element.length === 0) {
            return;
        }
        var el = this.element.closest('.fabrikElement');
        this.breadcrumbs = el.find('.breadcrumbs');
        this.folderdiv = el.find('.folderselect');

        this.folderdiv.slideUp(500, function () {
            // Animation complete.
        });
        this.hiddenField = el.find('.folderpath');
        el.find('.toggle').addEvent('click', function (e) {
            e.stopPropagation();
            self.slider.toggle();
        });
        this.watchAjaxFolderLinks();
    },


    watchAjaxFolderLinks: function () {
        var self = this;
        this.folderdiv.find('a').on('click', function (e) {
            self.browseFolders(e);
        });
        this.breadcrumbs.find('a').on('click', function (e) {
            self.useBreadcrumbs(e);
        });
    },


    browseFolders: function (e) {
        var text = $(e.target).text();
        e.stopPropagation();
        this.folderlist.push(text);
        var dir = this.options.dir + this.folderlist.join(this.options.ds);
        this.addCrumb(text);
        this.doAjaxBrowse(dir);
    },

    useBreadcrumbs: function (e) {
        e.stopPropagation();
        var self = this;
        var c = e.target.className;
        this.folderlist = [];
        this.breadcrumbs.find('a').each(function (link) {
            if (link.className !== c) {
                self.folderlist.push($(e.target).html());
            }
        });

        var home = [this.breadcrumbs.find('a').shift().clone(),
            this.breadcrumbs.find('span').shift().clone()];
        this.breadcrumbs.empty();
        this.breadcrumbs.append(home);
        this.folderlist.each(function (txt) {
            self.addCrumb(txt);
        });
        var dir = this.options.dir + this.folderlist.join(this.options.ds);
        this.doAjaxBrowse(dir);
    },

    doAjaxBrowse: function (dir) {

        var self = this,
            data = {
                'dir'       : dir,
                'option'    : 'com_fabrik',
                'format'    : 'raw',
                'task'      : 'plugin.pluginAjax',
                'plugin'    : 'fileupload',
                'method'    : 'ajax_getFolders',
                'element_id': this.options.id
            };
        new $.ajax({
            url : '',
            data: data
        }).done(function (r) {
                r = JSON.decode(r);
                this.folderdiv.empty();

                r.each(function (folder) {

                    $(document.createElement('li')).addClass('fileupload_folder').append(
                        $(document.createElement('a')).attr({'href': '#'}).text(folder)).inject(self.folderdiv);
                });
                if (r.length === 0) {
                    this.folderdiv.slideUp(500);
                } else {
                    this.folderdiv.slideDown(500);
                }
                this.watchAjaxFolderLinks();
                this.hiddenField.value = '/' + this.folderlist.join('/') + '/';
                this.trigger('onBrowse');
            });
    },


    addCrumb: function (txt) {
        this.breadcrumbs.append(
            $(document.createElement('a')).attr({'href': '#', 'class': 'crumb' + this.folderlist.length}).text(txt),
            $(document.createElement('span')).text(' / ')
        );
    }
});