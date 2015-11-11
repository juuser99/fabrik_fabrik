/**
 * Thumbs Element
 *
 * @copyright: Copyright (C) 2005-2015, fabrikar.com - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

var FbThumbs = my.Class(FbElement, {
    constructor: function (element, options, thumb) {
        this.field = $('#' + element);
        FbThumbs.Super.call(this, element, options);
        this.thumb = thumb;
        this.spinner = new Spinner(this.getContainer());
        this.setupj3();
    },

    setupj3: function () {
        var c = this.getContainer(),
            up = c.find('button.thumb-up'),
            down = c.find('button.thumb-down'),
            add,
            self = this;

        up.addEvent('click', function (e) {
            e.stopPropagation();
            if (self.options.canUse) {
                add = up.hasClass('btn-success') ? false : true;
                self.doAjax('up', add);
                if (!add) {
                    up.removeClass('btn-success');
                } else {
                    up.addClass('btn-success');
                    down.removeClass('btn-danger');
                }
            }
            else {
                self.doNoAccess();
            }
        });

        down.addEvent('click', function (e) {
            e.stopPropagation();
            if (self.options.canUse) {
                add = down.hasClass('btn-danger') ? false : true;
                self.doAjax('down', add);
                if (!add) {
                    down.removeClass('btn-danger');
                } else {
                    down.addClass('btn-danger');
                    up.removeClass('btn-success');
                }
            }
            else {
                self.doNoAccess();
            }
        });
    },

    doAjax: function (th, add) {
        add = add ? true : false;
        if (this.options.editable === false) {
            this.spinner.show();
            var data = {
                'option'     : 'com_fabrik',
                'format'     : 'raw',
                'task'       : 'plugin.pluginAjax',
                'plugin'     : 'thumbs',
                'method'     : 'ajax_rate',
                'g'          : 'element',
                'element_id' : this.options.elid,
                'row_id'     : this.options.row_id,
                'elementname': this.options.elid,
                'userid'     : this.options.userid,
                'thumb'      : th,
                'listid'     : this.options.listid,
                'formid'     : this.options.formid,
                'add'        : add
            };

            new $.ajax({
                url   : '',
                'data': data,
            }).done(function (r) {
                    r = JSON.decode(r);
                    this.spinner.hide();
                    if (r.error) {
                        console.log(r.error);
                    } else {
                        if (r !== '') {
                            var c = this.getContainer();
                            c.find('button.thumb-up .thumb-count').text(r[0]);
                            c.find('button.thumb-down .thumb-count').text(r[1]);
                        }
                    }
                });
        }
    },

    doNoAccess: function () {
        if (this.options.noAccessMsg !== '') {
            window.alert(this.options.noAccessMsg);
        }
    }

});