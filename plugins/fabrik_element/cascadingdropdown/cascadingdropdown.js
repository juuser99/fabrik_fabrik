/**
 * Cascading Dropdown Element
 *
 * @copyright: Copyright (C) 2005-2013, fabrikar.com - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

/**
 watch another element for changes to its value, and send an ajax call to update
 this elements values
 */

var FbCascadingdropdown = my.Class(FbDatabasejoin, {

    constructor: function (element, options) {
        var self = this;
        this.ignoreAjax = false;
        FbCascadingdropdown.Super.call(this, element, options);
        this.plugin = 'cascadingdropdown';
        /**
         * In order to be able to remove specific change event functions when we clone
         * the element, we have to bind the call to a variable, can't use inline functions
         */
        this.doChangeEvent = this.doChange.bind(this);
        $('#' + this.options.watch).on(this.options.watchChangeEvent, this.doChangeEvent);
        if (this.options.showDesc === true) {
            this.element.on('change', function (e) {
                self.showDesc(e);
            });
        }
        if (this.element.length > 0) {
            this.spinner = new Spinner(this.element.parent('.fabrikElementContainer'));
        }
    },

    attachedToForm: function () {
        // $$$ rob have to call update here otherwise all options can be shown
        //use this method as getValue on el wont work if el readonly
        // $$$ hugh - only do this if not editing an existing row, see ticket #725
        // $$$ hugh - ignoreAjax is set when duplicating a group, when we do need to change()
        // regardless of whether this is a new row or editing.
        if (this.ignoreAjax || (this.options.editable && !this.options.editing)) {
            var v = this.form.formElements.get(this.options.watch).getValue();
            this.change(v, $(this.options.watch).prop('id'));
        }
    },

    dowatch: function (e) {
        var v = Fabrik.blocks[this.form.form.prop('id')].formElements[this.options.watch].getValue();
        this.change(v, e.target.id);
    },

    doChange: function (e) {
        if (this.options.displayType === 'auto-complete') {
            this.element.value = '';
            this.getAutoCompleteLabelField().value = '';
        }
        this.dowatch(e);
    },

    /**
     * Change
     * @param   v          Value of observed element
     * @param   triggerid  Observed element's HTML id
     */
    change: function (v, triggerid) {
        var self = this;
        /* $$$ rob think this is obsolete:
         * http://fabrikar.com/forums/showthread.php?t=19675&page=2
         * $$$ hugh - nope, we still need it, with a slight modification to allow CDD to work in first group:
         * http://fabrikar.com/forums/showthread.php?p=109638#post109638
         */
        if (window.ie) {
            if (this.options.repeatCounter.toInt() === 0) {
                // this is the original cdd element
                var s = triggerid.substr(triggerid.length - 2, 1);
                var i = triggerid.substr(triggerid.length - 1, 1);
                // test for "_x" at end of trigger id where x is an int
                if (s === '_' && typeOf(parseInt(i, 10)) === 'number' && i !== '0') {
                    //found so this is the bug where a third watch element incorrectly updates orig
                    return;
                }
            }
        }
        this.spinner.show();
        // $$$ hugh testing new getFormElementData() method to include current form element values in data
        // so any custom 'where' clause on the cdd can use {placeholders}.  Can't use getFormData() because
        // it includes all QS from current page, including task=processForm, which screws up this AJAX call.
        var formdata = this.form.getFormElementData();

        var data = {
            'option'                    : 'com_fabrik',
            'format'                    : 'raw',
            'task'                      : 'plugin.pluginAjax',
            'plugin'                    : 'cascadingdropdown',
            'method'                    : 'ajax_getOptions',
            'element_id'                : this.options.id,
            'v'                         : v,
            'formid'                    : this.form.id,
            'fabrik_cascade_ajax_update': 1,
            'lang'                      : this.options.lang
        };
        data = Object.append(formdata, data);
        if (this.myAjax) {
            // $$$ rob stops ascyro behaviour when older ajax call might take longer than new call and thus populate the dd with old data.
            this.myAjax.cancel();
        }
        this.myAjax = $.ajax({
            url       : '',
            method    : 'post',
            'data'    : data,
            dataType  : 'json',
            onComplete: function () {
                this.spinner.hide();
            }.bind(this),
        }).fail(function (jqxhr, textStatus, error) {
            console.log(textStatus + '', '' + error);
        }).done(function () {
            self.spinner.hide();
        }).success(function (json) {
            var origValue = self.getValue(),
                updateField,
                c;
            self.spinner.hide();

            if (self.options.editable) {
                self.destroyElement();
            } else {
                self.element.find('div').destroy();
            }

            if (this.options.showDesc === true) {
                c = self.getContainer().find('.dbjoin-description');
                c.empty();
            }
            this.myAjax = null;
            var singleResult = json.length === 1;
            if (!this.ignoreAjax) {
                json.each(function (k) {
                    var item = this;
                    if (this.options.editable === false) {

                        // Pretify new lines to brs
                        item.text = item.text.replace(/\n/g, '<br />');
                        $(document.createElement('div')).html(item.text).inject(this.element);
                    } else {
                        updateField = (item.value !== '' && item.value === origValue) || singleResult;
                        self.addOption(item.value, item.text, updateField);
                    }

                    if (self.options.showDesc === true && item.description) {
                        var className = self.options.showPleaseSelect ? 'notice description-' + (k) : 'notice description-' + (k - 1);
                        $(document.createElement('div')).css({display: 'none'})
                            .addClass(className).html(item.description).inject(c);
                    }
                });
            } else {
                if (self.options.showPleaseSelect && json.length > 0) {
                    var item = json.shift();
                    if (self.options.editable === false) {
                        $(document.createElement('div')).text(item.text).inject(this.element);
                    } else {
                        updateField = (item.value !== '' && item.value === origValue) || singleResult;
                        self.addOption(item.value, item.text, updateField);
                        $(document.createElement('option')).attr({'value': item.value, 'selected': 'selected'})
                            .text(item.text).inject(this.element);
                    }
                }
            }
            self.ignoreAjax = false;
            // $$$ hugh - need to remove/add 'readonly' class ???  Probably need to add/remove the readonly="readonly" attribute as well
            //this.element.disabled = (this.element.options.length === 1 ? true : false);
            if (self.options.editable && this.options.displayType === 'dropdown') {
                if (self.element.options.length === 1) {
                    // SELECTS DON'T HAVE READONLY PROPERTIES
                    //this.element.setProperty('readonly', true);
                    self.element.addClass('readonly');
                } else {
                    //this.element.readonly = false;
                    //this.element.removeProperty('readonly');
                    self.element.removeClass('readonly');
                }
            }
            self.renewEvents();
            // $$$ hugh - need to fire this CDD's 'change' event in case we have another CDD
            // daisy chained on us.  We just don't need to do it if 'ignoreAjax' is true, because
            // that means we're being added to the form, and everyone will get their change() method
            // run anyway.  Note we have to supply the 'dowatch_event' we tucked away in dowatch()
            // above.
            if (!self.ignoreAjax) {
                self.ingoreShowDesc = true;
                self.element.trigger('change');
                self.ingoreShowDesc = false;
            }
            self.ignoreAjax = false;

            var newV = [self.getValue()];
            self.setValue(newV);

            Fabrik.trigger('fabrik.cdd.update', self);
        });
    },

    destroyElement: function () {
        switch (this.options.displayType) {
            case 'radio':
            /* falls through */
            case 'checkbox':
                this.getContainer().getElements('*[data-role="suboption"]').destroy();
                break;
            case 'dropdown':
            /* falls through */
            default:
                this.element.empty();
                break;
        }
    },

    cloned: function (c) {
        var watch = $('#' + this.options.watch),
            self = this;
        // c is the repeat group count
        this.myAjax = null;
        FbCascadingdropdown.Super.prototype.cloned(this, c);
        this.spinner = new Spinner(this.element.closest('.fabrikElementContainer'));
        // Cloned seems to be called correctly
        if (this.options.watchInSameGroup === true) {
            // $$$ hugh - nope, 'cos watch already has the _X appended to it!
            // Should really work out base watch name (without _X) in PHP and put it in this.options.origWatch,
            // but for now ... regex it ...
            // this.options.watch = this.options.watch + '_' + c;
            if (this.options.watch.test(/_(\d+)$/)) {
                this.options.watch = this.options.watch.replace(/_(\d+)$/, '_' + c);
            }
            else {
                this.options.watch = this.options.watch + '_' + c;
            }
        }
        /**
         * Remove the previously bound change event function, by name, then re-bind it and re-add it
         */
        /**
         * Actually, we don't want to remove it, as this stops the element we got copied from
         * being updated on a change.  This issue only surfaced when we changed this code to use
         * a bound function, so it actually started removing the event, which it never did before
         * when we referenced an inline function().
         *
         * Update ... if the watched element is in the repeat group, we do want to remove it,
         * but if the watch is on the main form, we don't.  In other words, if the watch is on the main
         * form, then every CDD in this repeat is watching it.  If it's in the repeat group, then each repeat
         * CDD only watches the one in it's own group.
         */
        if (this.options.watchInSameGroup) {
            watch.removeEvent(this.options.watchChangeEvent, this.doChangeEvent);
        }
        this.doChangeEvent = this.doChange.bind(this);
        watch.on(this.options.watchChangeEvent, this.doChangeEvent);

        if (this.options.watchInSameGroup === true) {
            this.element.empty();
            // Set ingoreAjax so that the ajax event that is fired when the element is added to the form manager
            // does not update the newly cloned drop-down
            this.ignoreAjax = true;
        }
        if (this.options.showDesc === true) {
            this.element.on('change', function () {
                self.showDesc();
            });
        }
        Fabrik.trigger('fabrik.cdd.update', this);
    },

    /**
     * Update auto-complete fields id and create new auto-completer object for duplicated element
     */
    cloneAutoComplete: function () {
        var f = this.getAutoCompleteLabelField();
        f.id = this.element.id + '-auto-complete';
        f.name = this.element.name.replace('[]', '') + '-auto-complete';
        $('#' + f.id).val('');
        new FabCddAutocomplete(this.element.id, this.options.autoCompleteOpts);
    },

    showDesc: function (e) {
        if (this.ingoreShowDesc === true) {
            return;
        }
        var v = e.target.selectedIndex,
            c = this.getContainer().find('.dbjoin-description'),
            show = c.find('.description-' + v),
            myFx;
        c.getElements('.notice').each(function (d) {
            if (d === show) {
                myFx = new Fx.Style(show, 'opacity', {
                    duration  : 400,
                    transition: Fx.Transitions.linear
                });
                myFx.set(0);
                d.show();
                myFx.start(0, 1);
            } else {
                d.hide();
            }
        }.bind(this));
    }
});