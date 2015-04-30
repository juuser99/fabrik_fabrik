/**
 * Thumbs Element
 *
 * @copyright: Copyright (C) 2005-2015, fabrikar.com - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

var FbThumbs =  new Class({
	Extends : FbElement,
	initialize: function (element, options, thumb) {
		this.field = document.id(element);
		this.parent(element, options);
		this.thumb = thumb;
		this.spinner = new Spinner(this.getContainer());
		this.setupj3();
	},
	
	setupj3: function () {
		var c = this.getContainer();
		var up = c.getElement('button.thumb-up');
		var down = c.getElement('button.thumb-down');
		
		up.addEvent('click', function (e) {
			e.stop();
			if (this.options.canUse) {
				var add = up.hasClass('btn-success') ? false : true;
				this.doAjax('up', add);
				if (!add) {
					up.removeClass('btn-success');
				} else {
					up.addClass('btn-success');
					if (typeOf(down) !== 'null') {
						down.removeClass('btn-danger');
					}
				}
			}
			else {
				this.doNoAccess();
			}
		}.bind(this));
		
		if (typeOf(down) !== 'null') {
			down.addEvent('click', function (e) {
				e.stop();
				if (this.options.canUse) {
					var add = down.hasClass('btn-danger') ? false : true;
					this.doAjax('down', add);
					if (!add) {
						down.removeClass('btn-danger');
					} else {
						down.addClass('btn-danger');
						up.removeClass('btn-success');
					}
				}
				else {
					this.doNoAccess();
				}
			}.bind(this));
		}
	},

	doAjax: function (th, add) {
		add = add ? true : false;
		if (this.options.editable === false) {
			this.spinner.show();
			var data = {
				'option': 'com_fabrik',
				'format': 'raw',
				'view': 'pluginAjax',
				'plugin': 'thumbs',
				'method': 'ajax_rate',
				'g': 'element',
				'element_id': this.options.elid,
				'row_id': this.options.row_id,
				'elementname': this.options.elid,
				'userid': this.options.userid,
				'thumb': th,
				'listid': this.options.listid,
				'formid': this.options.formid,
				'add': add
			};

			new Request({url: '',
				'data': data,
				onComplete: function (r) {
					r = JSON.decode(r);
					this.spinner.hide();
					if (r.error) {
						console.log(r.error);
					} else {
						if (r !== '') {
							var c = this.getContainer();
							c.getElement('button.thumb-up .thumb-count').set('text', r[0]);
							if (typeOf(c.getElement('button.thumb-down')) !== 'null') {
								c.getElement('button.thumb-down .thumb-count').set('text', r[1]);
							}
						}
					}
				}.bind(this)
			}).send();
		}
	},
	
	doNoAccess: function () {
		if (this.options.noAccessMsg !== '') {
			alert(this.options.noAccessMsg);
		}
	}
	
});