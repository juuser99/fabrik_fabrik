/**
 * Admin RepeatGroup Editor
 *
 * @copyright: Copyright (C) 2005-2015, fabrikar.com - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

var FbRepeatGroup = my.Class({

    options: {
        repeatmin: 1
    },

    /**
     *
     * @param {string} element
     * @param {object} options
     */
    constructor: function (element, options) {
        this.element = $('#' + element);
        this.options = $.extend(this.options, options);
        this.counter = this.getCounter();
        this.watchAdd();
        this.watchDelete();
    },

    repeatContainers: function () {
        return this.element.find('.repeatGroup');
    },

    watchAdd: function () {
        var newid, self = this;
        this.element.find('a[data-button=addButton]').on('click', function (e) {
            e.stopPropagation();
            var div = self.repeatContainers().getLast();
            var newc = self.counter + 1;
            var id = div.id.replace('-' + self.counter, '-' + newc);
            var c = $(document.createElement('div')).addClass('repeatGroup').attr({'id': id}).html(div.innerHTML);
            c.inject(div, 'after');
            self.counter = newc;

            // Update params ids
            if (self.counter !== 0) {
               c.find('input, select').each(function (key, i) {
                    var newPlugin = false;
                    var newid = '';
                    var oldid = i.id;
                    if (i.id !== '') {
                        var a = i.id.split('-');
                        a.pop();
                        newid = a.join('-') + '-' + self.counter;
                        i.id = newid;
                    }

                   self.increaseName(i);
                    jQuery.each(FabrikAdmin.model.fields, function (type, plugins) {
                        var newPlugin = false;
                        if (FabrikAdmin.model.fields[type][oldid] !== undefined) {
                            var plugin = FabrikAdmin.model.fields[type][oldid];
                            newPlugin = Object.clone(plugin);
                            try {
                                newPlugin.cloned(newid, self.counter);
                            } catch (err) {
                                fconsole('no clone method available for ' + i.id);
                            }
                        }
                        if (newPlugin !== false) {
                            FabrikAdmin.model.fields[type][i.id] = newPlugin;
                        }
                    });
                });

                c.find('img[src=components/com_fabrik/images/ajax-loader.gif]').each(function () {
                    var a = this.id.split('-');
                    a.pop();
                    var newid = a.join('-') + '-' + self.counter + '_loader';
                    this.id = newid;
                });
            }
        });
    },

    getCounter: function () {
        return this.repeatContainers().length;
    },

    watchDelete: function () {
        var btns = this.element.find('a[data-button=deleteButton]'),
            self = this;
        btns.removeEvents();
        btns.each(function (x) {
            $(this).on('click', function (e) {
                e.stopPropagation();
                var count = self.getCounter();
                if (count > self.options.repeatmin) {
                    var u = self.repeatContainers().getLast();
                    u.destroy();
                }
                self.rename(x);
            });
        });
    },

    increaseName: function (i) {
        var namebits = i.name.split('][');
        var ref = parseInt(namebits[2].replace(']', ''), 10) + 1;
        namebits.splice(2, 1, ref);
        i.name = namebits.join('][') + ']';
    },

    rename: function (x) {
        var self = this;
        this.element.find('input, select').each(function () {
            this.name = self._decreaseName(this.name, x);
        });
    },

    _decreaseName: function (n, delIndex) {
        var namebits = n.split(']['),
            i = parseInt(namebits[2].replace(']', ''), 10);
        if (i >= 1 && i > delIndex) {
            i--;
        }
        if (namebits.length === 3) {
            i = i + ']';
        }
        namebits.splice(2, 1, i);
        var r = namebits.join('][');
        return r;
    }
});