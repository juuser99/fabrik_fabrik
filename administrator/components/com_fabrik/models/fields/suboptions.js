/**
 * Admin SubOptions Editor
 *
 * @copyright: Copyright (C) 2005-2015, fabrikar.com - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

var Suboptions = my.Class({

    options: {
        sub_initial_selection: [],
        j3                   : false,
        defaultMax           : 0
    },

    constructor: function (name, options) {
        this.options = $.extend(this.options, options);
        this.element = $('#' + this.options.id);

        if (this.element.length === 0) {
            if (window.confirm('oh dear - somethings gone wrong with loading the sub-options, do you want to reload?')) {

                // Force reload from server
                location.reload(true);
            }
        }
        this.watchButtons();
        this.watchDefaultCheckboxes();
        this.counter = 0;
        this.name = name;
        Object.each(this.options.sub_values, function (v, x) {
            var chx = Object.contains(this.options.sub_initial_selection, v) ? "checked='checked'" : '';
            this.addSubElement(v, this.options.sub_labels[x], chx);
        }.bind(this));

        if (this.options.sub_values.length === 0) {
            this.addSubElement('', '', false);
        }
        // $$$ rob - could probably do this better with firing an event from the main element page but for now this will do
        Joomla.submitbutton = function (pressbutton) {
            if (pressbutton !== 'element.cancel' && !this.onSave()) {
                return false;
            }
            Joomla.submitform(pressbutton);
        }.bind(this);

    },

    // For radio buttons we only want to have one default selected at a time
    watchDefaultCheckboxes: function () {
        var self = this;
        this.element.on('click', 'input.sub_initial_selection', function (e) {
            if (self.options.defaultMax === 1) {
                this.element.find('input.sub_initial_selection').each(function () {
                    if (this !== e.target) {
                        $(this).prop('checked', false);
                    }
                });
            }
        });
    },

    watchButtons: function () {
        var self = this;
        this.element.on('click', 'a[data-button="addSuboption"]', function (e) {
            e.preventDefault();
            self.addSubElement();
        });

        this.element.on('click', 'a[data-button="deleteSuboption"]', function (e) {
            e.preventDefault();
            var trs = self.element.find('tbody tr');
            if (trs.length > 1) {
                $(this).closest('tr').remove();
            }
        });
    },

    addOption: function (e) {
        this.addSubElement();
        e.stopPropagation();
    },

    removeSubElement: function (e) {
        var id = e.target.id.replace('sub_delete_', '');
        if ($('#sub_subElementBody').find('li').length > 1) {
            $('#sub_content_' + id).remove();
        }
        e.stopPropagation();
    },

    addJ3SubElement: function (sValue, sText, sCurChecked) {
        var chx = this._chx(sValue, sCurChecked);
        var delButton = this._deleteButton();
        var tr = $(document.createElement('tr')).append([
            $(document.createElement('td')).addClass('handle subhandle'),
            $(document.createElement('td')).attr({width: '30%'}).append(this._valueField(sValue)),
            $(document.createElement('td')).attr({width: '30%'}).append(this._labelField(sText)),
            $(document.createElement('td')).attr({width: '10%'}).html(
                chx
            ),
            delButton
        ]);
        var tbody = this.element.find('tbody');
        tbody.adopt(tr);

        if (!this.sortable) {
            this.sortable = new Sortables(tbody, {'handle': '.subhandle'});
        } else {
            this.sortable.addItems(tr);
        }
        this.counter++;

    },

    _valueField: function (sValue) {
        return $(document.createElement('input')).addClass('inputbox sub_values').attr({
            type : 'text',
            name : this.name + '[sub_values][]',
            id   : 'sub_value_' + this.counter,
            size : 20,
            value: sValue
        });
    },

    _labelField: function (sText) {
        return $(document.createElement('input')).addClass('inputbox sub_labels').attr({
            type : 'text',
            name : this.name + '[sub_labels][]',
            id   : 'sub_text_' + this.counter,
            size : 20,
            value: sText
        });
    },

    _chx: function (sValue, sCurChecked) {
        return "<input class=\"inputbox sub_initial_selection\" type=\"checkbox\" value=\"" + sValue + "\" name='" + this.name + "[sub_initial_selection][]' id=\"sub_checked_" + this.counter + "\" " + sCurChecked + " />";
    },

    _deleteButton: function () {
        return $(document.createElement('td')).attr({width: '20%'}).html(this.options.delButton);
    },

    addSubElement: function (sValue, sText, sCurChecked) {
        return this.addJ3SubElement(sValue, sText, sCurChecked);
    },

    onSave: function () {
        var values = [],
            ret = true,
            intial_selection = [],
            evalPop = $('#jform_params_dropdown_populate'),
            evalAdded = false;
        if (evalPop.val() !== '') {
            evalAdded = true;
        }
        if (!evalAdded) {
            $('.sub_values').each(function () {
                if ($(this).val() === '') {
                    window.alert(Joomla.JText._('COM_FABRIK_SUBOPTS_VALUES_ERROR'));
                    ret = false;
                }
                values.push($(this).val());
            });
        }
        $('.sub_initial_selection').each(function (c) {
            $(this).val(values[c]);
        });
        return ret;
    }
});