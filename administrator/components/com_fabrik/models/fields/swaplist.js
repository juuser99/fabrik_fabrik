/**
 * Admin SwapList Editor
 *
 * @copyright: Copyright (C) 2005-2015, fabrikar.com - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

var SwapList = my.Class({

    constructor: function (from, to, addbutton, removebutton, upbutton, downbutton) {
        this.from = $('#' + from);
        var self = this;
        this.to = $('#' + to);
        $('#' + addbutton).on('click', function (e) {
            e.stopPropagation();
            $('#jform__createGroup0').prop('checked', true);
            var opt = self.from.find('option:selected');
            opt.clone().appendTo(self.to);
            opt.remove();
        });

        $('#' + removebutton).on('click', function (e) {
            e.stopPropagation();
            var opt = self.to.find('option:selected');
            opt.clone().appendTo(self.from);
            opt.remove();
        });

        $('#' + upbutton).on('click', function (e) {
            e.stopPropagation();
            self.moveInList(-1);
        });

        $('#' + downbutton).on('click', function (e) {
            e.stopPropagation();
            self.moveInList(+1);
        });

        $('#adminForm').onsubmit = function (e) {
            jQuery.each(self.to.getElements('option'), function (key, opt) {
                opt.selected = true;
            });
            return true;
        };
    },

    /**
     *
     * @param {number} to
     * @returns {boolean}
     */
    moveInList: function (to) {
        var srcList = this.to,
            srcOpts = srcList.find('option'),
        index = this.to.prop('selectedIndex'),
        total = srcOpts.length - 1;

        if (index === -1) {
            return false;
        }
        if (to === +1 && index === total) {
            return false;
        }
        if (to === -1 && index === 0) {
            return false;
        }

        var items = [], i;
        var values = [];

        for (i = total; i >= 0; i--) {
            items[i] = srcOpts[i].text;
            values[i] = srcOpts[i].value;
        }
        for (i = total; i >= 0; i--) {
            if (index === i) {
                srcOpts[i + to] = new Option(items[i], values[i]);
                srcOpts[i] = new Option(items[i + to], values[i + to]);
                i--;
            } else {
                srcOpts[i] = new Option(items[i], values[i]);
            }
        }
        srcList.empty();
        srcList.append(srcOpts);
        srcList.focus();
        srcList.prop('selectedIndex', index + to);
        return true;
    },

    option: function (label, val) {
        return $('<option>').attr({'value': val}).text(label);
    }
});