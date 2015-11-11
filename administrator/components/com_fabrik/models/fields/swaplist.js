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
            e.stop();
            $('#jform__createGroup0').prop('checked', true);
            self.addSelectedToList(self.from, self.to);
            self.delSelectedFromList(self.from);
        });

        $('#' + removebutton).on('click', function (e) {
            e.stop();
            self.addSelectedToList(self.to, self.from);
            self.delSelectedFromList(self.to);
        });

        $('#' + upbutton).on('click', function (e) {
            e.stop();
            self.moveInList(-1);
        });

        $('#' + downbutton).on('click', function (e) {
            e.stop();
            self.moveInList(+1);
        });

        $('#adminForm').onsubmit = function (e) {
            jQuery.each(self.to.getElements('option'), function (key, opt) {
                opt.selected = true;
            });
            return true;
        };
    },

    addSelectedToList: function (from, to) {
        var i;
        var srcLen = from.length;
        var tgtLen = to.length;
        var tgt = 'x';

        // Build array of target items
        for (i = tgtLen - 1; i > -1; i--) {
            tgt += ',' + to.options[i].value + ',';
        }

        // Pull selected resources and add them to list
        for (i = 0; i < srcLen; i++) {
            if (from.options[i].selected && tgt.indexOf(',' + from.options[i].value + ',') === -1) {
                var opt = new Option(from.options[i].text, from.options[i].value);
                to.options[to.length] = opt;
            }
        }
    },

    delSelectedFromList: function (from) {
        var srcLen = from.length;
        for (var i = srcLen - 1; i > -1; i--) {
            if (from.options[i].selected) {
                from.options[i] = null;
            }
        }
    },

    moveInList: function (to) {
        var srcList = this.to;
        var index = this.to.selectedIndex;
        var total = srcList.options.length - 1;

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
            items[i] = srcList.options[i].text;
            values[i] = srcList.options[i].value;
        }
        for (i = total; i >= 0; i--) {
            if (index === i) {
                srcList.options[i + to] = new Option(items[i], values[i], 0, 1);
                srcList.options[i] = new Option(items[i + to], values[i + to]);
                i--;
            } else {
                srcList.options[i] = new Option(items[i], values[i]);
            }
        }
        srcList.focus();
        return true;
    }
});