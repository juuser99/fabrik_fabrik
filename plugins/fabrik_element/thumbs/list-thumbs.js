/**
 * Thumbs Element - List
 *
 * @copyright: Copyright (C) 2005-2015, fabrikar.com - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

var FbThumbsList = my.Class({

    options: {
        'imageover'  : '',
        'imageout'   : '',
        'userid'     : '',
        'formid'     : 0,
        'noAccessMsg': '',
        'canUse'     : true
    },

    constructor: function (id, options) {
        this.options = $.extend(this.options, options);
        this.col = document.find('.' + id);
        if (this.options.voteType === 'comment') {
            this.setUpBootstrappedComments();
        } else {
            this.setUpBootstrapped();
        }
    },

    setUpBootstrappedComments: function () {
        var self = this;
        $(document).on('click', '*[data-fabrik-thumb]', function (e) {
            if (self.options.canUse) {
                var add = $(this).hasClass('btn-success') ? false : true;
                var dir = $(this).data('fabrik-thumb');
                var formid = $(this).data('fabrik-thumb-formid');
                var rowid = $(this).data('fabrik-thumb-rowid');

                self.doAjax(this, dir, add);
                if (dir === 'up') {
                    if (!add) {
                        $(this).removeClass('btn-success');
                    } else {
                        $(this).addClass('btn-success');
                        var down = $('button[data-fabrik-thumb-formid=' + formid + '][data-fabrik-thumb-rowid=' + rowid + '][data-fabrik-thumb=down]');
                        down.removeClass('btn-danger');
                    }
                } else {
                    var up = $('button[data-fabrik-thumb-formid=' + formid + '][data-fabrik-thumb-rowid=' + rowid + '][data-fabrik-thumb=up]');
                    if (!add) {
                        $(this).removeClass('btn-danger');
                    } else {
                        $(this).addClass('btn-danger');
                        up.removeClass('btn-success');
                    }
                }
            }
            else {
                e.stopPropagation();
                self.doNoAccess();
            }
        });

    },

    setUpBootstrapped: function () {
        var self = this, up, down, add;
        this.col.each(function (td) {
            var row = td.closest('.fabrik_row');

            if (row.length > 0) {
                up = td.find('button.thumb-up');
                down = td.find('button.thumb-down');

                up.on('click', function (e) {
                    e.stopPropagation();
                    if (self.options.canUse) {
                        add = up.hasClass('btn-success') ? false : true;
                        self.doAjax(up, 'up', add);

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

                down.on('click', function (e) {
                    e.stopPropagation();
                    if (self.options.canUse) {
                        add = down.hasClass('btn-danger') ? false : true;
                        self.doAjax(down, 'down', add);

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
            }
        });
    },

    doAjax: function (e, thumb, add) {
        // We shouldn't get here if they didn't have access, but doesn't hurt to check
        if (!this.options.canUse) {
            this.doNoAccess();
        }
        else {
            add = add ? true : false;
            var row = e.parent();
            var rowid = e.get('data-fabrik-thumb-rowid');
            var count_thumb = $('#count_thumb' + thumb + rowid);
            Fabrik.loader.start(row);
            this.thumb = thumb;

            var data = {
                'option'     : 'com_fabrik',
                'format'     : 'raw',
                'task'       : 'plugin.pluginAjax',
                'plugin'     : 'thumbs',
                'method'     : 'ajax_rate',
                'g'          : 'element',
                'element_id' : this.options.elid,
                'row_id'     : rowid,
                'elementname': this.options.elid,
                'userid'     : this.options.userid,
                'thumb'      : this.thumb,
                'listid'     : this.options.listid,
                'formid'     : this.options.formid,
                'add'        : add
            };

            if (this.options.voteType === 'comment') {
                data.special = 'comments_' + this.options.formid;
            }

            $.ajax({
                url   : '',
                'data': data
            }).done(function (r) {
                var count_thumbup = $('#count_thumbup' + rowid),
                    count_thumbdown = $('#count_thumbdown' + rowid),
                    thumbup = row.find('.thumbup'),
                    thumbdown = row.find('.thumbdown');
                Fabrik.loader.stop(row);
                r = JSON.decode(r);

                if (r.error) {
                    console.log(r.error);
                } else {
                    row.find('button.thumb-up .thumb-count').text(r[0]);
                    row.find('button.thumb-down .thumb-count').text(r[1]);
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