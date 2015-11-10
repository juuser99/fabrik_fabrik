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
		this.element = document.id(element);
		if (typeOf(this.element) === 'null') {
			return;
		}

		this.options = $.append(this.options, options);
		this.fx = {};
		this.fx.toggleForms = {};
		this.spinner = new Spinner('fabrik-comments', {'message': 'loading'});
		this.ajax = {};
		this.ajax.deleteComment = new Request({
			'url': '',
			'method': 'get',
			'data': {
				'option': 'com_fabrik',
				'format': 'raw',
				'task': 'plugin.pluginAjax',
				'plugin': 'comment',
				'method': 'deleteComment',
				'g': 'form',
				'formid': this.options.formid,
				'rowid': this.options.rowid
			},
			'onComplete': function (e) {
				this.deleteComplete(e);
			}.bind(this)
		});
		this.ajax.updateComment = new Request({
			'url': '',
			'method': 'post',
			'data': {
				'option': 'com_fabrik',
				'format': 'raw',
				'task': 'plugin.pluginAjax',
				'plugin': 'comment',
				'method': 'updateComment',
				'g': 'form',
				'formid': this.options.formid,
				'rowid': this.options.rowid
			}
		});
		this.watchReply();
		this.watchInput();
	},

	ajaxComplete: function (d) {
		d = JSON.decode(d);
		var depth = (d.depth.toInt() * 20) + 'px';
		var id = 'comment_' + d.id;
		var li = new Element('li', {'id': id, styles: {'margin-left': depth}
		}).set('html', d.content);
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

		this.ajax.addComment = new Request({
			'url': 'index.php',
			'method': 'get',
			'data': {
				'option': 'com_fabrik',
				'format': 'raw',
				'task': 'plugin.pluginAjax',
				'plugin': 'comment',
				'method': 'addComment',
				'g': 'form',
				'formid': this.options.formid,
				'rowid': this.options.rowid,
				'label': this.options.label
			},

			'onSuccess': function (r) {
				this.ajaxComplete(r);
			}.bind(this),

			'onError': function (text, error) {
				fconsole(text + ": " + error);
				this.spinner.hide();
			}.bind(this),

			'onFailure': function (xhr) {
				alert(xhr.statusText);
				this.spinner.hide();
			}.bind(this)
		});

		this.element.getElements('.replyForm').each(function (f) {
			var input = f.getElement('textarea');
			if (!input) {
				return;
			}
			f.getElement('button.submit').addEvent('click', function (e) {
				this.doInput(e);
			}.bind(this));

			input.addEvent('click', function (e) {
				this.testInput(e);
			}.bind(this));

		}.bind(this));
	},

	testInput : function (e) {
		if (e.target.get('value') === Joomla.JText._('PLG_FORM_COMMENT_TYPE_A_COMMENT_HERE')) {
			e.target.value = '';
		}
	},

	updateThumbs: function () {
		if (typeOf(this.thumbs) !== 'null') {
			this.thumbs.removeEvents();
			this.thumbs.addEvents();
		}
	},

	/**
	 * Check details and then submit the form
	 * @param {event} e
	 */
	doInput : function (e) {
		this.spinner.show();
		var replyForm = $(e).target.closest('.replyForm');
		if (replyForm.id === 'master-comment-form') {
			var lis = this.element.getElement('ul').getElements('li');
			if (lis.length > 0) {
				this.currentLi = lis.pop();
			} else {
				this.currentLi = this.element.getElement('ul');
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

		var v = replyform.getElement('textarea').get('value');
		e.stop();
		if (v === '') {
			this.spinner.hide();
			window.alert(Joomla.JText._('PLG_FORM_COMMENT_PLEASE_ENTER_A_COMMENT_BEFORE_POSTING'));
			return;
		}

		var name = replyForm.getElement('input[name=name]');
		if (name) {
			var namestr = name.get('value');
			if (namestr === '') {
				this.spinner.hide();
				window.alert(Joomla.JText._('PLG_FORM_COMMENT_PLEASE_ENTER_A_NAME_BEFORE_POSTING'));
				return;
			}
			this.ajax.addComment.options.data.name = namestr;
		}

		var notify = replyForm.getElements('input[name^=notify]').filter(function (i) {
			return i.checked;
		});

		this.ajax.addComment.options.data.notify = notify.length > 0 ? notify[0].get('value') : '0';

		var email = replyForm.getElement('input[name=email]');
		if (email) {
			var emailstr = email.get('value');
			if (emailstr === '') {
				this.spinner.hide();
				window.alert(Joomla.JText._('PLG_FORM_COMMENT_ENTER_EMAIL_BEFORE_POSTNG'));
				return;
			}
		}
		var replyto = replyForm.getElement('input[name=reply_to]').get('value');
		if (replyto === '') {
			replyto = 0;
		}
		if (replyForm.getElement('input[name=email]')) {
			this.ajax.addComment.options.data.email = replyForm.getElement('input[name=email]').get('value');
		}
		this.ajax.addComment.options.data.renderOrder = replyForm.getElement('input[name=renderOrder]').get('value');
		if (replyForm.getElement('select[name=rating]')) {
			this.ajax.addComment.options.data.rating = replyForm.getElement('select[name=rating]').get('value');
		}
		if (replyForm.getElement('input[name^=anonymous]')) {
			var sel = replyForm.getElements('input[name^=anonymous]').filter(function (i) {
				return i.checked === true;
			});
			this.ajax.addComment.options.data.anonymous = sel[0].get('value');
		}

		this.ajax.addComment.options.data.reply_to = replyto;
		this.ajax.addComment.options.data.comment = v;
		this.ajax.addComment.send();
		replyForm.getElement('textarea').value = '';
	},

	saveComment : function (div) {
		var id = div.closest('.comment').id.replace('comment-', '');

		this.ajax.updateComment.options.data.comment_id = id;
		// @TODO causing an error when saving inline edit
		/*if (typeOf(comment_plugin_notify) !== 'null') {
			this.ajax.updateComment.options.data.comment_plugin_notify = comment_plugin_notify.get('value');
		}*/
		this.ajax.updateComment.options.data.comment = div.get('text');
		this.ajax.updateComment.send();
	},

	// toggle fx the reply forms - recalled each time a comment is added via ajax
	watchReply : function () {
		this.spinner.resize();
		this.element.closest('.replybutton').each(function (a) {
			var fx;
			$(this).removeEvents();
			var commentform = $(this).parent().parent().getNext();
			if (commentform.length === 0) {
				// wierd ie7 ness?
				commentform = a.closest('.comment').find('.replyForm');
			}
			if (typeOf(commentform) !== 'null') {
				if (this.options.wysiwyg) {
					fx = commentform;
				}
				else {
                    var li = a.getParent('.comment').getParent('li');
                    if (window.ie) {
                        fx = new Fx.Slide(commentform, 'opacity', {
                            duration : 5000
                        });

                    } else {
	                    if (this.fx.toggleForms.has(li.id)) {
		                    fx = this.fx.toggleForms.get(li.id);
	                    } else {
		                    fx = new Fx.Slide(commentform, 'opacity', {
			                    duration: 5000
		                    });
		                    this.fx.toggleForms.set(li.id, fx);
	                    }
                    }
				}

				fx.hide();
				a.addEvent('click', function (e) {
					e.stop();
					fx.toggle();
				}.bind(this));
			}
		}.bind(this));
		// watch delete comment buttons
		this.element.getElements('.del-comment').each(function (a) {
			a.removeEvents();
			a.addEvent('click', function (e) {
				this.ajax.deleteComment.options.data.comment_id = $(e.target).closest('.comment')[0].id.replace('comment-', '');
				this.ajax.deleteComment.send();
				this.updateThumbs();
				e.stop();
			}.bind(this));
		}.bind(this));
		// if admin watch inline edit
		if (this.options.admin) {

			this.element.getElements('.comment-content').each(function (a) {
				a.removeEvents();
				a.addEvent('click', function (e) {
					a.inlineEdit({
						'defaultval' : '',
						'type' : 'textarea',
						'onComplete' : function (editing, oldcontent, newcontent) {
								this.saveComment(editing);
							}.bind(this)
					});
					var c = $(e).target.parent();
					var commentid = c.id.replace('comment-', '');
					new Request({
						'url': '',
						'method': 'get',
						'data': {
							'option': 'com_fabrik',
							'format': 'raw',
							'task': 'plugin.pluginAjax',
							'plugin': 'comment',
							'method': 'getEmail',
							'commentid': commentid,
							'g': 'form',
							'formid': this.options.formid,
							'rowid': this.options.rowid
						},
						'onComplete': function (r) {
							c.getElements('.info').dispose();
							new Element('span', {
								'class' : 'info'
							}).set('html', r).inject(c);
						}.bind(this)
					}).send();

					e.stop();
				}.bind(this));
			}.bind(this));
		}
	},

	deleteComplete : function (r) {
		var c = document.id('comment_' + r);
		var fx = new Fx.Morph(c, {
			duration : 1000,
			transition : Fx.Transitions.Quart.easeOut
		});
		fx.start({
			'opacity' : 0,
			'height' : 0
		}).chain(function () {
			c.dispose();
		});
	}
});