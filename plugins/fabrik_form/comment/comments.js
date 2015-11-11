/**
 * Form Comment
 *
 * @copyright: Copyright (C) 2005-2015, fabrikar.com - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

var FabrikComment = my.Class({

	options: {
			'formid': 0,
			'rowid': 0,
			'label': '',
		    'wysiwyg': false
	},

    constructor: function (element, options) {
        this.element = $('#' + element);
        if (this.element.length === 0) {
            return;
        }

        this.options = $.append(this.options, options);
        this.fx = {};
        this.fx.toggleForms = {};
        this.spinner = new Spinner('fabrik-comments', {'message': 'loading'});
        this.ajax = {};
        this.watchReply();
        this.watchInput();
    },

    ajaxComplete: function (d) {
        d = JSON.decode(d);
        var depth = (d.depth.toInt() * 20) + 'px';
        var id = 'comment_' + d.id;
        var li = $(document.createElement('li')).attr({
            'id': id
        })
            .css({'margin-left': depth}
        ).html(d.content);
        if (this.currentLi.get('tag') === 'li') {
            li.inject(this.currentLi, 'after');
        } else {
            li.inject(this.currentLi);
        }
        var fx = new Fx.Tween(li, {'property': 'opacity', duration: 5000});
        fx.set(0);
        fx.start(0, 100);

        this.watchReply();
        if (typeOf(d.message) !== 'null') {
            alert(d.message.title, d.message.message);
        }
        // For update
        this.spinner.hide();
        this.watchInput();
        this.updateThumbs();
    },

    // ***************************//
    // CAN THE LIST BE ADDED TO //
    // ***************************//

    watchInput: function () {
        var self = this;
        this.commentData = {
            'option': 'com_fabrik',
            'format': 'raw',
            'task'  : 'plugin.pluginAjax',
            'plugin': 'comment',
            'method': 'addComment',
            'g'     : 'form',
            'formid': this.options.formid,
            'rowid' : this.options.rowid,
            'label' : this.options.label
        };

        this.element.find('.replyForm').each(function (f) {
            var input = f.find('textarea');
            if (!input) {
                return;
            }
            f.find('button.submit').on('click', function (e) {
                self.doInput(e);
            });

            input.on('click', function (e) {
                self.testInput(e);
            });

        });
    },

    testInput: function (e) {
        if (e.target.val() === Joomla.JText._('PLG_FORM_COMMENT_TYPE_A_COMMENT_HERE')) {
            e.target.value = '';
        }
    },

    updateThumbs: function () {
        this.thumbs.removeEvents();
        this.thumbs.addEvents();
    },

    /**
     * Check details and then submit the form
     * @param {event} e
     */
    doInput: function (e) {
        var self = this;
        this.spinner.show();
        var replyForm = $(e.target).closest('.replyForm');
        if (replyForm.prop('id') === 'master-comment-form') {
            var lis = this.element.find('ul').find('li');
            if (lis.length > 0) {
                this.currentLi = lis.pop();
            } else {
                this.currentLi = this.element.find('ul');
            }
        } else {
            this.currentLi = replyForm.closest('li');
        }

		if (e.type === 'keydown') {
			if (e.keyCode.toInt() !== 13) {
				this.spinner.hide();
				return;
			}
		}

		if (this.options.wysiwyg) {
			if (typeof tinyMCE !== 'undefined') {
				tinyMCE.triggerSave();
			}
		}

	    var v = replyForm.find('textarea').val();
		e.stop();
		if (v === '') {
			this.spinner.hide();
			window.alert(Joomla.JText._('PLG_FORM_COMMENT_PLEASE_ENTER_A_COMMENT_BEFORE_POSTING'));
			return;
		}

        var name = replyForm.find('input[name=name]');
        var namestr = name.val();
        if (namestr === '') {
            this.spinner.hide();
            window.alert(Joomla.JText._('PLG_FORM_COMMENT_PLEASE_ENTER_A_NAME_BEFORE_POSTING'));
            return;
        }
        this.commentData.name = namestr;

        var notify = replyForm.find('input[name^=notify]').filter(function (i) {
            return i.checked;
        });

        this.commentData.notify = notify.length > 0 ? notify[0].val() : '0';

        var email = replyForm.find('input[name=email]');
        if (email) {
            var emailstr = email.val();
            if (emailstr === '') {
                this.spinner.hide();
                window.alert(Joomla.JText._('PLG_FORM_COMMENT_ENTER_EMAIL_BEFORE_POSTNG'));
                return;
            }
        }
        var replyto = replyForm.find('input[name=reply_to]').val();
        if (replyto === '') {
            replyto = 0;
        }
        this.commentData.email = replyForm.find('input[name=email]').val();
        this.commentData.renderOrder = replyForm.find('input[name=renderOrder]').val();
        this.commentData.rating = replyForm.find('select[name=rating]').val();
        var sel = replyForm.find('input[name^=anonymous]').filter(function (i) {
            return i.checked === true;
        });
        this.commentData.anonymous = sel[0].val();
        this.commentData.reply_to = replyto;
        this.commentData.comment = v;

        $.ajax({
            'url'   : 'index.php',
            'method': 'get',

        }).fail(function (jqxhr, textStatus, error) {
            window.alert(textStatus + ': ' + error);
            self.spinner.hide();
        }).success(function (r) {
            self.ajaxComplete(r);
        });

        replyForm.find('textarea').value = '';
    },

    saveComment: function (div) {
        var id = div.closest('.comment').id.replace('comment-', '');

        $.ajax({
            'url'   : '',
            'method': 'post',
            'data'  : {
                'option'  : 'com_fabrik',
                'format'  : 'raw',
                'task'    : 'plugin.pluginAjax',
                'plugin'  : 'comment',
                'method'  : 'updateComment',
                'g'       : 'form',
                'formid'  : this.options.formid,
                'rowid'   : this.options.rowid,
                comment   : div.get('text'),
                comment_id: id
            }
        });
    },

    // toggle fx the reply forms - recalled each time a comment is added via ajax
    watchReply: function () {
        var self = this, sel;
        this.spinner.resize();
        this.element.closest('.replybutton').each(function (a) {
            var fx;
            $(this).removeEvents();
            var commentForm = $(this).parent().parent().getNext();
            if (commentForm.length === 0) {
                // wierd ie7 ness?
                commentForm = a.closest('.comment').find('.replyForm');
            }
            if (commentForm.length > 0) {
                var li = a.closest('.comment').closest('li');
                commentForm.fadeOut();

                a.on('click', function (e) {
                    e.stop();
                    commentForm.fadeToggle();
                });
            }
        });
        // watch delete comment buttons
        this.element.find('.del-comment').each(function (a) {
            a.removeEvents();
            a.on('click', function (e) {
                $.ajax({
                    'url'   : '',
                    'method': 'get',
                    'data'  : {
                        'option'  : 'com_fabrik',
                        'format'  : 'raw',
                        'task'    : 'plugin.pluginAjax',
                        'plugin'  : 'comment',
                        'method'  : 'deleteComment',
                        'g'       : 'form',
                        'formid'  : this.options.formid,
                        'rowid'   : this.options.rowid,
                        comment_id: $(e.target).closest('.comment').prop('id').replace('comment-', '')
                    }
                }).done(function (e) {
                    self.deleteComplete(e);
                });

                self.updateThumbs();
                e.stop();
            });
        });
        // if admin watch inline edit
        if (this.options.admin) {

            this.element.find('.comment-content').each(function (a) {
                a.removeEvents();
                a.on('click', function (e) {
                    a.inlineEdit({
                        'defaultval': '',
                        'type'      : 'textarea',
                        'onComplete': function (editing, oldcontent, newcontent) {
                            self.saveComment(editing);
                        }
                    });
                    var c = $(e.target).parent();
                    var commentid = c.prop('id').replace('comment-', '');
                    new $.ajax({
                        'url'   : '',
                        'method': 'get',
                        'data'  : {
                            'option'   : 'com_fabrik',
                            'format'   : 'raw',
                            'task'     : 'plugin.pluginAjax',
                            'plugin'   : 'comment',
                            'method'   : 'getEmail',
                            'commentid': commentid,
                            'g'        : 'form',
                            'formid'   : self.options.formid,
                            'rowid'    : self.options.rowid
                        },
                    }).done(function (r) {
                            c.find('.info').dispose();
                            $(document.createElement('span')).addClass('info').html(r).inject(c);
                        });

                    e.stop();
                });
            });
        }
    },

    deleteComplete: function (r) {
        var c = $('#comment_' + r);
        var fx = new Fx.Morph(c, {
            duration  : 1000,
            transition: Fx.Transitions.Quart.easeOut
        });
        fx.start({
            'opacity': 0,
            'height' : 0
        }).chain(function () {
            c.dispose();
        });
    }
});