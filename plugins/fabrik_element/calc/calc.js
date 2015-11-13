/**
 * Calc Element Forms
 *
 * @copyright: Copyright (C) 2005-2013, fabrikar.com - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

var FbCalc = my.Class(FbElement, {

    constructor: function (element, options) {
        this.plugin = 'calc';
        this.oldAjaxCalc = null;
        FbCalc.Super.call(this, element, options);
    },

    attachedToForm: function () {
        var eventType, v2, o2,
            elements = this.form.formElements;
        if (this.options.ajax) {
            // @TODO - might want to think about firing ajaxCalc here as well, if we've just been added to the form
            // as part of duplicating a group.  Don't want to do it in cloned(), as that would be before elements
            // we observe have finished setting themselves up.  So just need to work out if this is on page load
            // or on group clone.
            this.options.observe.each(function (o) {
                eventType = elements[o2].getChangeEvent();
                if (o === '') {
                    return;
                }
                if (elements[o]) {
                    elements[o].addNewEventAux(elements[o].getChangeEvent(), function (e) {
                        this.calc(e);
                    }.bind(this));
                }
                else {
                    // Check to see if an observed element is actually part of a repeat group,
                    // and if so, modify the placeholder name they used to match this instance of it
                    // @TODO - add and test code for non-joined repeats!

                    // @TODO:  this needs updating as we dont store as join.x.element any more?
                    if (this.options.canRepeat) {
                        o2 = o + '_' + this.options.repeatCounter;
                        if (elements[o2]) {
                            elements[o2].addNewEventAux(eventType, function (e) {
                                this.calc(e);
                            }.bind(this));
                        }
                    }
                    else {
                        this.form.repeatGroupMarkers.each(function (v, k) {
                            o2 = '';
                            for (v2 = 0; v2 < v; v2++) {
                                o2 = 'join___' + this.form.options.group_join_ids[k] + '___' + o + '_' + v2;
                                if (this.form.formElements[o2]) {
                                    // Think we can add this one as sticky ...
                                    elements[o2].addNewEvent(eventType, function (e) {
                                        this.calc(e);
                                    }.bind(this));
                                }
                            }
                        }.bind(this));
                    }
                }
            }.bind(this));

            if (this.options.calcOnLoad) {
                this.calc();
            }
        }
    },

    calc: function () {
        var formdata = this.form.getFormElementData(),
            testdata = this.form.getFormData(false),
            self = this;

        $.each(testdata, function (k, v) {
            if (k.test(/^join\[\d+\]/) || k.test(/^fabrik_vars/)) {
                formdata[k] = v;
            }
        });

        $.each(formdata, function (k, v) {
            var el = self.form.formElements.get(k);
            if (el && el.options.inRepeatGroup && el.options.joinid === self.options.joinid &&
                el.options.repeatCounter === self.options.repeatCounter) {
                formdata[el.options.fullName] = v;
                formdata[el.options.fullName + '_raw'] = formdata[k + '_raw'];
            }
        });

        // For placeholders lets set repeat joined groups to their full element name

        var data = {
            'option'    : 'com_fabrik',
            'format'    : 'raw',
            'task'      : 'plugin.pluginAjax',
            'plugin'    : 'calc',
            'method'    : 'ajax_calc',
            'element_id': this.options.id,
            'formid'    : this.form.id
        };
        data = $.extend(formdata, data);
        Fabrik.loader.start(this.element.parent(), Joomla.JText._('COM_FABRIK_LOADING'));
        $.ajax({
            'url' : '',
            method: 'post',
            'data': data,
        }).done(function (r) {
            Fabrik.loader.stop(self.element.parent());
            self.update(r);
            if (self.options.validations) {

                // If we have a validation on the element run it after AJAX calc is done
                self.form.doElementValidation(self.options.element);
            }
            // Fire an onChange event so that js actions can be attached and fired when the value updates
            self.element.trigger('change');
            Fabrik.trigger('fabrik.calc.update', [this, r]);
        });
    },


    cloned: function (c) {
        FbCalc.Super.prototype.cloned(this, c);
        this.attachedToForm();
    },

    update: function (val) {
        if (this.getElement()) {
            this.element.html(val);
            this.options.value = val;
        }
    },

    getValue: function () {
        if (this.element) {
            return this.options.value;
        }
        return false;
    }
});